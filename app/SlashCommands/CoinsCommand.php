<?php

namespace App\SlashCommands;

use Laracord\Commands\SlashCommand;
use App\Helpers\RedisHelper;
use App\Repositories\UserRepository;

class CoinsCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'coins';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Consulta extrato das coins';

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
        $interaction->acknowledgeWithResponse(true)->then(function () use ($interaction) {
            $loop = $this->discord->getLoop();
            $loop->addTimer(0.1, function () use ($interaction) {
                if (
                    !RedisHelper::cooldown(
                        'cooldown:generic:coins:' . $interaction->member->user['id'],
                        config('butecobot.commands.cooldown'),
                        config('butecobot.commands.threshold')
                    )
                ) {
                    $interaction->sendFollowUpMessage(
                        $this->message('Não vai brotar dinheiro do nada! Aguarde 1 min para ver seu extrato!')
                        ->title('Suas coins')
                        ->color('#FF0000')
                        ->thumbnail(config('images.steve_no'))
                        ->build(),
                        true
                    );
                    return;
                }

                $userRepository = new UserRepository;
                $discordId = $interaction->member->user['id'];
                $user = $userRepository->getByDiscordId($discordId);

                if (!$user) {
                    if ($userRepository->registerAndGiveInitialCoins(
                        $interaction->member->user['id'],
                        $interaction->member->user['username'],
                        $interaction->member->user->global_name,
                        $interaction->member->user->avatar,
                        $interaction->member->joined_at->format('Y-m-d H:i:s')
                    )) {
                        $interaction->sendFollowUpMessage(
                            $this->message('Você recebeu **100** coins iniciais! Aposte sabiamente :man_mage:')
                            ->title('Bem vindo')
                            ->color('#F5D920')
                            ->thumbnail(config('images.one_coin'))
                            ->build(),
                            true
                        );

                        return;
                    }
                }

                // Check if is registered user but didn't receive initial coins
                if ($user['received_initial_coins'] === 0) {
                    $userRepository->giveCoins($interaction->member->user['id'], 100, 'Initial');
                    $userRepository->updateReceivedInitialCoins($user['id']);

                    $interaction->sendFollowUpMessage(
                        $this->message('Você recebeu **100** coins iniciais! Aposte sabiamente :man_mage:')
                        ->title('Bem vindo')
                        ->color('#F5D920')
                        ->thumbnail(config('images.one_coin'))
                        ->build(),
                        true
                    );
                }

                $coinsQuery = $userRepository->getCurrentCoins($interaction->member->user['id']);
                $currentCoins = $coinsQuery['total_coins'];
                $dailyCoins = 100;
                $message = '';

                // Check if user can receive daily coins
                if ($user && $userRepository->canReceivedDailyCoins($interaction->member->user['id'])) {
                    $currentCoins += $dailyCoins;
                    $userRepository->giveCoins($interaction->member->user['id'], $dailyCoins, 'Daily');

                    $message .= "**+%s diárias**\n";
                    $message = sprintf($message, $dailyCoins);
                }

                $message .= sprintf('**B$ %s** coins', number_format($currentCoins, 2, ',', '.'));
                $image = config('images.one_coin');

                $interaction->sendFollowUpMessage(
                    $this->message($message)
                    ->title('Saldo')
                    ->color($currentCoins === 0 ? '#FF0000' : '#00FF00')
                    ->thumbnail($image)
                    ->build(),
                    true
                );
            });
        });
    }
}
