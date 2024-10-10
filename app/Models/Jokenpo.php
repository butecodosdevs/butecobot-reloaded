<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jokenpo extends Model
{
    protected $table = 'jokenpo';

    protected $fillable = [
        'created_by',
        'bot_move',
    ];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}