<?php
namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'guild_id',
        'message_id',
        'discord_id',
        'channel_id',
        'channel',
        'content',
        'content_history',
        'emojis_history',
        'sticker_items',
        'attachments',
        'deleted',
    ];
}