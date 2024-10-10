<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JokenpoPlayer extends Model
{
    protected $table = 'jokenpo_players';

    protected $fillable = [
        'jokenpo_id',
        'user_id',
        'move',
        'amount',
        'result',
    ];

    public function jokenpo()
    {
        return $this->belongsTo(Jokenpo::class, 'jokenpo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}