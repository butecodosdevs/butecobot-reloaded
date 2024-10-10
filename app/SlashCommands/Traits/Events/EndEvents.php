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
                    ->build(),
                true
            );
            return;
        }

        if ($event['status'] == EventRepository::PAID) {
            $interaction->respondWithMessage(
                $this->message('Este evento já foi pago!')
                    ->title('Eventos')
                    ->build(),
                true
            );
            return;
        }

        if ($event['status'] !== EventRepository::CLOSED) {
            $interaction->respondWithMessage(
                $this->message('Evento precisa estar fechado para ser finalizado!')
                    ->title('Eventos')
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
                        ->build(),
                    true
                );
                return;
            }

            $interaction->respondWithMessage(
                $this->message(sprintf("Evento: #%s %s\nResultado: Empate!\n\nApostas devolvidas", $eventId, $event['name']))
                    ->title('Eventos')
                    ->build(),
                false
            );
            return;
        }

        $payoutEvent = $eventRepository->payoutEvent($eventId, $choiceOption);

        if (count($payoutEvent['winners']) === 0) {
            $interaction->respondWithMessage(
                $this->message('Não houveram apostas neste evento!')
                    ->title('Eventos')
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
                $earningsLabel .= sprintf("%s %s \n", $winner['earnings'], $winner['extraLabel']);
            }
        }

        $eventsDescription = sprintf(
            "**%s** \n **Vencedor**: %s - %s \n \n \n",
            $event['name'],
            $choiceOption,
            $payoutEvent['winner_choice']['description']
        );

        $interaction->respondWithMessage(
            $this->message($eventsDescription)
                ->title('Eventos')
                ->color('#F5D920')
                ->fields([
                    'Ganhador' => $winnersLabel,
                    'Valor' => $earningsLabel
                ])
                ->image($winnersImage)
                ->build()
        );
    }
}
