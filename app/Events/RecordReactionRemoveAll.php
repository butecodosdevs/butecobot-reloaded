<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Facades\Date;
use Laracord\Events\Event;
use App\Models\Mongo\Message as MessageModel;

class RecordReactionRemoveAll extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_REMOVE_ALL;

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
            'remove_all' => true,
            'is_custom' => false,
            'react' => false,
            'emoji' => null,
            'author_id' => null,
            'created_at' => Date::now(),
        ];
        $message->emojis_history = array_merge($message->emojis_history ?? [], [$emojiRecord]);
        $message->save();
    }
}
