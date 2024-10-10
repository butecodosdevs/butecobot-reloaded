<?php

namespace App\Entities;

class JokenpoPlayerEntity
{
    /**
     * The player ID.
     *
     * @var string $discordId
     */

    /**
     * The player's move.
     *
     * @var string $move
     */

    /**
     * The result of the game.
     *
     * @var null|string
     */
    public ?string $result = null;

    public function __construct(public string $discordId, public string $move)
    {
    }

    public function getDiscordId() {
        return $this->discordId;
    }

    public function getMove() {
        return $this->move;
    }

    public function setMove($move) {
        $this->move = $move;
    }

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result = $result;
    }
}
