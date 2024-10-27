<?php

namespace App\Events;

use Discord\Discord;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;
use App\Models\Mongo\Message as MessageModel;

class RecordMessageDelete extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_DELETE;

    /**
     * Handle the event.
     */
    public function handle(object $message, Discord $discord)
    {
        $message = MessageModel::where('message_id', $message->id)->first();
        $message->deleted = true;
        $message->save();
    }
}
