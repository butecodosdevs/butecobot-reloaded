<?php

namespace App\Entities;

class JokenpoEntity
{
    /**
     * The game ID.
     *
     * @var int $gameId
     */

    /**
     * The rules of the game.
     *
     * @var array $rules
     */
    public $rules = [
        'tesoura' => ['papel', 'lagarto'],
        'papel' => ['pedra', 'spock'],
        'pedra' => ['lagarto', 'tesoura'],
        'lagarto' => ['spock', 'papel'],
        'spock' => ['tesoura', 'pedra'],
    ];

    /**
     * The possible moves.
     *
     * @var array $moves
     */
    public $moves = ['tesoura', 'papel', 'pedra', 'lagarto', 'spock'];

    /**
     * The labels for the moves.
     * @var array $labels
     */
    public $labels = [
        'tesoura' => '✂️',
        'papel' => '📝',
        'pedra' => '🪨',
        'lagarto' => '🦎',
        'spock' => '🖖',
    ];

    /**
     * The bot's move.
     *
     * @var string
     */
    public $botMove;

    /**
    * Movements of all players
    * @var JokenpoPlayerEntity[] $players
    */
    public $players = [];

    public function __construct(public int $gameId)
    {
    }

    /**
     * Get the game ID.
     *
     * @return int
     */
    public function getId() {
        return $this->gameId;
    }

    /**
     * Get the emoji for a move.
     *
     * @param string $move
     * @return string
     */
    function getEmoji(string $move) : string
    {
        return $this->labels[$move];
    }

    /**
     * Set the player's move.
     *
     * @param  JokenpoPlayerEntity  $player
     * @return false|array
     */
    public function setPlayerMove(JokenpoPlayerEntity $player) : bool
    {
        foreach ($this->players as $movement) {
            if ($movement->getDiscordId() === $player->getDiscordId()) {
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
    public function getPlayers() : array
    {
        return $this->players;
    }

    /**
     * Set the bot's move.
     *
     * @return void
     */
    public function setBotMove() : void
    {
        $this->botMove = $this->moves[array_rand($this->moves)];
    }

    /**
     * Get the bot's move.
     *
     * @return string
     */
    public function getBotMove() : string
    {
        return $this->botMove;
    }

    /**
     * Get the outcome of the game.
     *
     * @param  string  $move1
     * @param  string  $move2
     * @return string
     */
    private function getOutcome($move1, $move2) : string
    {
        if ($move1 === $move2) {
            return "empate";
        } elseif (in_array($move2, $this->rules[$move1])) {
            return "ganhou";
        } else {
            return "perdeu";
        }
    }

    /**
     * Evaluate the moves of the players.
     *
     * @return array
     */
    public function evaluateMoves() : array
    {
        $results = [];

        /** @var JokenpoPlayerEntity $player */
        foreach ($this->players as $player) {
            $result = $this->getOutcome($player->getMove(), $this->botMove);
            $player->setResult($result);
            $results[] = $player;
        }

        return $results;
    }
}
