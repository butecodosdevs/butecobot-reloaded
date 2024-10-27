<?php
namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class UserActivity extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'guild_id',
        'discord_id',
        'presence_type',
        'activity_name',
        'activity_type',
        'activity_emoji',
        'status_client',
        'status_state',
        'hash',
    ];
}