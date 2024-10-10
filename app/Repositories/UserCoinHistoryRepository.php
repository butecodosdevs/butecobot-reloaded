<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserCoinHistory;

class UserCoinHistoryRepository
{
    public function all() : Collection
    {
        return UserCoinHistory::all();
    }

    public function create(
        int $userId,
        float $amount,
        string $type,
        int $entityId = null,
        ?string $description = null
    ) : UserCoinHistory
    {
        return UserCoinHistory::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'entity_id' => $entityId,
            'description' => $description,
        ]);
    }

    public function spendCoins(string $discordId, float $amount, string $type, ?array $description = null) : bool
    {
        $user = User::where('discord_id', $discordId)->first();

        $create = UserCoinHistory::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'description' => json_encode($description),
        ]);

        return $create ? true : false;
    }

    public function listTop10() : array
    {
        return UserCoinHistory::with('user')
            ->selectRaw('sum(amount) as total_coins, user_id')
            ->groupBy('user_id')
            ->orderBy('total_coins', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * do not performs any validation here, so be careful as this method can be used to "steal" coins
     */
    public function transfer(int $fromId, float $amount, int $toId) : bool
    {
        $type = 'Transfer';

        DB::beginTransaction();

        $from = UserCoinHistory::create([
            'user_id' => $fromId,
            'amount' => -$amount,
            'type' => $type,
            'entity_id' => $toId,
        ]);

        $to = UserCoinHistory::create([
            'user_id' => $toId,
            'amount' => $amount,
            'type' => $type,
            'entity_id' => $fromId,
        ]);

        if (!$from || !$to) {
            DB::rollback();
            return false;
        }

        DB::commit();

        return true;
    }

    public function hasAvailableCoins(int $discordUserId, float $amount) : bool
    {
        $coinsHistory = UserCoinHistory::selectRaw('sum(amount) as total_coins')->whereHas('user', function ($query) use ($discordUserId) {
            $query->where('discord_id', $discordUserId);
        })->get();

        return $coinsHistory[0]->total_coins >= $amount;
    }

    public function reachedMaximumAirplanesToday() : bool
    {
        $receivedAirplanes = UserCoinHistory::selectRaw('sum(amount) as total_coins')
            ->where('type', 'Airplane')
            ->whereDate('created_at', DB::raw('CURDATE()'))
            ->get();

        return $receivedAirplanes[0]->total_coins > env('LITTLE_AIRPLANES_MAXIMUM_AMOUNT_DAY');
    }
}
