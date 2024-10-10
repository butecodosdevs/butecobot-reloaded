<?php

namespace App\SlashCommands\Traits\Jokenpo;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Facades\Redis;
use Laracord\Commands\SlashCommand;
use App\Entities\JokenpoEntity;
use App\Entities\JokenpoPlayerEntity;
use App\Models\Jokenpo;
use App\Models\JokenpoPlayer;
use App\Repositories\UserCoinHistoryRepository;
use App\Repositories\UserRepository;

/**
 * @mixin SlashCommand
 * @property JokenPoEntity $game
 */
trait PlayJokenpo
{
    /**
     * @param JokenpoEntity $game
     * @return void
     */
    public function setGame(JokenpoEntity $game) : void
    {
        $this->games[$game->getId()] = $game;
    }

    /**
     * @param int $gameId
     * @return JokenpoEntity
     */
    public function getGame(int $gameId) : JokenpoEntity
    {
        return $this->games[$gameId];
    }

    /**
     * @param int $gameId
     * @return bool
     */
    public function hasGame(int $gameId) : bool
    {
        return isset($this->games[$gameId]);
    }

    /**
     * @param JokenpoEntity $game
     * @return void
     */
    public function updateGame(JokenpoEntity $game) : void
    {
        $this->games[$game->getId()] = $game;
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function deleteGame(int $gameId) : void
    {
        unset($this->games[$gameId]);
    }

    /**
     * @param int $gameId
     * @return int
     */
    public function setCounter(JokenpoEntity $game, int $counter) : void
    {
        $this->counters[$game->getId()] = $counter;
    }

    /**
     * @param JokenpoEntity $game
     * @return int
     */
    public function getCounter(JokenpoEntity $game) : int
    {
        return $this->counters[$game->getId()];
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function decreaseCounter(int $gameId) : void
    {
        $this->counters[$gameId]--;
    }

    /**
     * @param int $gameId
     * @return void
     */
    public function deleteCounter(int $gameId) : void
    {
        unset($this->counters[$gameId]);
    }

    /**
     * @param Interaction $interaction
     * @return void
     */
    public function startCounter(Interaction $interaction, JokenpoEntity $game) : void
    {
        $timer = $this->bot->getLoop()->addPeriodicTimer(1.5, function () use (&$timer, $interaction, $game) {
            $counter = $this->getCounter($game);
            $game = $this->getGame($game->getId());

            if ($counter === 0) {
                $this->bot->getLoop()->cancelTimer($timer);
                $game->setBotMove();

                Redis::del("jokenpo:game:{$game->getId()}");
                Jokenpo::find($game->getId())->update([
                    'bot_move' => $game->getBotMove(),
                ]);

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
    public function playJokenpo(Interaction $interaction, string $type, int $gameId) : void
    {
        $discordId = $interaction->member->user->id;
        $userCoinHistoryRepository = new UserCoinHistoryRepository;
        $userRepository = new UserRepository;
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

        if ($userCoinHistoryRepository->hasAvailableCoins($discordId, 200.0) === false) {
            $interaction->respondWithMessage(
                $this
                ->message('Você não tem coins suficientes para jogar!')
                ->build(),
                true
            );
            return;
        }

        $playerMove = $game->setPlayerMove(new JokenPoPlayerEntity($discordId, $type));

        if (!$playerMove) {
            $interaction->respondWithMessage(
                $this
                ->message('Você já fez sua jogada!')
                ->build(),
                true
            );
            return;
        }

        $userCoinHistoryRepository->spendCoins(
            $discordId,
            -200,
            'Jokenpo',
            [
                'game_id' => $game->getId(),
                'move' => $type,
            ]
        );

        $this->updateGame($game);
        Redis::set("jokenpo:game:" . $game->getId(), serialize($game));
        JokenpoPlayer::create([
            'jokenpo_id' => $game->getId(),
            'user_id' => $userRepository->getByDiscordId($discordId)['id'],
            'move' => $type,
            'amount' => 200,
        ]);

        $interaction->updateMessage(
            $this->buildGameMessage($game)
        );
    }

    /**
     * @return \Discord\Builders\MessageBuilder
     */
    public function buildGameMessage(JokenpoEntity $game) : MessageBuilder
    {
        $players = implode("\n", array_map(fn($player) => sprintf("<@%s>", $player->getDiscordId()), $game->getPlayers()));
        $choices = implode("\n", array_map(fn($player) => sprintf("%s %s", $game->getEmoji($player->getMove()), ucwords($player->getMove())), $game->getPlayers()));

        return $this
        ->message(sprintf("Ado, ado, ado quem perder é...ruim!\n\nCusto: :coin: 200\nPrêmio: :coin: 400\n\n⏰: **%s**", $this->getCounter($game)))
        ->title(sprintf('JO-KEN-PÔ! (%s)', $game->getId()))
        ->image(config('butecobot.images.jokenpo'))
        ->fields([
            'Jogador' => $players,
            'Escolheu' => $choices,
        ])
        ->button("Pedra", route: 'action:pedra:' . $game->getId(), style: 'secondary', emoji: $game->getEmoji('pedra'))
        ->button("Papel", route: 'action:papel:' . $game->getId(), style: 'secondary', emoji: $game->getEmoji('papel'))
        ->button("Tesoura", route: 'action:tesoura:' . $game->getId(), style: 'secondary', emoji: $game->getEmoji('tesoura'))
        ->button("Lagarto", route: 'action:lagarto:' . $game->getId(), style: 'secondary', emoji: $game->getEmoji('lagarto'))
        ->button("Spock", route: 'action:spock:' . $game->getId(), style: 'secondary', emoji: $game->getEmoji('spock'))
        ->build();
    }

    /**
     * @return \Discord\Builders\MessageBuilder
     */
    public function buildGameResults(JokenpoEntity $game) : MessageBuilder
    {
        $userCoinHistoryRepository = new UserCoinHistoryRepository;
        $userRepository = new UserRepository;
        $evaluatedPlayers = $game->evaluateMoves();
        $players = implode("\n", array_map(fn($player) => sprintf("<@%s>", $player->getDiscordId()), $game->getPlayers()));
        $choices = implode("\n", array_map(fn($player) => sprintf("%s %s", $game->getEmoji($player->getMove()), ucwords($player->getMove())), $game->getPlayers()));
        $results = implode("\n", array_map(fn($player) => ucwords($player->getResult()), $evaluatedPlayers));

        Jokenpo::find($game->getId())->update([
            'bot_move' => $game->getBotMove(),
        ]);

        // TODO: Refactor this to a repository
        array_map(function ($player) use ($game, $userRepository, $userCoinHistoryRepository) {
            JokenpoPlayer::where('jokenpo_id', $game->getId())
                ->where('user_id', $userRepository->getByDiscordId($player->getDiscordId())['id'])
                ->update(['result' => $player->getResult()]);

            if ($player->getResult() === 'ganhou') {
                $userCoinHistoryRepository->spendCoins(
                    $player->getDiscordId(),
                    400,
                    'Jokenpo',
                    [
                        'game_id' => $game->getId(),
                        'move' => $player->getMove(),
                        'result' => $player->getResult(),
                        'bot_move' => $game->getBotMove(),
                    ]
                );
                return;
            }
        }, $evaluatedPlayers);

        $this->deleteGame($game->getId());
        $this->deleteCounter($game->getId());

        return $this
        ->message(
            sprintf(
                "Eu escolhi: **%s**\n\nCusto: :coin: 200\nPrêmio: :coin: 400\n\n",
                sprintf("%s %s", $game->getEmoji($game->getBotMove()), ucwords($game->getBotMove()))
            )
        )
        ->title(sprintf('JO-KEN-PÔ! (%s)', $game->getId()))
        ->fields([
            'Jogador' => $players,
            'Escolheu' => $choices,
            'Resultado' => $results,
        ])
        ->build();
    }
}