<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'data',
        'lu',
        'lu_at',
    ];

    protected $casts = [
        'data' => 'array',
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marquerCommeLu()
    {
        $this->lu = true;
        $this->lu_at = now();
        $this->save();
        Cache::forget("notifications:unread_count:{$this->user_id}");
    }

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopeRecentes($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Créer une notification pour un utilisateur
     */
    public static function creer(
        int $userId,
        string $type,
        string $titre,
        string $message,
        ?array $data = null
    ): self {
        $notification = self::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'data' => $data,
        ]);

        Cache::forget("notifications:unread_count:{$userId}");

        return $notification;
    }
}
