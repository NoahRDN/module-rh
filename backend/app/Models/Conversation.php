<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'sujet',
        'employe_id',
        'statut',
        'priorite',
        'assigne_a',
        'derniere_activite',
    ];

    protected $casts = [
        'derniere_activite' => 'datetime',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function assigneA()
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function dernierMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function messagesNonLus()
    {
        return $this->messages()->where('lu', false);
    }

    public function scopeOuvertes($query)
    {
        return $query->where('statut', 'ouverte');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeFermees($query)
    {
        return $query->where('statut', 'fermee');
    }

    public function mettreAJourActivite()
    {
        $this->derniere_activite = now();
        $this->save();
    }

    public function fermer()
    {
        $this->statut = 'fermee';
        $this->save();
    }

    public function rouvrir()
    {
        $this->statut = 'ouverte';
        $this->save();
    }
}
