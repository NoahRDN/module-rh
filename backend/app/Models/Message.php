<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'contenu',
        'lu',
        'lu_at',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Mettre à jour la dernière activité de la conversation
            $message->conversation->mettreAJourActivite();
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function piecesJointes()
    {
        return $this->hasMany(MessagePieceJointe::class);
    }

    public function marquerCommeLu()
    {
        if (!$this->lu) {
            $this->lu = true;
            $this->lu_at = now();
            $this->save();
        }
    }

    public function scopeNonLus($query)
    {
        return $query->where('lu', false);
    }
}
