<?php

namespace App\SlashCommands;

use Discord\Voice\VoiceClient;
use Laracord\Commands\SlashCommand;
use Illuminate\Support\Facades\Storage;
use App\Helpers\RedisHelper;
use App\Repositories\UserRepository;
use App\Repositories\UserCoinHistoryRepository;

class LittleAirplanesCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'avioeszinhos';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Joga avioeszinhos de coins para o auditório';

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
    protected $admin = true;

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

        // if (!find_role_array($this->config['admin_role'], 'name', $interaction->member->roles)) {
        //     $interaction->respondWithMessage(
        //         MessageBuilder::new()->setContent('Você não tem permissão para usar este comando!'),
        //         true
        //     );

        //     return;
        // }

        if (
            !RedisHelper::cooldown(
                'cooldown:littleairplanes:fly:' . $interaction->member->user->id,
                env('LITTLE_AIRPLANES_COOLDOWN_TIMER'),
                env('LITTLE_AIRPLANES_COOLDOWN_LIMIT')
            )
        ) {
            $this->discord->getLogger()->info(sprintf(
                'Little Airplanes cooldown reached for user: %s (%s) - Discord ID: %s',
                $interaction->member->user->global_name,
                $interaction->member->user->username,
                $interaction->member->user->id
            ));

            $interaction->respondWithMessage(
                $this->message(sprintf('Aguarde %s minutos para mandar mais aviõeszinhos... ôêê!', env('LITTLE_AIRPLANES_COOLDOWN_TIMER') / 60))
                    ->title('MAH ÔÊÊ!')
                    ->image(config('butecobot.images.gonna_press'))
                    ->build(),
                true
            );

            return;
        }

        if ($userCoinHistoryRepository->reachedMaximumAirplanesToday()) {
            $interaction->respondWithMessage(
                $this->message(sprintf(
                        'Foram **%s coins** em :airplane_small: aviõeszinhos hoje que não dava pra ver o céu oêê! Agora só amanhã rá rá ê hi hi!',
                        env('LITTLE_AIRPLANES_MAXIMUM_AMOUNT_DAY')
                    ))
                    ->title('MAH ÔÊÊ!')
                    ->color('#FF0000')
                    ->image(config('butecobot.images.see_you_tomorrow'))
                    ->build()
            );
            return;
        }

        $members = array_keys($this->discord->getChannel($interaction->channel_id)->members->toArray());

        if (empty($members)) {
            $this->discord->getLogger()->info(sprintf('Little Airplanes no members found'));
            $interaction->respondWithMessage(
                $this->message('Ma, ma, ma, mas tem ninguém nessa sala, não tem como eu jogar meus :airplane_small:aviõeszinhos... ôêê!')
                    ->title('MAH ÔÊÊ!')
                    ->build(),
                true
            );
        }

        $interaction->acknowledgeWithResponse()->then(function () use ($interaction, $members, $userRepository, $userCoinHistoryRepository) {
            $this->discord->getLogger()->info(sprintf('Little Airplanes started for %s members', count($members)));

            $interaction->updateOriginalResponse(
                $this->message('Olha só quero ver, quero ver quem vai pegar os aviõeszinhos... ôêê!')
                    ->title('MAH ÔÊÊ!')
                    ->image(config('butecobot.images.airplanes'))
                    ->build()
            );

            // Little Airplanes Sound
            $channel = $this->discord->getChannel($interaction->channel_id);
            $audio = Storage::path('sounds/avioeszinhos.mp3');
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

            $loop = $this->discord->getLoop();
            $loop->addTimer(6, function () use ($members, $interaction, $userRepository, $userCoinHistoryRepository) {
                $airplanes = [];

                foreach ($members as $member) {
                    $isDeaf = $this->discord->getChannel($interaction->channel_id)->members[$member]->self_deaf;

                    if ($isDeaf) continue;

                    if (mt_rand(0, 99) < env('LITTLE_AIRPLANES_PROBABILITY') * 100) {
                        if (!$userRepository->userExistByDiscordId($member)) continue;

                        $extraValue = mt_rand(0, 99) < env('LITTLE_AIRPLANES_PROBABILITY_BOOSTED') * 100
                            ? env('LITTLE_AIRPLANES_PROBABILITY_VALUE_BOOSTED')
                            : mt_rand(env('LITTLE_AIRPLANES_PROBABILITY_VALUE_MIN'), env('LITTLE_AIRPLANES_PROBABILITY_VALUE_MAX'));

                        $airplanes[] = [
                            'discord_id' => $member,
                            'amount' => $extraValue
                        ];

                        $user = $userRepository->getByDiscordId($member);
                        $userCoinHistoryRepository->create($user['id'], $extraValue, 'Airplane');
                    }
                }

                if (empty($airplanes)) {
                    $this->discord->getLogger()->info(sprintf('Little Airplanes no one won :('));

                    $interaction->updateOriginalResponse(
                        $this->message('Acho que o Roque esqueceu de fazer meus :airplane_small:aviõeszinhos... ôêê!')
                            ->title('MAH ÔÊÊ!')
                            ->image(config('butecobot.images.silvio_thats_ok'))
                            ->build()
                    );
                    return;
                }

                $airports = '';
                $amount = '';

                foreach ($airplanes as $airplane) {
                    $airports .= sprintf("<@%s> \n", $airplane['discord_id']);
                    $amount .= sprintf(
                        "%s %s \n",
                        $airplane['amount'] < env('LITTLE_AIRPLANES_PROBABILITY_VALUE_BOOSTED')
                            ? ':airplane_small:'
                            : ':airplane:',
                        $airplane['amount']
                    );
                }

                $interaction->updateOriginalResponse(
                    $this->message('Os :airplane_small:aviõeszinhos voaram pelo auditório e caíram em cima de:')
                        ->title('MAH ÔÔÊ!')
                        ->color('#8FCE00')
                        ->image(config('butecobot.images.silvio_cheers'))
                        ->fields([
                            'Nome' => $airports,
                            'Valor (C$)' => $amount
                        ])
                        ->build()
                );
            });

            return;
        });
    }
}
