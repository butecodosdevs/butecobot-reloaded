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
                ->build(),
                true
            );
            return;
        }

        if (!in_array($event['status'], [EventRepository::OPEN])) {
            $interaction->respondWithMessage(
                $this->message('Evento precisa estar aberto!')
                ->title('Eventos')
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

        $eventOdds = $eventRepository->calculateOdds($eventId);
        $eventsDescription = sprintf(
            "**Status do Evento:** %s \n **A**: %s \n **B**: %s \n \n",
            $eventRepository::LABEL[$event['status']],
            sprintf('%s (x%s)', $event['choices'][0]['description'], number_format($eventOdds['odds_a'], 2)),
            sprintf('%s (x%s)', $event['choices'][1]['description'], number_format($eventOdds['odds_b'], 2))
        );

        $interaction->respondWithMessage(
            $this->message($eventsDescription)
            ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
            ->color('#F5D920')
            ->button('Apostar A', route: "betOn:{$event['id']}:A")
            ->button('Apostar B', route: "betOn:{$event['id']}:B")
            ->image(config('butecobot.images.events')[$bannerKey])
            ->build()
        );
    }
}
