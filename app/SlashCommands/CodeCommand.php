<?php

namespace App\SlashCommands;

use Laracord\Commands\SlashCommand;

class CodeCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'codigo';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Exibe o link para o código fonte do bot';

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
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function handle($interaction)
    {
        $interaction->respondWithMessage(
            $this
              ->message()
              ->title('Codigo fonte do bot')
              ->content('Aí manolo o código do bot tá aqui ó, não palpite, commit!')
              ->buttons([
                'GitHub' => 'https://github.com/butecodosdevs/butecobot-reloaded',
            ])
              ->build()
        );
    }
}
