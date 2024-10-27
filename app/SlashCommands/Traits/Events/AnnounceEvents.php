<?php

namespace App\SlashCommands\Traits\Events;

use Discord\Voice\VoiceClient;
use Laracord\Commands\SlashCommand;
use Illuminate\Support\Facades\Storage;
use App\Repositories\EventRepository;

/**
 * @mixin SlashCommand
 */
trait AnnounceEvents
{
    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function announceEvent($interaction): void
    {
        $eventRepository = new EventRepository;
        $eventId = $this->value('anunciar.evento');
        $bannerKey = $this->value('anunciar.banner');
        $event = $eventRepository->getEventById($eventId);

        if (!$event) {
            $interaction->respondWithMessage(
                $this->message('Evento não existe')
                ->title('Eventos')
                ->authorName('')
                ->authorIcon('')
                ->error()
                ->build(),
                true
            );
            return;
        }

        if (!in_array($event['status'], [EventRepository::OPEN])) {
            $interaction->respondWithMessage(
                $this->message('Evento precisa estar aberto!')
                ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                ->authorName('')
                ->authorIcon('')
                ->error()
                ->build(),
                true
            );
            return;
        }

        $channel = $this->discord->getChannel($interaction->channel_id);
        $audio = Storage::path('sounds/rumble.mp3');
        $voice = $this->discord->getVoiceClient($channel->guild_id);

        if ($channel->isVoiceBased() && $bannerKey === 'UFC') {
            if ($voice) {
                $voice->playFile($audio);
            } else {
                $this->discord->joinVoiceChannel($channel)->then(function (VoiceClient $voice) use ($audio) {
                    $voice->playFile($audio);
                });
            }
        }

        $statusMessage = match($event['status']) {
            $eventRepository::CLOSED => ':red_square: Fechado para apostas',
            default => ':green_square: Aberto para apostas',
        };

        $eventOdds = $eventRepository->calculateOdds($eventId);
        $eventsDescription = sprintf(
            "**%s** \n\n **A**: %s \n **B**: %s \n\n",
            $statusMessage,
            sprintf('%s (x%s)', $event['choices'][0]['description'], number_format($eventOdds['odds_a'], 2)),
            sprintf('%s (x%s)', $event['choices'][1]['description'], number_format($eventOdds['odds_b'], 2))
        );

        $interaction->respondWithMessage(
            $this->message($eventsDescription)
            ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
            ->authorName('')
            ->authorIcon('')
            ->color('#F5D920')
            ->button("A: " . $event['choices'][0]['description'], route: "betOn:{$event['id']}:A", style: 'secondary', emoji: '🎫')
            ->button("B: " . $event['choices'][1]['description'], route: "betOn:{$event['id']}:B", style: 'secondary', emoji: '🎫')
            ->image(config('butecobot.images.events')[$bannerKey])
            ->info()
            ->build()
        );
    }
}
