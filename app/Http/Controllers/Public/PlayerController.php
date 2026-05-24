<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function show(Player $player): Response
    {
        $matches = GameMatch::with(['playerA', 'playerB', 'competition'])
            ->where(function ($q) use ($player) {
                $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id);
            })
            ->where('status', 'done')
            ->orderByDesc('ended_at')
            ->limit(20)
            ->get();

        $palmares = [
            ['year' => '2025', 'title' => 'Coupe du Gabon', 'rank' => '1ᵉʳ'],
            ['year' => '2024', 'title' => 'Coupe du Gabon', 'rank' => '1ᵉʳ'],
            ['year' => '2024', 'title' => 'Open du Cadre', 'rank' => '1ᵉʳ'],
            ['year' => '2024', 'title' => 'Akanda Masters', 'rank' => '2ᵉ'],
            ['year' => '2023', 'title' => "Open d'Owendo", 'rank' => '3ᵉ'],
            ['year' => '2023', 'title' => 'Coupe FGB Jeunes', 'rank' => '1ᵉʳ'],
        ];

        return Inertia::render('Public/Player', [
            'player' => $player->load('club'),
            'matches' => $matches,
            'palmares' => $palmares,
            'form' => [6, 5, 7, 7, 4, 7, 7, 3, 7, 6, 7, 7],
        ]);
    }
}
