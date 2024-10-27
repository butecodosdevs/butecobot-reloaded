<?php
namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class UserStatus extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'guild_id',
        'discord_id',
        'status',
        'client'
    ];
}