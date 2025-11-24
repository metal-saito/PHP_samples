<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::with('user')->forUser($request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter overdue tasks
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->get('status', 'pending'),
            'priority' => $request->get('priority', 'medium'),
            'due_date' => $request->due_date,
        ]);

        return new TaskResource($task->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task): TaskResource
    {
        $this->authorize('view', $task);
        
        return new TaskResource($task->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Get task statistics for the authenticated user.
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total' => Task::forUser($userId)->count(),
            'by_status' => [
                'pending' => Task::forUser($userId)->status('pending')->count(),
                'in_progress' => Task::forUser($userId)->status('in_progress')->count(),
                'completed' => Task::forUser($userId)->status('completed')->count(),
                'cancelled' => Task::forUser($userId)->status('cancelled')->count(),
            ],
            'by_priority' => [
                'low' => Task::forUser($userId)->where('priority', 'low')->count(),
                'medium' => Task::forUser($userId)->where('priority', 'medium')->count(),
                'high' => Task::forUser($userId)->where('priority', 'high')->count(),
                'urgent' => Task::forUser($userId)->where('priority', 'urgent')->count(),
            ],
            'overdue' => Task::forUser($userId)->overdue()->count(),
        ];

        return response()->json($stats);
    }
}
