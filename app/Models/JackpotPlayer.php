<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JackpotPlayer extends Model
{
    protected $table = 'jackpot_players';

    protected $fillable = [
        'jackpot_id',
        'user_id',
        'bet',
        'result',
    ];

    public function jackpot()
    {
        return $this->belongsTo(Jackpot::class, 'jackpot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
