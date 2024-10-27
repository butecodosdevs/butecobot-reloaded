<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Facades\Storage;
use Laracord\Events\Event;
use App\Models\Mongo\Message as MessageModel;
use App\Models\Mongo\User as UserModel;

class RecordMessage extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_CREATE;

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord)
    {
        if ($message->author->bot) {
            return;
        }

        $user = UserModel::where('guild_id', $message->guild_id)->where('discord_id', $message->author->id)->first();

        if (!$user) {
            $newAvatarUrlQuery = parse_url($message->author->avatar, PHP_URL_QUERY);
            $newAvatarFilename = basename($message->author->avatar, '?' . $newAvatarUrlQuery);
            $newAvatarContent = @file_get_contents($message->author->avatar);

            if ($newAvatarContent) {
                Storage::put("avatars/{$message->guild_id}/{$message->author->id}/{$newAvatarFilename}", $newAvatarContent);
            }

            UserModel::create([
                'guild_id' => $message->guild_id,
                'discord_id' => $message->author->id,
                'username' => $message->author->username,
                'global_name' => $message->author->global_name,
                'avatar' => $message->author->avatar,
                'avatar_filename' => $newAvatarFilename,
                'username_history' => [],
                'global_name_history' => [],
                'avatar_history' => [],
            ]);
        }

        $stickerItems = $message->sticker_items !== null ? $message->sticker_items->toArray() : [];
        $attachments = $message->attachments !== null ? $message->attachments->toArray() : [];

        if (count($stickerItems) > 0) {
            $stickerItems = array_values(
                array_map(function ($stickerItem) {
                    return [
                        'sticker_id' => $stickerItem->id,
                        'name' => $stickerItem->name,
                        'format_type' => $stickerItem->format_type,
                    ];
                }, $stickerItems)
            );
        }

        if (count($attachments) > 0) {
            $attachments = array_values(
                array_map(function ($attachment) {
                    return [
                        'attachment_id' => $attachment->id,
                        'url' => $attachment->url,
                        'type' => $attachment->content_type,
                        'filename' => $attachment->filename,
                        'size' => $attachment->size,
                    ];
                }, $attachments)
            );
        }

        MessageModel::create([
            'guild_id' => $message->guild_id,
            'message_id' => $message->id,
            'discord_id' => $message->author->id,
            'channel_id' => $message->channel->id,
            'channel' => $message->channel->name,
            'username' => $message->author->username,
            'content' => $message->content,
            'content_history' => [],
            'emojis_history' => [],
            'sticker_items' => $stickerItems,
            'attachments' => $attachments,
            'deleted' => false,
        ]);
    }
}
