<?php

namespace App;

use Illuminate\Support\Facades\Route;
use Discord\Parts\User\Activity;
use Laracord\Laracord;

class Bot extends Laracord
{
    /**
     * The HTTP routes.
     */
    public function routes(): void
    {
        Route::middleware('web')->group(function () {
            // Route::get('/', fn () => 'Hello world!');
        });

        Route::middleware('api')->group(function () {
            // Route::get('/commands', fn () => collect($this->registeredCommands)->map(fn ($command) => [
            //     'signature' => $command->getSignature(),
            //     'description' => $command->getDescription(),
            // ]));
        });
    }

    public function afterBoot(): void
    {
        $activity = $this->discord()->factory(Activity::class, [
            'type' => Activity::TYPE_PLAYING,
            'name' => 'Fiado só amanhã!',
        ]);

        $this->discord()->updatePresence($activity);
    }
}
