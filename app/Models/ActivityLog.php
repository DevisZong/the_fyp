<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'user_name',
        'action',
        'details',
        'ip_address',
        'user_agent',
        'status',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // Scopes for filtering
    public function scopeByStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType(Builder $query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange(Builder $query, string $range)
    {
        $now = now();

        switch ($range) {
            case 'today':
                return $query->whereDate('created_at', $now->toDateString());
            case 'yesterday':
                return $query->whereDate('created_at', $now->subDay()->toDateString());
            case 'week':
                return $query->where('created_at', '>=', $now->subWeek());
            case 'month':
                return $query->where('created_at', '>=', $now->subMonth());
            default:
                return $query;
        }
    }

    public function scopeSearch(Builder $query, string $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('user_name', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%")
                ->orWhere('details', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%");
        });
    }

    // Helper method to get activity stats
    public static function getStats()
    {
        $today = now()->toDateString();

        return [
            'total_logins' => self::where('type', 'login')
                ->where('status', 'success')
                ->whereDate('created_at', $today)
                ->count(),
            'active_users' => self::where('type', 'login')
                ->where('status', 'success')
                ->whereDate('created_at', $today)
                ->distinct('user_id')
                ->count(),
            'error_count' => self::where('status', 'error')
                ->whereDate('created_at', $today)
                ->count(),
            'last_activity' => self::latest()->first()?->created_at?->diffForHumans() ?? 'No activity'
        ];
    }
}
