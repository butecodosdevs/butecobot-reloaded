<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use Laracord\Commands\SlashCommand;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Repositories\UserRepository;
use App\Entities\JokenpoEntity;
use App\Models\Jokenpo;
use App\SlashCommands\Traits\Jokenpo\PlayJokenpo;

class JokenpoCommand extends SlashCommand
{
    use PlayJokenpo;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'jokenpo';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Pedra, papel, tesoura, lagarto, spock... frenético!';

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
        $userRepository = new UserRepository;
        $user = $userRepository->getByDiscordId($interaction->member->user['id']);
        $newGame = Jokenpo::create([
            'created_by' => $user['id'],
        ]);
        Redis::set("jokenpo:game:" . $newGame['id'], serialize($newGame), 'EX', 120);
        $game = new JokenpoEntity($newGame['id']);

        $this->setGame($game);
        $this->setCounter($game, env('JOKENPO_TIMER', 30));

        $channel = $this->discord->getChannel($interaction->channel_id);
        $audio = Storage::path('sounds/jokenpo.mp3');
        $this->bot->getLogger()->info('Audio: ' . $audio);
        $voice = $this->discord->getVoiceClient($channel->guild_id);

        if ($channel->isVoiceBased()) {
            if ($voice) {
                $voice->playFile($audio);
            } else {
                $this->discord->joinVoiceChannel($channel)->then(function (VoiceClient $voice) use ($audio) {
                    $voice->playFile($audio);
                });
            }
        }

        $interaction->respondWithMessage(
            $this->buildGameMessage($game)
        )->then(fn() => $this->startCounter($interaction, $game));
    }

    /**
     * The command interaction routes.
     */
    public function interactions() : array
    {
        return [
            'action:{type}:{gameId}' => fn (Interaction $interaction, string $type, int $gameId) => $this->playJokenpo($interaction, $type, $gameId),
        ];
    }
}
