<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Résultats — {{ $dateLabel }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #fff; }

  .page-header {
    padding: 14px 20px 10px;
    border-bottom: 2px solid #111;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 12px;
  }
  .page-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.01em; }
  .page-header .meta { font-size: 10px; color: #555; text-align: right; line-height: 1.6; }

  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead th {
    background: #1a1a1a;
    color: #fff;
    font-size: 9px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 6px 8px;
    text-align: left;
    font-weight: 600;
  }
  thead th.center { text-align: center; }
  tbody tr { border-bottom: 1px solid #ddd; }
  tbody tr:nth-child(even) { background: #f7f7f7; }
  tbody td { padding: 6px 8px; vertical-align: middle; }
  tbody td.center { text-align: center; }
  .score-a { font-weight: 700; color: #1b5e38; font-size: 13px; }
  .score-b { font-weight: 700; color: #1b5e38; font-size: 13px; }
  .score-sep { color: #aaa; font-size: 10px; margin: 0 2px; }
  .winner { font-weight: 700; }
  .loser { color: #888; }
  .badge-pool { background: #e8f5e9; color: #1b5e38; padding: 2px 6px; font-size: 8px; font-weight: 700; letter-spacing: 0.1em; border-radius: 2px; }
  .badge-ko { background: #e3f2fd; color: #1565c0; padding: 2px 6px; font-size: 8px; font-weight: 700; letter-spacing: 0.1em; border-radius: 2px; }

  .summary { padding: 8px 0; font-size: 10px; color: #555; border-top: 1px solid #ddd; margin-top: -16px; }
  .no-matches { padding: 40px; text-align: center; color: #888; font-size: 13px; }
  .print-footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #aaa; display: flex; justify-content: space-between; }

  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    @page { margin: 15mm 10mm; }
  }
</style>
</head>
<body>
<div class="page-header">
  <div>
    <div style="font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase; color: #888; margin-bottom: 4px;">
      Club 8 Pool · Résultats journée
    </div>
    <h1>{{ $competition->name }}</h1>
    <div style="font-size: 13px; font-weight: 600; margin-top: 4px;">{{ $dateLabel }}</div>
  </div>
  <div class="meta">
    {{ count($matches) }} match{{ count($matches) > 1 ? 's' : '' }} joué{{ count($matches) > 1 ? 's' : '' }}<br />
    Généré le {{ now()->format('d/m/Y à H:i') }}<br />
    <strong>{{ $competition->venue ?? '' }}</strong>
  </div>
</div>

<button class="no-print"
        onclick="window.print()"
        style="margin: 8px 20px 16px; padding: 8px 18px; background: #111; color: #fff;
               border: none; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 3px;">
  ⊕ Imprimer / Enregistrer PDF
</button>

@if(count($matches) === 0)
  <div class="no-matches">Aucun match terminé pour cette journée.</div>
@else
  <table>
    <thead>
      <tr>
        <th>Phase</th>
        <th>Poule / Tour</th>
        <th>Joueur A</th>
        <th class="center">Sc. A</th>
        <th class="center">Sc. B</th>
        <th>Joueur B</th>
        <th>Arbitre</th>
        <th>Table</th>
        <th>Début</th>
        <th>Fin</th>
        <th>Durée</th>
      </tr>
    </thead>
    <tbody>
      @foreach($matches as $m)
      <tr>
        <td>
          @if($m['phase'] === 'pool')
            <span class="badge-pool">Poule</span>
          @else
            <span class="badge-ko">K.O.</span>
          @endif
        </td>
        <td>{{ $m['round_label'] }}</td>
        <td class="{{ $m['winner'] === 'a' ? 'winner' : ($m['winner'] === 'b' ? 'loser' : '') }}">
          {{ $m['player_a'] }}
        </td>
        <td class="center">
          @if($m['winner'] === 'a')
            <span class="score-a">{{ $m['score_a'] }}</span>
          @else
            <span style="color:#888;">{{ $m['score_a'] }}</span>
          @endif
        </td>
        <td class="center">
          @if($m['winner'] === 'b')
            <span class="score-b">{{ $m['score_b'] }}</span>
          @else
            <span style="color:#888;">{{ $m['score_b'] }}</span>
          @endif
        </td>
        <td class="{{ $m['winner'] === 'b' ? 'winner' : ($m['winner'] === 'a' ? 'loser' : '') }}">
          {{ $m['player_b'] }}
        </td>
        <td style="color:#666;">{{ $m['referee'] ?? '—' }}</td>
        <td style="color:#666;">{{ $m['table'] ?? '—' }}</td>
        <td class="center" style="color:#666;">{{ $m['started_at'] ?? '—' }}</td>
        <td class="center" style="color:#666;">{{ $m['ended_at'] ?? '—' }}</td>
        <td class="center" style="color:#666;">{{ $m['duration'] ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="summary">
    {{ count($matches) }} match(s) au total
    · {{ collect($matches)->where('phase', 'pool')->count() }} de poule
    · {{ collect($matches)->where('phase', '!=', 'pool')->count() }} de phase finale
  </div>
@endif

<div class="print-footer">
  <span>Club 8 Pool · Icone Pool, Libreville</span>
  <span>{{ $competition->name }} · {{ $dateLabel }}</span>
</div>

<script>
  // Auto-print si paramètre autoprint dans l'URL
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
  }
</script>
</body>
</html>
