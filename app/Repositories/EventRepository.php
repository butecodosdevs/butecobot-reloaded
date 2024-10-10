<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laracord\Commands\SlashCommand;
use App\Models\Event;
use App\Models\EventChoice;

class EventRepository
{
    public const OPEN = 1;
    public const CLOSED = 2;
    public const CANCELED = 3;
    public const PAID = 4;
    public const DRAW = 5;

    public const LABEL = [
        self::OPEN => 'Aberto',
        self::CLOSED => 'Fechado',
        self::CANCELED => 'Cancelado',
        self::PAID => 'Pago',
        self::DRAW => 'Empate',
    ];

    public const LABEL_LONG = [
        self::OPEN => 'Aberto para apostas',
        self::CLOSED => 'Fechado para apostas',
        self::CANCELED => 'Cancelado',
        self::PAID => 'Apostas pagas',
        self::DRAW => 'Empate, não houve vencedor',
    ];

    private EventBetRepository $eventBetRepository;
    private EventChoiceRepository $eventChoiceRepository;
    private UserRepository $userRepository;
    private UserCoinHistoryRepository $userCoinHistoryRepository;
    private int $eventExtraLuckyChance;

    public function __construct(private ?SlashCommand $slashCommand = null)
    {
        $this->userRepository = new UserRepository;
        $this->userCoinHistoryRepository = new UserCoinHistoryRepository;
        $this->eventBetRepository = new EventBetRepository;
        $this->eventExtraLuckyChance = env('EVENT_EXTRA_LUCKY_CHANCE') * 100;
        $this->eventChoiceRepository = new EventChoiceRepository;
    }

    public function all(): Collection
    {
        return Event::all();
    }

    public function getEventById(int $eventId): ?Event
    {
        return Event::with('choices', 'bets', 'created_by', 'winner_choice')->find($eventId);
    }

    public function create(string $eventName, string $optionA, string $optionB, string $discordId): bool|Event
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->getByDiscordId($discordId);

            $eventData = [
                'name' => $eventName,
                'status' => self::OPEN,
                'created_by' => $user['id'],
            ];

            $newEvent = Event::create($eventData);
            $newEvent->choices()->createMany([
                [
                    'choice' => 'A',
                    'description' => $optionA,
                ],
                [
                    'choice' => 'B',
                    'description' => $optionB,
                ],
            ]);

            DB::commit();

