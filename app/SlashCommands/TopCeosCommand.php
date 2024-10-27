<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Laracord\Commands\SlashCommand;
use App\Helpers\StringHelper;
use App\Repositories\UserCoinHistoryRepository;

class TopCeosCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'top';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Mostra os TOPs de cada categoria.';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'name' => 'patroes',
            'description' => 'Lista dos mais ricos do buteco',
            'type' => Option::SUB_COMMAND,
        ],
    ];

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
        $userCoinHistoryRepository = new UserCoinHistoryRepository;
        $top10list = $userCoinHistoryRepository->listTop10();
        $topBettersImage = config('butecobot.images.top_ceos_rectangular');
        $users = '';
        $acc = '';

        foreach ($top10list as $key => $bet) {
            $username = substr($bet['user']['nickname'] ?? $bet['user']['username'], 0, 25);
            $users .= match ($key) {
                0 => sprintf(":first_place: %s\n", $username),
                1 => sprintf(":second_place: %s\n", $username),
                2 => sprintf(":third_place: %s\n", $username),
                default => sprintf(":medal: %s\n", $username),
            };
            $acc .= sprintf("C$ %s \n", StringHelper::formatMoney($bet['total_coins']));
        }

        $interaction->respondWithMessage(
            $this->message()
                ->authorName('')
                ->title('TOP 10 PATRÕES')
                ->color('#F5D920')
                ->image($topBettersImage)
                ->fields([
                    'Usuário' => $users ,
                    'Acumulado' => $acc ,
                ])
                ->build(),
                false
        );
    }
}
