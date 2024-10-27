<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Facades\Date;
use Laracord\Events\Event;
use App\Models\Mongo\Message as MessageModel;

class RecordReactionRemove extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_REMOVE;

    /**
     * Handle the event.
     */
    public function handle(MessageReaction $reaction, Discord $discord)
    {
        $message = MessageModel::where('message_id', $reaction->message_id)->first();

        if (!$message) {
            return;
        }

        $emojiRecord = [
            'remove_all' => false,
            'is_custom' => $reaction->emoji->id !== null,
            'react' => false,
            'emoji' => $reaction->emoji->id ?? $reaction->emoji->name,
            'author_id' => $reaction->member->user->id,
            'created_at' => Date::now(),
        ];
        $message->emojis_history = array_merge($message->emojis_history ?? [], [$emojiRecord]);
        $message->save();
    }
}
