<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;
use App\Models\Mongo\Message as MessageModel;

class RecordReactionAdd extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_ADD;

    /**
     * Handle the event.
     */
    public function handle(MessageReaction $reaction, Discord $discord)
    {
        $message = MessageModel::where('message_id', $reaction->message_id)->first();

        if (!$message) {
            return;
        }

        $emoji = $reaction->emoji->id ?? $reaction->emoji->name;
        $message->emojis = array_merge($message->emojis ?? [], [$emoji]);
        $message->save();
    }
}
