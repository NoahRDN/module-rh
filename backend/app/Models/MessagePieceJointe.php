<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessagePieceJointe extends Model
{
    protected $table = 'message_pieces_jointes';

    protected $fillable = [
        'message_id',
        'nom',
        'chemin',
        'type_mime',
        'taille',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
