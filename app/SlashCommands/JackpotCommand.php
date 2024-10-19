<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use Laracord\Commands\SlashCommand;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Entities\JackpotEntity;
use App\Models\Jackpot;
use App\Models\JackpotPlayer;
use App\SlashCommands\Traits\Jackpot\PlayJackpot;

class JackpotCommand extends SlashCommand
{
    use PlayJackpot;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'jackpot';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Tente sua sorte no Jackpot!';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Indicates whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * The game instance.
     *
     * @var JokenpoEntity[]
     */
    protected array $games = [];

    /**
     * Game timers counters.
     *
     * @var array
     */
    protected array $counters = [];

    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function handle($interaction)
    {
        $game = new JackpotEntity(1);

        $this->setGame($game);
        $this->setCounter($game, env('JACKPOT_TIMER', 30));

        $interaction->respondWithMessage(
            $this->buildGameMessage($game)
        )->then(fn() => $this->startCounter($interaction, $game));
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'action:{gameId}' => fn(Interaction $interaction, int $gameId) => $this->playJackpot($interaction, $gameId),
        ];
    }
}