            return $newEvent;
        } catch (\Exception $e) {
            DB::rollback();
            $this->slashCommand->console()->log($e->getMessage());
            return false;
        }
    }

    public function closeEvent(int $eventId): bool
    {
        $event = Event::find($eventId);
        $event->status = self::CLOSED;
        return $event->save();
    }

    public function finishEvent(int $eventId): bool
    {
        $event = Event::find($eventId);
        $event->status = self::PAID;
        return $event->save();
    }

    public function listEventsChoicesByStatus(array $status): Collection
    {
        return Event::with('choices')->where('status', $status)->get();
    }

    public function getEventDataById(int $eventId): Collection
    {
        return Event::with('choices')->find($eventId)->get();
    }

    public function listEventsOpen(): Collection
    {
        return $this->listEventsChoicesByStatus(['status_open' => self::OPEN]);
    }

    public function listEventsClosed(): Collection
    {
        return $this->listEventsChoicesByStatus(['status_closed' => self::CLOSED]);
    }

    public function listEventById(int $eventId): Collection
    {
        return $this->getEventDataById($eventId);
    }

    public function updateEventWithWinner(int $choiceId, int $eventId): bool
    {
        $event = Event::find($eventId);
        $event->status = self::PAID;
        $event->winner_choice_id = $choiceId;
        return $event->save();
    }

    private function smoothProbability($amount)
    {
        $base = 10;
        return log($amount + 1, $base);
    }

    public function calculateOdds(int $eventId): array
    {
        $bets = $this->eventBetRepository->getBetsByEventId($eventId);

        $totalBetsArrayBase = [
            'total' => 0,
            'count' => 0
        ];
        $totalBetsA = array_reduce($bets, function ($acc, $item) {
            $acc['total'] += $item['event_choice']['choice'] === 'A' ? $this->smoothProbability($item['amount']) : 0;
            $acc['count'] += 1;

            return $acc;
        }, $totalBetsArrayBase);
        $totalBetsB = array_reduce($bets, function ($acc, $item) {
            $acc['total'] += $item['event_choice']['choice'] === 'B' ? $this->smoothProbability($item['amount']) : 0;
            $acc['count'] += 1;

            return $acc;
        }, $totalBetsArrayBase);

        $oddsA = $totalBetsA['total'] !== 0 ? ($totalBetsB['total'] / $totalBetsA['total']) + 1 : 1;
        $oddsB = $totalBetsB['total'] !== 0 ? ($totalBetsA['total'] / $totalBetsB['total']) + 1 : 1;

        return [
            'odds_a' => $oddsA,
            'odds_b' => $oddsB,
            'total_bets_a' => $totalBetsA['total'],
            'total_bets_b' => $totalBetsB['total'],
        ];
    }

    public function drawEvent(int $eventId): bool
    {
        try {
            DB::beginTransaction();

            $bets = $this->eventBetRepository->getBetsByEventId($eventId);

            foreach ($bets as $bet) {
                $rollbackBet = $this->userCoinHistoryRepository->create(
                    $bet['user_id'],
                    $bet['amount'],
                    'EventBet',
                    $eventId,
                    json_encode([
                        'status' => 'Draw'
                    ])
                );

                if (!$rollbackBet) {
                    throw new \Exception('Error rolling back bet');
                }
            }

            $updateEvent = Event::find($eventId);
            $updateEvent->status = self::DRAW;
            $updateEvent->save();

            if (!$updateEvent) {
                throw new \Exception('Error updating event');
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            return false;
        }
    }

    public function payoutEvent(int $eventId, string $winnerChoiceLabel): array
    {
        $winners = [];
        $event = Event::with('choices', 'bets', 'winner_choice')->find($eventId);
        $choice = $event->choices->where('choice', $winnerChoiceLabel)->first();
        $odds = $this->calculateOdds($eventId);

        DB::beginTransaction();

        try {
            $payoutEvents = [
                'winner_choice' => [
                    'option' => $choice['description'],
                    'description' => $choice['description']
                ],
                'winners' => []
            ];

            foreach ($event['bets'] as $bet) {
                if ($bet['choice_id'] !== $choice['id']) {
                    continue;
                }

                $extra = rand(0, 99) < $this->eventExtraLuckyChance ? $this->extraMultiplier() : 1;
                $ownExtra = $extra > 1;

                $oddMultiplier = $choice['choice'] === 'A' ? round($odds['odds_a'], 2) : round($odds['odds_b'], 2);
                $betPayout = $bet['amount'] * $oddMultiplier;
                $betPayoutFinal = round($betPayout * $extra, 2);

                $this->userCoinHistoryRepository->create(
                    $bet['user_id'],
                    $betPayoutFinal,
                    'EventBet',
                    $eventId,
                    json_encode([
                        'betted' => $bet['amount'],
                        'choice' => $bet['event_choice']['choice'],
                        'odds' => $choice['choice'] === 'A' ? round($odds['odds_a'], 2) : round($odds['odds_b'], 2),
                        'extraLucky' => $ownExtra ? sprintf('Extra Lucky: %s', $extra) : null
                    ])
                );

                $payoutEvents['winners'][] = [
                    'discord_id' => $bet['user']['discord_id'],
                    'username' => $bet['user']['username'],
                    'choice' => $bet['event_choice']['choice'],
                    'earnings' => $betPayoutFinal,
                    'extraLabel' => $ownExtra ? sprintf(' (:rocket: %sx)', $extra) : false,
                ];
            }

            $event->status = self::PAID;
            $event->winner_choice_id = $choice['id'];
            $event->save();

            DB::commit();

            return $payoutEvents;
        } catch (\Exception $e) {
            DB::rollback();
            return [];
        }
    }

    private function extraMultiplier(): float
    {
        return rand(15, 25) / 10;
    }
}
