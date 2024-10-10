<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Illuminate\Support\Facades\Http;
use Laracord\Commands\SlashCommand;
use App\Helpers\RedisHelper;
use App\Repositories\UserRepository;
use App\Repositories\UserCoinHistoryRepository;

class PicassoCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'picasso';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Descreva aquilo que você quer e Picasso irá pinta-lo (la ele).';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'type' => Option::STRING,
            'name' => 'pinte',
            'description' => 'Descreva aquilo que você quer e Picasso irá pinta-lo (la ele).',
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
        $userCoinHistoryRepository = new UserCoinHistoryRepository;
        $prompt = $this->value('pinte');
        $askCost = $originalAskCost = env('PICASSO_COINS_COST');

        if (
            !RedisHelper::cooldown(
                'cooldown:picasso:paint:' . $interaction->member->user->id,
                env('COMMAND_COOLDOWN_TIMER'),
                env('COMMAND_COOLDOWN_THRESHOLD')
            )
        ) {
            $interaction->respondWithMessage(
                $this->message('Não confunda a pica de aço do mestre de obras com a obra de arte do mestre Picasso! Aguarde 1 minuto para fazer outra pergunta!')
                    ->title('Hmmm...')
                    ->image(config('butecobot.images.gonna_press'))
                    ->build(),
                true
            );
            return;
        }

        if (!$userCoinHistoryRepository->hasAvailableCoins($interaction->member->user->id, $askCost)) {
            $interaction->respondWithMessage(
                $this->message(sprintf(
                            "Pelo que vejo aqui sua carteira anda meio vazia. O mestre das artes plásticas não vale meros **%s coins**?",
                            $originalAskCost
                        ))
                        ->title('Arte não é de graça!')
                        ->image(config('butecobot.images.nomoney'))
                        ->build(),
                true
            );
            return;
        }

        $interaction->acknowledgeWithResponse()->then(function () use ($interaction, $userCoinHistoryRepository, $prompt, $askCost) {
            $art = $this->requestArt($prompt);

            if (isset($art['error'])) {
                $interaction->updateOriginalResponse(
                    $this->message('Deu ruim na arte, tenta de novo aí, mas descreve melhor o que você quer! Ah, o texto tem que ser em inglês e não me venha pedir para desenhar putaria que eu não sou o Picasso do pornô!')
                        ->title('Erro, um erro terrível!')
                        ->build()
                );
                return;
            }

            $userCoinHistoryRepository->spendCoins(
                $interaction->member->user->id,
                -$askCost,
                'Master',
                [ 'description' => $prompt ]
            );

            $interaction->updateOriginalResponse(
                $this->message(sprintf("**Me pediram isso:** %s\n\n**Custo:** %s coins", $prompt, $askCost))
                    ->title('Contemple minha arte!')
                    ->color('#1D80C3')
                    ->image($art['data'][0]['url'])
                    ->build()
            );
        });

        return;
    }

    private function requestArt(string $prompt)
    {
        $response = Http::
                        withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                        ])
                        ->withBody(json_encode([
                            "model" => env('PICASSO_IMAGE_GENERATION_MODEL'),
                            "prompt" => $prompt,
                            "n" => (int) env('PICASSO_IMAGE_QTY'),
                        ]))
                        ->post('https://api.openai.com/v1/images/generations');

        return $response->json();
    }
}
