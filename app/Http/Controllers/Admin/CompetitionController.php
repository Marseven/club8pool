<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Pool;
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
        $data = $this->validateData($request);
        $data['slug'] = Str::slug($data['name']) . '-' . now()->timestamp;
        $data['status'] = 'draft';
        $data['format'] = $this->mapFormat($data['structure']);

        $comp = Competition::create($data);

        // Auto-create empty pools when applicable.
        if (in_array($comp->structure, ['pools_knockout', 'pools_only'])) {
            $count = max(1, (int) $comp->pool_count);
            for ($i = 0; $i < $count; $i++) {
                Pool::create([
                    'competition_id' => $comp->id,
                    'name' => chr(ord('A') + $i),
                    'position' => $i,
                    'size' => $comp->pool_size,
                ]);
            }
        }

        return redirect()->route('admin.competitions.show', $comp);
    }

    public function show(Competition $competition): Response
    {
        return Inertia::render('Admin/Competitions/Show', [
            'competition' => $competition->load(['tables', 'pools', 'registrations.player.club']),
        ]);
    }

    public function edit(Competition $competition): Response
    {
        return Inertia::render('Admin/Competitions/Edit', [
            'competition' => $competition,
        ]);
    }

    public function update(Request $request, Competition $competition): RedirectResponse
    {
        $data = $this->validateData($request, $competition);
        $data['format'] = $this->mapFormat($data['structure']);
        $competition->update($data);
        return redirect()->route('admin.competitions.show', $competition)->with('success', 'Compétition mise à jour.');
    }

    private function mapFormat(string $structure): string
    {
        return match ($structure) {
            'knockout' => 'single_elim',
            'pools_knockout', 'pools_only' => 'pools',
            'round_robin' => 'round_robin',
            default => 'single_elim',
        };
    }

    private function validateData(Request $request, ?Competition $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string'],
            'discipline' => ['required', 'in:8-ball,10-ball,snooker,blackball'],
            'format' => ['required', 'string'],
            'structure' => ['required', 'in:knockout,pools_knockout,pools_only,round_robin'],
            'player_slots' => ['required', 'integer', 'min:2', 'max:256'],
            'pool_count' => ['nullable', 'integer', 'min:0', 'max:32'],
            'pool_size' => ['nullable', 'integer', 'min:0', 'max:32'],
            'qualifiers_per_pool' => ['nullable', 'integer', 'min:0', 'max:16'],
            'race_to' => ['required', 'integer', 'min:1', 'max:25'],
            'shot_clock' => ['required', 'integer', 'min:5', 'max:120'],
            'alternate_break' => ['boolean'],
            'allow_draw' => ['boolean'],
            'enable_warnings' => ['boolean'],
            'push_out' => ['boolean'],
            'frame_pause' => ['nullable', 'integer'],
            'tiebreak_race' => ['nullable', 'integer'],
            'venue' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'entry_fee' => ['nullable', 'integer'],
            'deposit' => ['nullable', 'integer'],
            'prize_pool' => ['nullable', 'integer'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
        ]);
    }
}
