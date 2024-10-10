<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;
use App\SlashCommands\Traits\Events\AnnounceEvents;
use App\SlashCommands\Traits\Events\BetEvents;
use App\SlashCommands\Traits\Events\CloseEvents;
use App\SlashCommands\Traits\Events\CreateEvents;
use App\SlashCommands\Traits\Events\EndEvents;
use App\SlashCommands\Traits\Events\ListEvents;

class EventsCommand extends SlashCommand
{
    use AnnounceEvents, BetEvents, CreateEvents, CloseEvents, EndEvents, ListEvents;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'eventos';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Gerencia eventos de apostas';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'type' => Option::SUB_COMMAND,
            'name' => 'criar',
            'description' => 'Cria evento',
            'options' => [
                [
                    'type' => Option::STRING,
                    'name' => 'nome',
                    'description' => 'Nome do evento',
                    'required' => true,
                ],
                [
                    'type' => Option::STRING,
                    'name' => 'a',
                    'description' => 'Opção A',
                    'required' => true,
                ],
                [
                    'type' => Option::STRING,
                    'name' => 'b',
                    'description' => 'Opção B',
                    'required' => true,
                ],
            ]
        ],
        [
            'type' => Option::SUB_COMMAND,
            'name' => 'fechar',
            'description' => 'Fecha evento e não recebe mais apostas',
            'options' => [
                [
                    'type' => Option::INTEGER,
                    'name' => 'evento',
                    'description' => 'ID do evento',
                    'required' => true,
                ],
            ]
        ],
        [
            'type' => Option::SUB_COMMAND,
            'name' => 'encerrar',
            'description' => 'Encerra evento e paga as apostas',
            'options' => [
                [
                    'type' => Option::INTEGER,
                    'name' => 'evento',
                    'description' => 'ID do evento',
                    'required' => true,
                ],
                [
                    'type' => Option::STRING,
                    'name' => 'opcao',
                    'description' => 'Opção A ou B.',
                    'required' => true,
                    'choices' => [
                        [
                            'name' => 'A',
                            'value' => 'A'
                        ],
                        [
                            'name' => 'B',
                            'value' => 'B'
                        ],
                        [
                            'name' => 'Empate',
                            'value' => 'Empate'
                        ]
                    ]
                ],
            ]
        ],
        [
            'type' => Option::SUB_COMMAND,
            'name' => 'anunciar',
            'description' => 'Anuncia o evento de forma personalizada',
            'options' => [
                [
                    'type' => Option::INTEGER,
                    'name' => 'evento',
                    'description' => 'ID do evento',
                    'required' => true,
                ],
                [
                    'type' => Option::STRING,
                    'name' => 'banner',
                    'description' => 'Imagem do banner para utilizar ',
                    'required' => true,
                    'choices' => [
                        [
                            'name' => 'UFC',
                            'value' => 'UFC'
                        ],
                        [
                            'name' => 'Genérica',
                            'value' => 'GENERIC'
                        ],
                        [
                            'name' => 'Libertadores',
                            'value' => 'LIBERTADORES'
                        ],
                        [
                            'name' => 'Zoeira',
                            'value' => 'ZOEIRA'
                        ]
                    ]
                ],
            ]
        ],
        [
            'type' => Option::SUB_COMMAND,
            'name' => 'listar',
            'description' => 'Lista eventos criados e pendentes para iniciar',
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
    protected $admin = true;

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Events basically.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Handle the slash command.
     *
     * @param  Interaction  $interaction
     * @return mixed
     */
    public function handle($interaction)
    {
        $command = key($this->value());

        switch ($command) {
            case 'listar':
                $this->listEvents($interaction);
                break;
            case 'criar':
                $this->createEvent($interaction);
                break;
            case 'fechar':
                $this->closeEvent($interaction);
                break;
            case 'encerrar':
                $this->endEvent($interaction);
                break;
            case 'anunciar':
                $this->announceEvent($interaction);
                break;
            default:
                $interaction->respondWithMessage(
                    $this->message('Comando inválido')->build()
                );
        }
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'list:backward' => fn (Interaction $interaction) => $this->listEventsBackward($interaction),
            'list:forward' => fn (Interaction $interaction) => $this->listEventsForward($interaction),
            'betOn:{evento}:{choice}' => fn (Interaction $interaction, int $evento, string $choice) => $this->betEventModal($interaction, $evento, $choice),
        ];
    }
}
