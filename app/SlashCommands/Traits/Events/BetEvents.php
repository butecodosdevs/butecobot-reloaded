<?php

namespace App\SlashCommands\Traits\Events;

use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;
use App\Repositories\EventRepository;
use App\Repositories\EventBetRepository;
use App\Repositories\UserRepository;

/**
 * @mixin SlashCommand
 */
trait BetEvents
{
    /**
     * @param \Discord\Parts\Interactions\Interaction $interaction
     * @param string $choice
     * @return void
     */
    public function betEventModal($interaction, int $event, string $choice): void
    {
        $this
            ->modal('Apostar no Evento')
            ->text('Evento', placeholder: 'Digite o ID do evento', required: true, value: $event)
            ->text('Escolha', placeholder: 'Escolha A ou B', required: true, value: $choice)
            ->text('Valor', placeholder: 'Qual valor deseja apostar?', required: true, value: '')
            ->submit(fn ($interaction, $components) => $this->betEventModalHandler($interaction, $components))
            ->show($interaction);
    }

    /**
     * @param \Discord\Parts\Interactions\Interaction $interaction
     * @param \Discord\Helpers\Collection $components
     * @return void
     */
    public function betEventModalHandler(Interaction $interaction, Collection $components): void
    {
        $event = $components->get('custom_id', 'evento')->value;
        $choice = $components->get('custom_id', 'escolha')->value;
        $amount = $components->get('custom_id', 'valor')->value;

        $this->betEventBet($interaction, $event, $choice, $amount);
    }

    /**
     * @param mixed $interaction
     * @param mixed $betEventId
     * @param mixed $betChoice
     * @param mixed $betAmount
     * @return void
     */
    public function betEventBet(Interaction $interaction, $betEventId, $betChoice, $betAmount): void
    {
        $userRepository = new UserRepository;
        $eventRepository = new EventRepository;
        $eventBetRepository = new EventBetRepository;
        $discordId = $interaction->member->user->id;
        $user = $userRepository->getByDiscordId($discordId);
        $betChoice = strtoupper($betChoice);

        if (!is_numeric($betEventId)) {
            $interaction->respondWithMessage(
                $this->message('ID do evento inválido')
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->error()
                    ->build(),
                true
            );
            return;
        }

        $event = $eventRepository->getEventById($betEventId);
        $errorMessage = match(true) {
            !$user => 'Você ainda não coleteu suas coins iniciais! Digita **/coins** e pegue suas coins! :coin::coin::coin:',
            !$event => "Evento **#{$betEventId}** não existe",
            $event['status'] !== EventRepository::OPEN => 'Evento fechado para apostas!',
            $eventBetRepository->didUserBet($discordId, $betEventId) => 'Você já apostou neste evento!',
            $betAmount <= 0 || !is_numeric($betAmount) => 'Valor da aposta inválido, o valor deve ser acima de 0',
            !$userRepository->hasAvailableCoins($discordId, $betAmount) => 'Você não possui coins suficientes!',
            !in_array($betChoice, ['A', 'B']) => 'Escolha inválida, escolha A ou B',
            default => null,
        };

        if ($errorMessage) {
            $interaction->respondWithMessage(
                $this->message($errorMessage)
                    ->title('Eventos')
                    ->authorName('')
                    ->authorIcon('')
                    ->error()
                    ->build(),
                true
            );
            return;
        }

        $bet = $eventBetRepository->create($discordId, $betEventId, $betChoice, $betAmount);
        $choiceDetails = array_values(array_filter(
            $event['choices']->toArray(),
            fn ($item) => $item['choice'] === $betChoice
        ));

        if ($bet) {
            $interaction->respondWithMessage(
                $this->message(sprintf(
                    "🎫 **%s: %s** \n\nValor apostado: **B$ %s**",
                    $betChoice,
                    $choiceDetails[0]['description'],
                    number_format($betAmount, 2, '.', ','),
                    $betChoice
                ))
                ->title(sprintf('[#%s] %s', $event['id'], $event['name']))
                ->color('#F5D920')
                ->authorName('')
                ->authorIcon('')
                ->thumbnail(config('butecobot.images.place_bet'))
                ->info()
                ->build(),
                true
            );
        }
    }
}
