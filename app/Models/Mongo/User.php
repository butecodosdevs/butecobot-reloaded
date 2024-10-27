<?php
namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class User extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'guild_id',
        'discord_id',
        'username',
        'global_name',
        'avatar',
        'avatar_filename',
        'avatar_history',
        'username_history',
        'global_name_history',
    ];
}