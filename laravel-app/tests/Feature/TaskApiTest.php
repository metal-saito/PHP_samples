<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        return [$user, $token];
    }

    public function test_authenticated_user_can_create_task(): void
    {
        [$user, $token] = $this->authenticateUser();

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'high',
            'due_date' => now()->addDays(7)->toIso8601String(),
        ];

        $response = $this->withToken($token)
                         ->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'status',
                         'priority',
                         'due_date',
                         'user',
                     ],
                 ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id,
        ]);
    }

    public function test_task_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
        ]);

        $response->assertStatus(401);
    }

    public function test_task_creation_validates_required_fields(): void
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)
                         ->postJson('/api/tasks', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_list_their_tasks(): void
    {
        [$user, $token] = $this->authenticateUser();

        Task::factory()->count(3)->create(['user_id' => $user->id]);
        Task::factory()->count(2)->create(); // Other user's tasks

        $response = $this->withToken($token)
                         ->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_task(): void
    {
        [$user, $token] = $this->authenticateUser();

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken($token)
                         ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $task->id);
    }

    public function test_user_cannot_view_other_users_task(): void
    {
        [$user, $token] = $this->authenticateUser();
        
        $otherUserTask = Task::factory()->create();

        $response = $this->withToken($token)
                         ->getJson("/api/tasks/{$otherUserTask->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_task(): void
    {
        [$user, $token] = $this->authenticateUser();

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken($token)
                         ->putJson("/api/tasks/{$task->id}", [
                             'title' => 'Updated Title',
                             'status' => 'completed',
                         ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_their_task(): void
    {
        [$user, $token] = $this->authenticateUser();

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken($token)
                         ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_can_get_task_statistics(): void
    {
        [$user, $token] = $this->authenticateUser();

        Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Task::factory()->create(['user_id' => $user->id, 'priority' => 'high']);

        $response = $this->withToken($token)
                         ->getJson('/api/tasks-statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'by_status' => ['pending', 'in_progress', 'completed', 'cancelled'],
                     'by_priority' => ['low', 'medium', 'high', 'urgent'],
                     'overdue',
                 ]);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        [$user, $token] = $this->authenticateUser();

        Task::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $response = $this->withToken($token)
                         ->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }
}
