<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BlockService;

class BlockController extends Controller
{
    public function __construct(private BlockService $blockService) {}
    /**
     * toggleBlock - block and unblock 
     */
    public function toggleBlock(User $user)
    {
        return $this->blockService->toggleBlock($user);
    }

    /**
     * Blocklanganlar ro'yhati
     */
    public function blockedList()
    {
        return $this->blockService->blockedList();
    }
}
