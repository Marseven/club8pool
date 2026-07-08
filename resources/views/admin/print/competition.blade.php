<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $competition->name }} — Poules & Matchs</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; background: #fff; }

  /* Page header */
  .page-header {
    padding: 14px 20px 10px;
    border-bottom: 2px solid #111;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 16px;
  }
  .page-header h1 { font-size: 18px; font-weight: 700; }
  .page-header .meta { font-size: 9px; color: #555; text-align: right; line-height: 1.7; }

  /* Pool section */
  .pool-section {
    margin-bottom: 28px;
    page-break-inside: avoid;
  }
  .pool-header {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #1a1a1a;
    color: #fff;
    padding: 7px 14px;
    margin-bottom: 10px;
  }
  .pool-header .pool-name { font-size: 15px; font-weight: 700; letter-spacing: 0.06em; }
  .pool-header .pool-sub  { font-size: 9px; letter-spacing: 0.18em; text-transform: uppercase; color: #aaa; }

  /* Tables */
  table { width: 100%; border-collapse: collapse; }
  .tbl-standings { margin-bottom: 10px; }
  .tbl-matches   { margin-bottom: 2px; }

  thead th {
    background: #2e7d5e;
    color: #fff;
    font-size: 8px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 5px 8px;
    text-align: left;
    font-weight: 600;
  }
  thead th.center { text-align: center; }
  thead th.right  { text-align: right; }

  .tbl-matches thead th { background: #444; }

  tbody tr { border-bottom: 1px solid #e0e0e0; }
  tbody tr:nth-child(even) { background: #f8f8f8; }
  tbody td { padding: 5px 8px; vertical-align: middle; }
  tbody td.center { text-align: center; }
  tbody td.right  { text-align: right; }

  /* Standings */
  .rank-cell    { font-size: 13px; font-weight: 700; }
  .rank-q       { color: #1b5e38; }
  .rank-out     { color: #aaa; }
  .qualifier-row td { border-left: 3px solid #2e7d5e; }
  .name-cell    { font-weight: 600; font-size: 11px; }
  .slot-cell    { font-size: 8px; color: #888; letter-spacing: 0.1em; }
  .diff-pos     { color: #1b5e38; font-weight: 700; }
  .diff-neg     { color: #c62828; }

  /* Matches */
  .player-a     { font-weight: 600; }
  .player-b     { font-weight: 600; }
  .winner       { font-weight: 700; color: #111; }
  .loser        { color: #888; }
  .score-cell   { font-size: 12px; font-weight: 700; color: #1b5e38; }
  .score-sep    { color: #bbb; margin: 0 3px; }
  .status-done    { color: #1b5e38; }
  .status-live    { color: #c62828; font-weight: 700; }
  .status-pending { color: #aaa; }

  /* Stats bar */
  .pool-stats {
    font-size: 9px;
    color: #777;
    text-align: right;
    margin-bottom: 10px;
    padding: 0 2px;
  }

  /* Print footer */
  .print-footer {
    margin-top: 24px;
    padding-top: 8px;
    border-top: 1px solid #ddd;
    font-size: 9px;
    color: #aaa;
    display: flex;
    justify-content: space-between;
  }

  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .pool-section { page-break-inside: avoid; }
    @page { margin: 12mm 10mm; }
  }
</style>
</head>
<body>

<div class="page-header">
  <div>
    <div style="font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase; color: #888; margin-bottom: 4px;">
      Club 8 Pool · Classements & Résultats
    </div>
    <h1>{{ $competition->name }}</h1>
    <div style="font-size: 11px; color: #555; margin-top: 4px;">Phase de poules — {{ count($poolData) }} groupe{{ count($poolData) > 1 ? 's' : '' }}</div>
  </div>
  <div class="meta">
    Compétition : {{ $competition->venue ?? 'L\'Icône' }}, {{ $competition->city ?? 'Libreville' }}<br />
    Race to {{ $competition->pool_race_to ?? $competition->race_to }} (poules)<br />
    Généré le {{ now()->format('d/m/Y à H:i') }}
  </div>
</div>

<button class="no-print"
        onclick="window.print()"
        style="margin: 0 20px 16px; padding: 8px 18px; background: #111; color: #fff;
               border: none; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 3px;">
  ⊕ Imprimer / Enregistrer PDF
</button>

@foreach($poolData as $pool)
<div class="pool-section">
  <div class="pool-header">
    <div>
      <span class="pool-name">POULE {{ $pool['name'] }}</span>
    </div>
    <div class="pool-sub">
      {{ $pool['played'] }}/{{ $pool['total'] }} match{{ $pool['total'] > 1 ? 's' : '' }} joué{{ $pool['played'] > 1 ? 's' : '' }}
    </div>
  </div>

  {{-- Standings --}}
  <table class="tbl-standings">
    <thead>
      <tr>
        <th class="center" style="width:36px;">Rg</th>
        <th>Joueur</th>
        <th class="center" style="width:36px;">V</th>
        <th class="center" style="width:36px;">W</th>
        <th class="center" style="width:36px;">L</th>
        <th class="center" style="width:52px;">Diff</th>
      </tr>
    </thead>
    <tbody>
      @foreach($pool['standings'] as $s)
      <tr class="{{ $s['rank'] <= 2 ? 'qualifier-row' : '' }}">
        <td class="center rank-cell {{ $s['rank'] <= 2 ? 'rank-q' : 'rank-out' }}">
          {{ $s['rank'] }}
        </td>
        <td>
          <span class="name-cell">{{ $s['name'] }}</span>
          <span class="slot-cell">&nbsp;{{ $pool['name'] }}{{ $s['pool_slot'] }}</span>
        </td>
        <td class="center" style="font-weight:600;">{{ $s['v'] }}</td>
        <td class="center">{{ $s['w'] }}</td>
        <td class="center" style="color:#888;">{{ $s['l'] }}</td>
        <td class="center {{ $s['diff'] > 0 ? 'diff-pos' : ($s['diff'] < 0 ? 'diff-neg' : '') }}">
          {{ $s['diff'] > 0 ? '+' : '' }}{{ $s['diff'] }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Matches --}}
  @if(count($pool['matches']) > 0)
  <table class="tbl-matches">
    <thead>
      <tr>
        <th>Joueur A</th>
        <th class="center" style="width:70px;">Score</th>
        <th>Joueur B</th>
        <th class="center" style="width:52px;">Statut</th>
        <th class="center" style="width:52px;">Table</th>
        <th class="center" style="width:46px;">Début</th>
        <th class="center" style="width:46px;">Fin</th>
        <th style="width:80px;">Arbitre</th>
      </tr>
    </thead>
    <tbody>
      @foreach($pool['matches'] as $m)
      <tr>
        <td class="{{ $m['winner'] === 'a' ? 'winner' : ($m['winner'] === 'b' ? 'loser' : 'player-a') }}">
          {{ $m['player_a'] }}
        </td>
        <td class="center">
          @if($m['status'] === 'done')
            <span class="score-cell">{{ $m['score_a'] }}<span class="score-sep">—</span>{{ $m['score_b'] }}</span>
          @else
            <span style="color:#bbb;">–</span>
          @endif
        </td>
        <td class="{{ $m['winner'] === 'b' ? 'winner' : ($m['winner'] === 'a' ? 'loser' : 'player-b') }}">
          {{ $m['player_b'] }}
        </td>
        <td class="center status-{{ $m['status'] }}">
          @if($m['status'] === 'done') Terminé
          @elseif($m['status'] === 'live') ● Live
          @else À venir
          @endif
        </td>
        <td class="center" style="color:#666;">{{ $m['table'] ?? '—' }}</td>
        <td class="center" style="color:#666;">{{ $m['started_at'] ?? '—' }}</td>
        <td class="center" style="color:#666;">{{ $m['ended_at'] ?? '—' }}</td>
        <td style="color:#666;">{{ $m['referee'] ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>
@endforeach

<div class="print-footer">
  <span>Club 8 Pool · Icone Pool, Libreville</span>
  <span>{{ $competition->name }} · Généré le {{ now()->format('d/m/Y H:i') }}</span>
</div>

<script>
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
  }
</script>
</body>
</html>
