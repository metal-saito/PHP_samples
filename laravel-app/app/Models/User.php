<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * ユーザーモデル
 * 
 * アプリケーションのユーザーを表現するモデル。
 * Laravel Sanctumによるトークン認証をサポートし、
 * 複数のタスクを所有できます。
 *
 * @property int $id ユーザーID
 * @property string $name ユーザー名
 * @property string $email メールアドレス
 * @property string $password パスワード（ハッシュ化）
 * @property \Illuminate\Support\Carbon|null $email_verified_at メール認証日時
 * @property string|null $remember_token Remember Meトークン
 * @property \Illuminate\Support\Carbon $created_at 作成日時
 * @property \Illuminate\Support\Carbon $updated_at 更新日時
 * @property-read \Illuminate\Database\Eloquent\Collection<Task> $tasks タスクのコレクション
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * このユーザーが所有するタスクを取得
     * 
     * ユーザーは複数のタスクを作成・所有できます。
     *
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * このユーザーの完了していないタスクを取得
     *
     * @return HasMany<Task>
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)
                    ->whereNotIn('status', Task::COMPLETED_STATUSES);
    }

    /**
     * このユーザーの完了したタスクを取得
     *
     * @return HasMany<Task>
     */
    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class)
                    ->whereIn('status', Task::COMPLETED_STATUSES);
    }
}
