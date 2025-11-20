<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'resource_name',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * 他の予約と時間が重複しているかチェック
     */
    public function overlaps(Reservation $other): bool
    {
        if ($this->resource_name !== $other->resource_name) {
            return false;
        }

        return $this->starts_at < $other->ends_at && $other->starts_at < $this->ends_at;
    }

    /**
     * アクティブな予約を取得
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'booked');
    }
}

