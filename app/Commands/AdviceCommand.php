<?php

namespace App\Commands;

use Discord\Voice\VoiceClient;
use Illuminate\Support\Facades\Storage;
use Laracord\Commands\Command;

class AdviceCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'conselho';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Precisa de ajuda e de um conselho?';

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        $audio = Storage::path('sounds/fabioAkita.mp3');
        $movie = Storage::path('movies/FlavioPuto.mp4');
        $channel = $this->discord->getChannel($message->channel_id);
        $voice = $this->discord->getVoiceClient($channel->guild_id);

        if ($channel->isVoiceBased()) {
            if ($voice) {
                echo 'joinings';
                $voice->playFile($audio);
            } else {
                echo 'joining';
                $this->discord->joinVoiceChannel($channel)->then(function (VoiceClient $voice) use ($audio) {
                    $voice->playFile($audio);
                });
            }
        }

        return $this
            ->message('Veja esse video')
            ->filePath($movie)
            ->title('Ta pedindo conselho?')
            ->buttons([
                'You Tube' => 'https://www.youtube.com/watch?v=D3L8IOncLkg',
            ])
            ->reply($message);
    }
}
