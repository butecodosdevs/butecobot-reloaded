<?php

namespace App\SlashCommands\Traits\Events;

use Laracord\Commands\SlashCommand;
use App\Repositories\EventRepository;

/**
 * @mixin SlashCommand
 */
trait CloseEvents
{
    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function closeEvent($interaction): void
    {
        $eventRepository = new EventRepository;
        $eventId = $this->value('fechar.evento');
        $event = $eventRepository->getEventById($eventId);

        if ($event['status'] !== EventRepository::OPEN) {
            $interaction->respondWithMessage(
                $this->message(sprintf('Evento **#%s** precisa estar aberto para ser fechado!', $eventId))
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->build(),
                false
            );
            return;
        }

        if (!$event) {
            $interaction->respondWithMessage(
                $this->message(sprintf('Evento **#%s** não existe!', $eventId))
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->build(),
                false
            );
            return;
        }

        if (!$eventRepository->closeEvent($eventId)) {
            $interaction->respondWithMessage(
                $this->message(sprintf('Ocorreu um erro ao finalizar evento **#%s**', $eventId))
                    ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                    ->authorName('')
                    ->authorIcon('')
                    ->error()
                    ->build(),
                false
            );
            return;
        }

        $interaction->respondWithMessage(
            $this->message("Evento fechado! :lock: \n Esse evento não recebe mais apostas!")
                ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                ->authorName('')
                ->authorIcon('')
                ->info()
                ->build(),
            false
        );
        return;
    }
}
