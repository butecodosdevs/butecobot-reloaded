<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;
use Illuminate\Support\Facades\Date;
use App\Models\Mongo\Message as MessageModel;

class RecordMessageUpdates extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_UPDATE;

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord, ?Message $oldMessage)
    {
        if ($message->author->bot) {
            return;
        }

        $messageRecord = MessageModel::where('message_id', $message->id)->first();
        $history = $messageRecord->content_history ?? [];

        if (!empty($messageRecord->content)) {
            $oldMessageHistory = [
                'content' => $messageRecord->content,
                'created_at' => Date::now(),
            ];
            $history = array_merge($history, [ $oldMessageHistory ]);
        }

        $messageRecord->content = $message->content;
        $messageRecord->content_history = $history;
        $messageRecord->save();
    }
}
