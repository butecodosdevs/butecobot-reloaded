<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Laracord\Commands\SlashCommand;
use App\Repositories\UserRepository;
use App\Repositories\UserCoinHistoryRepository;

class TransferCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'transferir';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Transfere coins para outro usuário';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'type' => Option::USER,
            'name' => 'usuario',
            'description' => 'Nome do usuário',
            'required' => true,
        ],
        [
            'type' => Option::NUMBER,
            'name' => 'coins',
            'description' => 'Quantidade de coins para transferir',
            'required' => true,
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
        $userRepository = new UserRepository;
        $userCoinHistoryRepository = new UserCoinHistoryRepository;

        $fromDiscordId = $interaction->member->user->id;
        $toDiscordId = $interaction->data->options['usuario']->value;
        $coins = $interaction->data->options['coins']->value;
        $fromUser = $userRepository->getByDiscordId($fromDiscordId);
        $toUser = $userRepository->getByDiscordId($toDiscordId);

        $daysActiveAccount = (new \DateTime())->diff(new \DateTime($fromUser['created_at']))->days;

        if ($coins <= 0 || $coins > env('TRANSFER_LIMIT')) {
            $interaction->respondWithMessage(
                $this->message(
                    sprintf(
                        "Quantidade inválida. Valor deve ser entre 1 e %s coins",
                        number_format(env('TRANSFER_LIMIT'), 2, ',', '.')
                    )
                )
                ->title('Transferir')
                ->authorName('')
                ->thumbnail('')
                ->build()
                ,
                true
            );

            return;
        }

        if ($daysActiveAccount <= 15) {
            $interaction->respondWithMessage(
                $this->message(sprintf("Sua conta no %s precisa ter mais de 15 dias para transferir coins", env('APP_NAME')))
                    ->title('Transferir')
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                true
            );

            return;
        }

        if (!$userRepository->hasAvailableCoins($fromDiscordId, $coins)) {
            $interaction->respondWithMessage(
                $this->message('Trasferência não realizada, saldo insuficiente')
                    ->title('Transferir')
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                true
            );

            return;
        }

        if ($fromDiscordId === $toDiscordId) {
            $userCoinHistoryRepository->create($fromUser['id'], -$coins, 'Troll');

            $message = sprintf("Nossa mas você é engraçado mesmo né. Por ter sido troll por transferir para você mesmo, acabou de perder **%s** coins pela zoeira!\n\nInclusive tá todo mundo vendo essa merda aí que tu ta fazendo!\n\nHA! HA! HA! ENGRAÇADÃO! 👹👹👹", -$coins);
            $image = config('butecobot.images.sefodeu');

            $interaction->respondWithMessage(
                $this->message($message)
                    ->title('Transferir')
                    ->color('#44f520')
                    ->image($image)
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                false
            );
            return;
        }

        if (!$userRepository->userExistByDiscordId($fromDiscordId)) {
            $interaction->respondWithMessage(
                $this->message('Trasferência não realizada, remetente não encontrado')
                    ->title('Transferir')
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                true
            );

            return;
        }

        if (!$userRepository->userExistByDiscordId($toDiscordId)) {
            $interaction->respondWithMessage(
                $this->message('Trasferência não realizada, destinatário não encontrado')
                    ->title('Transferir')
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                true
            );

            return;
        }

        if (!$userCoinHistoryRepository->transfer($fromUser['id'], $coins, $toUser['id'])) {
            $interaction->respondWithMessage(
                $this->message('Trasferência não realizada, erro ao transferir coins')
                    ->title('Transferir')
                    ->authorName('')
                    ->thumbnail('')
                    ->build(),
                true
            );

            return;
        }

        $interaction->respondWithMessage(
            $this->message(
                sprintf(
                    "Transferência realizada com sucesso!\n\nValor: **%s** coins\nDestinatário: <@%s>",
                    number_format($coins, 2, ',', '.'),
                    $toDiscordId
                )
            )
            ->title('Transferir')
            ->authorName('')
            ->thumbnail('')
            ->build(),
            true
        );
    }
}
