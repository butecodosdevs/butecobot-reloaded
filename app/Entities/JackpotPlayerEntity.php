<?php

namespace App\Entities;

class JackpotPlayerEntity
{
    /**
     * The player ID.
     *
     * @var string $discordId
     */

    /**
     * The result of the game.
     *
     * @var null|string
     */
    public ?string $result = null;

    public function __construct(public string $discordId) {}

    /**
     * Get the player ID.
     *
     * @return string
     */
    public function getDiscordId()
    {
        return $this->discordId;
    }

    /**
     * Get the result of the game.
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set the result of the game.
     *
     * @param  string  $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }
}
