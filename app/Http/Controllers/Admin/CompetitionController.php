<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(): Response
    {
        $competitions = Competition::orderByDesc('starts_on')->withCount(['registrations', 'matches'])->get();

        return Inertia::render('Admin/Competitions/Index', [
            'competitions' => $competitions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Competitions/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'discipline' => ['required', 'string'],
            'format' => ['required', 'string'],
            'structure' => ['nullable', 'in:knockout,pools_knockout,pools_only,round_robin'],
            'player_slots' => ['required', 'integer', 'min:4', 'max:128'],
            'pool_count' => ['integer', 'min:0'],
            'pool_size' => ['integer', 'min:0'],
            'qualifiers_per_pool' => ['integer', 'min:0'],
            'race_to' => ['required', 'integer', 'min:1', 'max:25'],
            'shot_clock' => ['required', 'integer', 'min:5'],
            'alternate_break' => ['boolean'],
            'allow_draw' => ['boolean'],
            'enable_warnings' => ['boolean'],
            'push_out' => ['boolean'],
            'frame_pause' => ['integer'],
            'tiebreak_race' => ['integer'],
            'venue' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'entry_fee' => ['integer'],
            'deposit' => ['integer'],
            'prize_pool' => ['integer'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . now()->year;
        $data['status'] = 'draft';

        $comp = Competition::create($data);

        return redirect()->route('admin.competitions.show', $comp);
    }

    public function show(Competition $competition): Response
    {
        return Inertia::render('Admin/Competitions/Show', [
            'competition' => $competition->load(['tables', 'registrations.player.club']),
        ]);
    }
}
