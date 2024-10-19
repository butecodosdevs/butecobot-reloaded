<?php

namespace App\SlashCommands\Traits\Jackpot;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;
use App\Entities\JackpotEntity;
use App\Entities\JackpotPlayerEntity;

/**
 * @mixin SlashCommand
 * @property JackpotEntity $game
 */
trait PlayJackpot
{
    /**
     * @param JackpotEntity $game
     * @return void
     */
    public function setGame(JackpotEntity $game): void
    {
        $this->games[$game->getId()] = $game;
    }

    /**
     * @param int $gameId
     * @return JackpotEntity
     */
    public function getGame(int $gameId): JackpotEntity
    {
        return $this->games[$gameId];
    }

    /**
     * @param int $gameId
     * @return bool
     */
    public function hasGame(int $gameId): bool
    {
        return isset($this->games[$gameId]);
    }

    /**
     * @param JackpotEntity $game
     * @return void
     */
    public function updateGame(JackpotEntity $game): void
    {
        $this->games[$game->getId()] = $game;
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function deleteGame(int $gameId): void
    {
        unset($this->games[$gameId]);
    }

    /**
     * @param int $gameId
     * @return int
     */
    public function setCounter(JackpotEntity $game, int $counter): void
    {
        $this->counters[$game->getId()] = $counter;
    }

    /**
     * @param JackpotEntity $game
     * @return int
     */
    public function getCounter(JackpotEntity $game): int
    {
        return $this->counters[$game->getId()];
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function decreaseCounter(int $gameId): void
    {
        $this->counters[$gameId]--;
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function deleteCounter(int $gameId): void
    {
        unset($this->counters[$gameId]);
    }

    /**
     * @param Interaction $interaction
     * @return void
     */
    public function startCounter(Interaction $interaction, JackpotEntity $game): void
    {
        $timer = $this->bot->getLoop()->addPeriodicTimer(1.5, function () use (&$timer, $interaction, $game) {
            $counter = $this->getCounter($game);
            $game = $this->getGame($game->getId());

            if ($counter === 0) {
                $this->bot->getLoop()->cancelTimer($timer);
                $game->setGameResult();

                $interaction->updateOriginalResponse(
                    $this->buildGameResults($game)
                );
                return;
            }

            $interaction->updateOriginalResponse(
                $this->buildGameMessage($game)
            );

            $this->decreaseCounter($game->getId());
        });
    }

    /**
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @param  string  $type
     * @param  int  $gameId
     * @return void
     */
    public function playJackpot(Interaction $interaction, int $gameId): void
    {
        $discordId = $interaction->member->user->id;
        $game = $this->getGame($gameId);
        $counter = $this->getCounter($game);

        if ($counter <= 1) {
            $interaction->respondWithMessage(
                $this
                    ->message('O tempo para jogar acabou!')
                    ->build(),
                true
            );
            return;
        }

        $playerBet = $game->setPlayerBet(new JackpotPlayerEntity($discordId));

        if (!$playerBet) {
            $interaction->respondWithMessage(
                $this
                    ->message('Você já fez sua jogada!')
                    ->build(),
                true
            );
            return;
        }

        $this->updateGame($game);

        $interaction->updateMessage(
            $this->buildGameMessage($game)
        );
    }

    /**
     * @return \Discord\Builders\MessageBuilder
     */
    public function buildGameMessage(JackpotEntity $game): MessageBuilder
    {
        $players = implode("\n", array_map(fn($player) => sprintf("<@%s>", $player->getDiscordId()), $game->getPlayers()));

        $counter = $this->getCounter($game);
        $id = $game->getId();

        return $this
            ->message("Custo: :coin: 200\nBora jogar que o tempo ja vai acabar!\n\n⏰ **$counter**")
            ->title("JACKPOT! ($id)")
            ->button("Jogar", route: "action:$id", style: 'primary')
            ->fields([
                'Jogador' => $players,
            ])
            ->build();
    }

    /**
     * @return \Discord\Builders\MessageBuilder
     */
    public function buildGameResults(JackpotEntity $game): MessageBuilder
    {
        $message = $game->getGameMessage();
        $players = implode("\n", array_map(fn($player) => sprintf("<@%s>", $player->getDiscordId()), $game->getPlayers()));
        $id = $game->getId();

        $this->deleteGame($id);
        $this->deleteCounter($id);

        $emoji = $game->getEmoji($game->getGameResult());
        $label = ucwords($game->getGameResult());
        $multiplier = $game->getGameMultiplier() * 200;

        return $this
            ->message("**$emoji $label**\n$message\n\nCusto: :coin: 200\nResultado: :coin: $multiplier\n\n")
            ->title("Jackpot! ($id)")
            ->fields([
                'Jogador' => $players,
            ])
            ->build();
    }
}
