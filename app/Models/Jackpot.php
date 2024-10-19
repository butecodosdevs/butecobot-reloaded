<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jackpot extends Model
{
    protected $table = 'jackpots';

    protected $fillable = [
        'created_by',
        'game_result',
    ];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
