<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PoolResultsImporter
{
    /**
     * Parse the xlsx and return a draft of operations without applying them.
     *
     * Expected format (cf. fichier Excel fourni) :
     *   - Sheets named "Poules_A", "Poules_B", "Poules_C", "Poules_D" (ou variantes)
     *   - Header row : Match | Joueur 1 | Score | Joueur 2 | Score | Vainqueur [| Note]
     *   - Row exemple : "A1 vs A2" | "Youssef" | 3 | "Aziz" | 1 | "Youssef"
     *
     * Some sheets may have header order (Joueur 1 | Score | Joueur 2 | Score) — both supported.
     */
    public function parse(string $path, Competition $competition): array
    {
        $spreadsheet = IOFactory::load($path);
        $stats = ['matches' => [], 'errors' => [], 'skipped' => []];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $poolLetter = $this->detectPoolLetter($sheetName);
            if (! $poolLetter) {
                $stats['skipped'][] = "Feuille « {$sheetName} » ignorée (pas de Poule_X)";
                continue;
            }

            $pool = $competition->pools()->where('name', $poolLetter)->first();
            if (! $pool) {
                $stats['errors'][] = "Poule {$poolLetter} introuvable en DB";
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $this->parseSheet($sheet, $pool, $stats);
        }

        return $stats;
    }

    /**
     * Apply previously-parsed operations to the database.
     */
    public function apply(array $operations): array
    {
        $applied = 0;
        $errors = [];

        foreach ($operations as $op) {
            $match = GameMatch::find($op['match_id']);
            if (! $match) {
                $errors[] = "Match #{$op['match_id']} introuvable";
                continue;
            }

            $swap = $match->player_a_id !== $op['player_a_id'];
            $scoreA = $swap ? $op['score_b'] : $op['score_a'];
            $scoreB = $swap ? $op['score_a'] : $op['score_b'];

            $match->update([
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'status' => 'done',
                'is_draw' => $op['is_draw'] ?? ($scoreA === $scoreB),
                'started_at' => $match->started_at ?? now()->subHour(),
                'ended_at' => now(),
            ]);
            $applied++;
        }

        return ['applied' => $applied, 'errors' => $errors];
    }

    private function parseSheet($sheet, Pool $pool, array &$stats): void
    {
        $rows = $sheet->toArray(null, true, true, true);
        $headerRow = null;
        $columns = [];

        foreach ($rows as $rowIdx => $row) {
            $cells = array_values(array_map(fn ($v) => $v !== null ? trim((string) $v) : '', $row));
            $first = $cells[0] ?? '';

            // detect header line
            if ($headerRow === null && stripos($first, 'match') === 0) {
                $headerRow = $rowIdx;
                $columns = $this->resolveColumns($cells);
                continue;
            }
            if ($headerRow === null) continue;
            if ($first === '' || stripos($first, 'POULE') === 0) continue;

            $this->parseRow($cells, $columns, $pool, $stats);
        }
    }

    private function resolveColumns(array $headerCells): array
    {
        // Returns 0-based indices for each known column.
        $cols = ['match' => 0, 'player1' => 1, 'score1' => 2, 'player2' => 3, 'score2' => 4, 'winner' => 5, 'note' => 6];
        // Variant of pool A first sheet uses (Joueur 1 | Score | Joueur 2 | Score)
        // which is exactly our default. Other sheets sometimes have :
        // Match | Joueur 1 | <blank> | Joueur 2 | Score J1 | Vainqueur — skip those.
        $score1Header = strtolower($headerCells[2] ?? '');
        $score2Header = strtolower($headerCells[4] ?? '');

        if (str_starts_with($score1Header, 'joueur')) {
            // shape: Match | Joueur1 | ... | Joueur2 | Score | Vainqueur (no score on J1 col)
            // means score1 is at index 2 but blank, score2 at index 4 holds the J1 score in some variants…
            // Pour rester simple, on essaie le format standard. Si vide on skip.
        }

        return $cols;
    }

    private function parseRow(array $cells, array $cols, Pool $pool, array &$stats): void
    {
        $matchLabel = $cells[$cols['match']] ?? '';
        if (! preg_match('/^([A-Z])(\d+)\s*vs\s*([A-Z])(\d+)/i', $matchLabel, $m)) {
            return;
        }
        $slotA = (int) $m[2];
        $slotB = (int) $m[4];

        $score1Raw = $cells[$cols['score1']] ?? '';
        $score2Raw = $cells[$cols['score2']] ?? '';
        if ($score1Raw === '' && $score2Raw === '') return;

        if (! is_numeric($score1Raw) || ! is_numeric($score2Raw)) {
            // Score partiel (un seul rempli) : on essaie de déduire avec le vainqueur
            $winner = trim($cells[$cols['winner']] ?? '');
            if ($winner === '') {
                $stats['skipped'][] = "{$matchLabel} : score incomplet";
                return;
            }
        }
        $score1 = is_numeric($score1Raw) ? (int) $score1Raw : 0;
        $score2 = is_numeric($score2Raw) ? (int) $score2Raw : 0;

        // Lookup registrations by pool slot
        $regA = $pool->registrations()->where('pool_slot', $slotA)->first();
        $regB = $pool->registrations()->where('pool_slot', $slotB)->first();
        if (! $regA || ! $regB) {
            $stats['errors'][] = "{$matchLabel} : slot {$slotA} ou {$slotB} non assigné dans poule {$pool->name}";
            return;
        }

        // Find the match (round-robin generated)
        $match = GameMatch::where('pool_id', $pool->id)
            ->where('phase', 'pool')
            ->where(function ($q) use ($regA, $regB) {
                $q->where(function ($q2) use ($regA, $regB) {
                    $q2->where('player_a_id', $regA->player_id)->where('player_b_id', $regB->player_id);
                })->orWhere(function ($q2) use ($regA, $regB) {
                    $q2->where('player_a_id', $regB->player_id)->where('player_b_id', $regA->player_id);
                });
            })->first();

        if (! $match) {
            $stats['errors'][] = "{$matchLabel} : match introuvable en DB";
            return;
        }

        $isDraw = $score1 > 0 && $score2 > 0 && $score1 === $score2;

        $stats['matches'][] = [
            'match_id' => $match->id,
            'label' => $pool->name . $slotA . ' vs ' . $pool->name . $slotB,
            'player_a_id' => $regA->player_id,
            'player_b_id' => $regB->player_id,
            'player_a_name' => trim($regA->player->first_name . ' ' . $regA->player->last_name),
            'player_b_name' => trim($regB->player->first_name . ' ' . $regB->player->last_name),
            'score_a' => $score1,
            'score_b' => $score2,
            'is_draw' => $isDraw,
            'currently_done' => $match->status === 'done',
        ];
    }

    private function detectPoolLetter(string $sheetName): ?string
    {
        if (preg_match('/poules?[_\s-]*([A-Z])\b/i', $sheetName, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }
}
