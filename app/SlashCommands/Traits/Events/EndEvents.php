<?php

namespace App\SlashCommands\Traits\Events;

use Laracord\Commands\SlashCommand;
use App\Repositories\EventRepository;

/**
 * @mixin SlashCommand
 */
trait EndEvents
{
    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function endEvent($interaction)
    {
        $eventRepository = new EventRepository;
        $eventId = $this->value('encerrar.evento');
        $choiceOption = strtoupper($this->value('encerrar.opcao'));
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

        if ($event['status'] == EventRepository::PAID) {
            $interaction->respondWithMessage(
                $this->message('Este evento já foi pago!')
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->error()
                    ->build(),
                true
            );
            return;
        }

        if ($event['status'] !== EventRepository::CLOSED) {
            $interaction->respondWithMessage(
                $this->message('Evento precisa estar fechado para ser finalizado!')
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->error()
                    ->build(),
                true
            );
            return;
        }

        if ($choiceOption === 'EMPATE') {
            if (!$eventRepository->drawEvent($eventId)) {
                $interaction->respondWithMessage(
                    $this->message('Erro ao encerrar evento')
                        ->title('Eventos')
                        ->error()
                        ->build(),
                    true
                );
                return;
            }

            $interaction->respondWithMessage(
                $this->message(sprintf(":necktie: Evento empatado! Apostas devolvidas"))
                    ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                    ->authorName('')
                    ->authorIcon('')
                    ->warning()
                    ->build(),
                false
            );
            return;
        }

        $payoutEvent = $eventRepository->payoutEvent($eventId, $choiceOption);

        if (count($payoutEvent['winners']) === 0) {
            $interaction->respondWithMessage(
                $this->message('Não houveram apostas neste evento!')
                    ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                    ->authorIcon('')
                    ->authorName('')
                    ->warning()
                    ->build(),
                    false
                );
            return;
        }

        $winnersImage = config('butecobot.images.winners')[array_rand(config('butecobot.images.winners'))];
        $winnersLabel = '';
        $earningsLabel = '';

        foreach ($payoutEvent['winners'] as $winner) {
            if ($winner['choice'] == $choiceOption) {
                $winnersLabel .= sprintf("<@%s> \n", $winner['discord_id']);
                $earningsLabel .= sprintf("B$ %s %s \n", number_format($winner['earnings'], 2, '.', ','), $winner['extraLabel']);
            }
        }

        $eventsDescription = sprintf(
            "**Vencedor do evento**: \n**:star2: %s: %s**\n",
            $choiceOption,
            $payoutEvent['winner_choice']['description']
        );

        $interaction->respondWithMessage(
            $this->message($eventsDescription)
                ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                ->authorName('')
                ->authorIcon('')
                ->color('#F5D920')
                ->fields([
                    'Ganhador' => $winnersLabel,
                    'Valor' => $earningsLabel
                ])
                ->image($winnersImage)
                ->success()
                ->build()
        );
    }
}
