<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Laracord\Commands\SlashCommand;
use Illuminate\Support\Facades\Http;
use App\Helpers\RedisHelper;
use App\Repositories\UserCoinHistoryRepository;

class MasterCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'mestre';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Pergunte ao mestre.';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'type' => Option::STRING,
            'name' => 'pergunta',
            'description' => 'Pergunta para o mestre',
            'required' => true,
        ],
        [
            'type' => Option::INTEGER,
            'name' => 'boost',
            'description' => 'Boost de coins',
            'required' => false,
            'choices' => [
                [
                    'name' => '+50 coins (+500 tokens)',
                    'value' => '50',
                ],
                [
                    'name' => '+100 coins (+1000 tokens)',
                    'value' => '100',
                ],
                [
                    'name' => '+150 coins (+1500 tokens)',
                    'value' => '150',
                ]
            ]
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
        $questionInput = $this->value('pergunta');
        $boostInput = $this->value('boost');
        $askCost = $originalAskCost = env('MASTER_COINS_COST');
        $questionLimit = $this->value('boost') ? env('MASTER_QUESTION_MAX_SIZE') * 20 : env('MASTER_QUESTION_MAX_SIZE');

        if (
            !RedisHelper::cooldown(
                'cooldown:master:ask:' . $interaction->member->user->id,
                env('COMMAND_COOLDOWN_TIMER'),
                env('COMMAND_COOLDOWN_THRESHOLD')
            )
        ) {
            $this->discord->getLogger()->info(sprintf(
                'Master cooldown reached for user: %s (%s) - Discord ID: %s',
                $interaction->member->user->global_name,
                $interaction->member->user->username,
                $interaction->member->user->id
            ));
            $interaction->respondWithMessage(
                $this->message('Muito mais devagar aí cnpjoto, calabreso! Aguarde 1 minuto para fazer outra pergunta!')
                    ->title('Ow apressadinho!')
                    ->image(config('butecobot.images.gonna_press'))
                    ->build(),
                true
            );
            return;
        }

        $askCost = $boostInput ? $askCost + $boostInput : $askCost;

        if (!$userCoinHistoryRepository->hasAvailableCoins($interaction->member->user->id, $askCost)) {
            $interaction->respondWithMessage(
                $this->message(sprintf(
                        "Tu não tem dinheiro pra pagar o mestre, ja pegou as coins diárias? Roletinha? Pede um aviãozinho!!\n\nO mestre cobra singelos **%s coins** por pergunta! %s",
                        $originalAskCost,
                        $this->value('boost') ? sprintf("\n\nAlém do **Boost** de  **%s** coins que você pediu", $this->value('boost')) : ''
                    ))
                    ->title('MESTRE NÃO É OTÁRIO')
                    ->image(config('butecobot.images.nomoney'))
                    ->build(),
                true
            );
            return;
        }

        if (strlen($questionInput) > $questionLimit) {
            $interaction->respondWithMessage(
                $this->message('Tu é escritor por acaso? Escreve menos na moralzinha!')
                    ->title('MESTRE FICOU PUTO')
                    ->image(config('butecobot.images.typer'))
                    ->build(),
                true
            );
            return;
        }

        $answerPrivately = $this->value('boost') ? true : false;
        $interaction->acknowledgeWithResponse($answerPrivately)->then(
            function () use ($interaction, $userCoinHistoryRepository, $questionInput, $boostInput, $askCost) {
                $data = $this->makeQuestion(
                    $questionInput,
                    $boostInput
                        ? env('MASTER_QUESTION_RESPONSE_TOKENS') + ($boostInput * 10)
                        : env('MASTER_QUESTION_RESPONSE_TOKENS')
                );

                $message = sprintf(
                    "**Pergunta:**\n%s\n\n**Resposta:**\n%s%s\n\n**Custo:** %s coins",
                    $questionInput,
                    $data['choices'][0]['message']['content'],
                    $data['choices'][0]['finish_reason'] === 'length' ? '...' : '',
                    $askCost
                );

                $userCoinHistoryRepository->spendCoins(
                    $interaction->member->user->id,
                    -$askCost,
                    'Master',
                    [
                        'question' => $questionInput,
                        'boost' => $boostInput
                    ]
                );

                if ($boostInput) {
                    $interaction->user->sendMessage(
                        $this->message($message)
                            ->title('SABEDORIA DO MESTRE')
                            ->color('#1D80C3')
                            ->build()
                    );
                    $interaction->updateOriginalResponse(
                        $this->message("Respostas com **boost** vão para DM. Cheque sua DM, e a resposta estará lá!")
                            ->title('SABEDORIA DO MESTRE')
                            ->color('#1D80C3')
                            ->build()
                    );
                    return;
                }

                $interaction->updateOriginalResponse(
                    $this->message($message)
                        ->title('SABEDORIA DO MESTRE')
                        ->color('#1D80C3')
                        ->build()
                );
            }
        );

        return;
    }

    private function makeQuestion(string $question, int $tokens)
    {
        $messages = [
            [
                "role" => "system",
                "content" => env('MASTER_HUMOR')
            ],
            [
                "role" => "user",
                "content" => $question
            ],
        ];

        $response = Http::
                        withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                        ])
                        ->withBody(json_encode([
                            "model" => env('OPENAI_COMPLETION_MODEL'),
                            "messages" => $messages,
                            "temperature" => 1.2,
                            "top_p" => 1,
                            "n" => 1,
                            "stream" => false,
                            "max_tokens" => $tokens,
                            "presence_penalty" => 0,
                            "frequency_penalty" => 0
                        ]))
                        ->post('https://api.openai.com/v1/chat/completions');

        return $response->json();
    }
}
