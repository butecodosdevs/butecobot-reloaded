<?php

namespace App\Repositories;

use App\Models\Jokenpo;
use App\Repositories\UserRepository;
use App\Repositories\UserCoinHistoryRepository;

class JokenpoRepository
{
    private UserRepository $userRepository;
    private UserCoinHistoryRepository $userCoinHistoryRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository;
        $this->userCoinHistoryRepository = new UserCoinHistoryRepository;
    }
}
