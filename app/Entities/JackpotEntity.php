<?php

namespace App\Entities;

class JackpotEntity
{
    /**
     * The game ID.
     *
     * @var int $gameId
     */

    /**
     * The possible multipliers.
     *
     * @var array $rules
     */
    public $multipliers = [
        'zebra' => 0,
        'pinguin' => 1,
        'borboleta' => 2,
        'macaco' => 3,
        'coruja' => 4
    ];

    /**
     * The game probabilities.
     *
     * @var array $probabilities
     */
    public $probabilities = [
        'zebra' => [0, 45], // 45% of chance to win
        'pinguin' => [45, 65], // 20% of chance to win
        'borboleta' => [65, 85], // 20% of chance to win
        'macaco' => [85, 95], // 10% of chance to win
        'coruja' => [95, 100] // 5% of chance to win
    ];

    /**
     * The possible labels.
     *
     * @var array $labels
     */
    public $labels = [
        'zebra' => '🦓',
        'pinguin' => '🐧',
        'borboleta' => '🦋',
        'macaco' => '🐒',
        'coruja' => '🦉'
    ];

    /**
     * The possible messages.
     *
     * @var array $messages
     */
    public $messages = [
        'zebra' => 'Vish, deu zebra! Perdeu suas coins! 💸',
        'pinguin' => 'Opa deu pinguin! Você manteve suas coins para o proximo jogo!',
        'borboleta' => 'Borboleta chegando dobrando suas coins! 💵💵',
        'macaco' => 'Macaco chegou pulando e com o rolex no pulso **triplicando suas coins**! 💰💰',
        'coruja' => '💲😎 Você conseguiu a maior recompensa! **Suas coins x4** 😎💲'
    ];

    /**
     * The possible options.
     *
     * @var array $options
     */
    public $options = ['pinguin', 'borboleta', 'macaco', 'coruja'];

    /**
     * The game result.
     *
     * @var string $gameResult
     */
    public $gameResult;

    /**
     * Movements of all players
     * @var JackpotPlayerEntity[] $players
     */
    public $players = [];

    public function __construct(public int $gameId) {}

    /**
     * Get the game ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->gameId;
    }

    /**
     * Get the emoji for a bet.
     *
     * @param string $move
     * @return string
     */
    public function getEmoji(string $bet): string
    {
        return $this->labels[$bet];
    }

    /**
     * Set the player's bet.
     *
     * @param  JackpotPlayerEntity  $player
     * @return false|array
     */
    public function setPlayerBet(JackpotPlayerEntity $player): bool
    {
        foreach ($this->players as $bet) {
            if ($bet->getDiscordId() === $player->getDiscordId()) {
                return false;
            }
        }

        $this->players[] = $player;
        return true;
    }

    /**
     * Get the players.
     *
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getPlayerMultiplier($player): string
    {
        $playerBet = $player->getBet();
        $gameMultiplier = $this->multipliers[$player->getBet()];
        return $playerBet * $gameMultiplier;
    }

    public function getGameResult(): string
    {
        return $this->gameResult;
    }

    public function getGameMultiplier(): string
    {
        return $this->multipliers[$this->gameResult];
    }

    /**
     * Set the game result.
     *
     * @return string
     */
    public function setGameResult()
    {
        $result = '';

        // generate a random number between 0 and 99
        $randomNumber = mt_rand(0, 99);

        // loop through the probabilities and check if the random number is less than the probability
        foreach ($this->probabilities as $label => $probability) {
            if ($randomNumber >= $probability[0] && $randomNumber < $probability[1]) {
                $result = $label;
            }
        }

        $this->gameResult = $result;
    }

    public function getGameMessage(): string
    {
        return $this->messages[$this->gameResult];
    }
}
