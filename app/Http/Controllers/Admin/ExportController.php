<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use App\Services\PoolStanding;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): InertiaResponse
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->first();

        $days = [];
        if ($competition) {
            $days = GameMatch::where('competition_id', $competition->id)
                ->where('status', 'done')
                ->whereNotNull('ended_at')
                ->selectRaw('DATE(ended_at) as day, COUNT(*) as match_count')
                ->groupByRaw('DATE(ended_at)')
                ->orderByRaw('DATE(ended_at) DESC')
                ->get()
                ->map(fn ($r) => [
                    'date'        => $r->day,
                    'match_count' => (int) $r->match_count,
                ])
                ->values()
                ->all();
        }

        return Inertia::render('Admin/Exports', [
            'competition' => $competition ? [
                'id'   => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
            ] : null,
            'days' => $days,
        ]);
    }

    public function downloadExcel(Request $request): StreamedResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $date = $request->input('date');

        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        $matches = $this->matchesForDay($competition->id, $date);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Résultats');

        // Header bloc
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', $competition->name . ' — Résultats du ' . $this->fmtDate($date));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a1a1a']],
        ]);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('F5F5F0');
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Column headers
        $headers = ['Phase', 'Poule/Tour', 'Joueur A', 'Score A', 'Score B', 'Joueur B', 'Arbitre', 'Table', 'Début', 'Fin', 'Durée'];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        foreach ($headers as $i => $h) {
            $cell = $cols[$i] . '2';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A2:K2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2e7d5e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '444444']]],
        ]);
        $sheet->getStyle('A2:K2')->getFont()->getColor()->setRGB('FFFFFF');

        // Data rows
        $row = 3;
        foreach ($matches as $m) {
            $isEven = ($row % 2 === 0);
            $bg     = $isEven ? 'F9F9F9' : 'FFFFFF';

            $sheet->setCellValue("A{$row}", $m['phase_label']);
            $sheet->setCellValue("B{$row}", $m['round_label']);
            $sheet->setCellValue("C{$row}", $m['player_a']);
            $sheet->setCellValue("D{$row}", $m['score_a'] ?? '');
            $sheet->setCellValue("E{$row}", $m['score_b'] ?? '');
            $sheet->setCellValue("F{$row}", $m['player_b']);
            $sheet->setCellValue("G{$row}", $m['referee'] ?? '');
            $sheet->setCellValue("H{$row}", $m['table'] ?? '');
            $sheet->setCellValue("I{$row}", $m['started_at'] ?? '');
            $sheet->setCellValue("J{$row}", $m['ended_at'] ?? '');
            $sheet->setCellValue("K{$row}", $m['duration'] ?? '');

            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            ]);
            $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Column widths
        $widths = [14, 16, 28, 8, 8, 28, 20, 10, 10, 10, 10];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        // Footer count
        $sheet->setCellValue("A{$row}", count($matches) . ' match(s) — généré le ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('888888');
        $sheet->mergeCells("A{$row}:K{$row}");

        $filename = 'c8p_resultats_' . $date . '.xlsx';

        return ResponseFacade::streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function printPdf(Request $request): HttpResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $date = $request->input('date');

        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        $matches  = $this->matchesForDay($competition->id, $date);
        $dateLabel = $this->fmtDate($date);

        return response()->view('admin.print.day_results', compact('competition', 'matches', 'date', 'dateLabel'));
    }

    public function competitionPdf(): HttpResponse
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        $pools = Pool::where('competition_id', $competition->id)
            ->orderBy('position')
            ->get();

        $poolData = $pools->map(function (Pool $pool) use ($competition) {
            $standings = PoolStanding::compute($pool)->map(fn ($r) => [
                'name'      => $r['player'] ? ($r['player']->first_name . ' ' . $r['player']->last_name) : '—',
                'pool_slot' => $r['pool_slot'],
                'v'         => $r['v'],
                'w'         => $r['w'],
                'l'         => $r['l'],
                'diff'      => $r['diff'],
                'rank'      => $r['rank'],
            ])->values()->all();

            $matches = GameMatch::where('pool_id', $pool->id)
                ->where('phase', 'pool')
                ->with(['playerA', 'playerB', 'table', 'referee'])
                ->orderBy('scheduled_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($m) => [
                    'player_a'   => $m->playerA ? ($m->playerA->first_name . ' ' . $m->playerA->last_name) : '—',
                    'player_b'   => $m->playerB ? ($m->playerB->first_name . ' ' . $m->playerB->last_name) : '—',
                    'score_a'    => $m->score_a,
                    'score_b'    => $m->score_b,
                    'status'     => $m->status,
                    'table'      => $m->table?->name,
                    'referee'    => $m->referee?->name,
                    'started_at' => $m->started_at?->format('H:i'),
                    'ended_at'   => $m->ended_at?->format('H:i'),
                    'winner'     => $m->status === 'done'
                        ? ($m->score_a > $m->score_b ? 'a' : 'b')
                        : null,
                ])->all();

            $played   = collect($matches)->where('status', 'done')->count();
            $total    = count($matches);

            return [
                'name'       => $pool->name,
                'standings'  => $standings,
                'matches'    => $matches,
                'played'     => $played,
                'total'      => $total,
            ];
        })->all();

        return response()->view('admin.print.competition', compact('competition', 'poolData'));
    }

    // -------------------------------------------------------------------------

    private function matchesForDay(int $competitionId, string $date): array
    {
        return GameMatch::where('competition_id', $competitionId)
            ->where('status', 'done')
            ->whereRaw('DATE(ended_at) = ?', [$date])
            ->with(['playerA', 'playerB', 'pool', 'table', 'referee'])
            ->orderBy('ended_at')
            ->get()
            ->map(function (GameMatch $m) {
                $dur = $m->duration_seconds
                    ? floor($m->duration_seconds / 60) . 'min'
                    : null;

                return [
                    'id'          => $m->id,
                    'phase'       => $m->phase,
                    'phase_label' => $m->phase === 'pool' ? 'Poule' : 'Phase finale',
                    'round'       => $m->round,
                    'round_label' => $m->pool?->name ?? ucfirst((string) $m->round),
                    'player_a'    => $m->playerA
                        ? $m->playerA->first_name . ' ' . $m->playerA->last_name
                        : '—',
                    'player_b'    => $m->playerB
                        ? $m->playerB->first_name . ' ' . $m->playerB->last_name
                        : '—',
                    'score_a'     => $m->score_a,
                    'score_b'     => $m->score_b,
                    'referee'     => $m->referee?->name,
                    'table'       => $m->table?->name,
                    'started_at'  => $m->started_at?->format('H:i'),
                    'ended_at'    => $m->ended_at?->format('H:i'),
                    'duration'    => $dur,
                    'winner'      => $m->score_a !== null && $m->score_b !== null
                        ? ($m->score_a > $m->score_b ? 'a' : 'b')
                        : null,
                ];
            })
            ->all();
    }

    private function fmtDate(string $date): string
    {
        $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        $parts  = explode('-', $date);
        $day    = (int) $parts[2];
        $month  = $months[(int) $parts[1] - 1];
        return $day . ' ' . $month . ' ' . $parts[0];
    }
}
