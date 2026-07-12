<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $competition->name }} — Rapport complet</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; background: #fff; }

  /* Cover / header */
  .cover {
    padding: 26px 20px 20px;
    border-bottom: 3px solid #111;
    margin-bottom: 20px;
  }
  .cover .kicker { font-size: 9px; letter-spacing: 0.26em; text-transform: uppercase; color: #888; }
  .cover h1 { font-size: 26px; font-weight: 700; margin-top: 6px; letter-spacing: -0.01em; }
  .cover .sub { font-size: 12px; color: #555; margin-top: 8px; line-height: 1.6; }

  /* Section titles */
  .section { margin-bottom: 26px; }
  .section-title {
    font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    padding-bottom: 5px; border-bottom: 2px solid #111; margin-bottom: 12px;
  }

  /* KPI cards */
  .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .kpi {
    border: 1px solid #ddd; padding: 10px 12px; background: #fafafa;
  }
  .kpi .val { font-size: 22px; font-weight: 700; color: #1b5e38; }
  .kpi .lbl { font-size: 8px; letter-spacing: 0.14em; text-transform: uppercase; color: #888; margin-top: 3px; }

  /* Winner banner */
  .winner {
    display: flex; align-items: center; gap: 14px;
    border: 2px solid #1b5e38; background: #eef7f1; padding: 14px 18px; margin-bottom: 20px;
  }
  .winner .trophy { font-size: 30px; }
  .winner .lbl { font-size: 9px; letter-spacing: 0.22em; text-transform: uppercase; color: #888; }
  .winner .name { font-size: 22px; font-weight: 700; color: #1b5e38; margin-top: 2px; }

  /* Tables */
  table { width: 100%; border-collapse: collapse; }
  thead th {
    background: #2e7d5e; color: #fff; font-size: 8px; letter-spacing: 0.14em;
    text-transform: uppercase; padding: 5px 8px; text-align: left; font-weight: 600;
  }
  thead th.center { text-align: center; }
  .tbl-matches thead th { background: #444; }
  tbody tr { border-bottom: 1px solid #e0e0e0; }
  tbody tr:nth-child(even) { background: #f8f8f8; }
  tbody td { padding: 5px 8px; vertical-align: middle; }
  tbody td.center { text-align: center; }

  /* Pool block */
  .pool-section { margin-bottom: 20px; page-break-inside: avoid; }
  .pool-header {
    display: flex; align-items: center; gap: 12px;
    background: #1a1a1a; color: #fff; padding: 6px 14px; margin-bottom: 8px;
  }
  .pool-header .pool-name { font-size: 14px; font-weight: 700; letter-spacing: 0.06em; }
  .pool-header .pool-sub  { font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: #aaa; }
  .tbl-standings { margin-bottom: 8px; }
  .rank-cell { font-size: 12px; font-weight: 700; }
  .rank-q { color: #1b5e38; }
  .rank-out { color: #aaa; }
  .qualifier-row td { border-left: 3px solid #2e7d5e; }
  .name-cell { font-weight: 600; font-size: 11px; }
  .slot-cell { font-size: 8px; color: #888; letter-spacing: 0.1em; }
  .diff-pos { color: #1b5e38; font-weight: 700; }
  .diff-neg { color: #c62828; }
  .winner-p { font-weight: 700; color: #111; }
  .loser-p  { color: #888; }
  .score-cell { font-size: 12px; font-weight: 700; color: #1b5e38; }
  .score-sep { color: #bbb; margin: 0 3px; }

  /* Knockout rounds */
  .ko-round { margin-bottom: 12px; page-break-inside: avoid; }
  .ko-round-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em;
    color: #2e7d5e; margin-bottom: 6px;
  }

  /* Leaderboards */
  .lead-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .lead-card { border: 1px solid #ddd; }
  .lead-head {
    font-size: 9px; letter-spacing: 0.14em; text-transform: uppercase; color: #fff;
    background: #2e7d5e; padding: 6px 10px; font-weight: 700;
  }
  .lead-row { display: flex; justify-content: space-between; padding: 5px 10px; border-bottom: 1px solid #eee; }
  .lead-row .who { font-size: 10px; }
  .lead-row .who .club { color: #999; font-size: 8px; }
  .lead-row .num { font-size: 12px; font-weight: 700; color: #1b5e38; }
  .lead-empty { padding: 10px; font-size: 9px; color: #aaa; text-align: center; }

  .print-footer {
    margin-top: 22px; padding-top: 8px; border-top: 1px solid #ddd;
    font-size: 9px; color: #aaa; display: flex; justify-content: space-between;
  }

  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .section, .pool-section, .ko-round { page-break-inside: avoid; }
    @page { margin: 12mm 10mm; }
  }
</style>
</head>
<body>

@php
  $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
  $fmt = function ($d) use ($months) {
    if (! $d) return null;
    $ts = strtotime($d);
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
  };
  $start = $fmt($competition->starts_on);
  $end   = $fmt($competition->ends_on);
@endphp

<div class="cover">
  <div class="kicker">Club 8 Pool · Rapport de compétition</div>
  <h1>{{ $competition->name }}</h1>
  <div class="sub">
    {{ $competition->venue ?? "L'Icône" }}, {{ $competition->city ?? 'Libreville' }}
    @if($start) · {{ $start }}@if($end && $end !== $start) → {{ $end }}@endif @endif<br />
    Discipline : {{ strtoupper($competition->discipline) }} ·
    Race poules {{ $competition->pool_race_to ?? $competition->race_to }} ·
    Race finale {{ $competition->knockout_race_to ?? $competition->race_to }}
    @if($competition->prize_pool) · Dotation {{ number_format($competition->prize_pool, 0, ',', ' ') }} FCFA @endif
  </div>
</div>

<button class="no-print" onclick="window.print()"
        style="margin: 0 20px 16px; padding: 8px 18px; background: #111; color: #fff;
               border: none; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 3px;">
  ⊕ Imprimer / Enregistrer PDF
</button>

{{-- ── Vainqueur ── --}}
@if($winner)
<div class="winner" style="margin: 0 20px 20px;">
  <span class="trophy">🏆</span>
  <div>
    <div class="lbl">Vainqueur</div>
    <div class="name">{{ $winner }}</div>
  </div>
</div>
@endif

<div style="padding: 0 20px;">

{{-- ── Synthèse ── --}}
<div class="section">
  <div class="section-title">Synthèse</div>
  <div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $overview['players'] }}</div><div class="lbl">Joueurs inscrits</div></div>
    <div class="kpi"><div class="val">{{ $overview['matches_done'] }}/{{ $overview['matches_total'] }}</div><div class="lbl">Matchs joués</div></div>
    <div class="kpi"><div class="val">{{ $overview['frames_total'] }}</div><div class="lbl">Manches jouées</div></div>
    <div class="kpi"><div class="val">{{ $overview['frames_avg'] }}</div><div class="lbl">Manches / match</div></div>
    <div class="kpi"><div class="val">{{ $overview['pools'] }}</div><div class="lbl">Poules</div></div>
    <div class="kpi"><div class="val">{{ $overview['avg_duration'] ?? '—' }}@if($overview['avg_duration'])<span style="font-size:11px">min</span>@endif</div><div class="lbl">Durée moy. / match</div></div>
    <div class="kpi"><div class="val">{{ $overview['total_hours'] ?? '—' }}@if($overview['total_hours'])<span style="font-size:11px">h</span>@endif</div><div class="lbl">Temps de jeu total</div></div>
    <div class="kpi"><div class="val">{{ count($knockoutRounds) }}</div><div class="lbl">Tours phase finale</div></div>
  </div>
</div>

{{-- ── Poules ── --}}
@if(count($poolData))
<div class="section">
  <div class="section-title">Phase de poules</div>
  @foreach($poolData as $pool)
  <div class="pool-section">
    <div class="pool-header">
      <span class="pool-name">POULE {{ $pool['name'] }}</span>
      <span class="pool-sub">{{ $pool['played'] }}/{{ $pool['total'] }} match{{ $pool['total'] > 1 ? 's' : '' }} joué{{ $pool['played'] > 1 ? 's' : '' }}</span>
    </div>

    <table class="tbl-standings">
      <thead>
        <tr>
          <th class="center" style="width:32px;">Rg</th>
          <th>Joueur</th>
          <th class="center" style="width:34px;">V</th>
          <th class="center" style="width:34px;">W</th>
          <th class="center" style="width:34px;">L</th>
          <th class="center" style="width:48px;">Diff</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pool['standings'] as $s)
        <tr class="{{ $s['rank'] <= 2 ? 'qualifier-row' : '' }}">
          <td class="center rank-cell {{ $s['rank'] <= 2 ? 'rank-q' : 'rank-out' }}">{{ $s['rank'] }}</td>
          <td><span class="name-cell">{{ $s['name'] }}</span><span class="slot-cell">&nbsp;{{ $pool['name'] }}{{ $s['pool_slot'] }}</span></td>
          <td class="center" style="font-weight:600;">{{ $s['v'] }}</td>
          <td class="center">{{ $s['w'] }}</td>
          <td class="center" style="color:#888;">{{ $s['l'] }}</td>
          <td class="center {{ $s['diff'] > 0 ? 'diff-pos' : ($s['diff'] < 0 ? 'diff-neg' : '') }}">{{ $s['diff'] > 0 ? '+' : '' }}{{ $s['diff'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    @if(count($pool['matches']))
    <table class="tbl-matches">
      <thead>
        <tr>
          <th>Joueur A</th>
          <th class="center" style="width:66px;">Score</th>
          <th>Joueur B</th>
          <th class="center" style="width:46px;">Table</th>
          <th style="width:80px;">Arbitre</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pool['matches'] as $m)
        <tr>
          <td class="{{ $m['winner'] === 'a' ? 'winner-p' : ($m['winner'] === 'b' ? 'loser-p' : '') }}">{{ $m['player_a'] }}</td>
          <td class="center">
            @if($m['status'] === 'done')<span class="score-cell">{{ $m['score_a'] }}<span class="score-sep">—</span>{{ $m['score_b'] }}</span>
            @else<span style="color:#bbb;">–</span>@endif
          </td>
          <td class="{{ $m['winner'] === 'b' ? 'winner-p' : ($m['winner'] === 'a' ? 'loser-p' : '') }}">{{ $m['player_b'] }}</td>
          <td class="center" style="color:#666;">{{ $m['table'] ?? '—' }}</td>
          <td style="color:#666;">{{ $m['referee'] ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
  @endforeach
</div>
@endif

{{-- ── Phase finale ── --}}
@if(count($knockoutRounds))
<div class="section">
  <div class="section-title">Phase finale</div>
  @foreach($knockoutRounds as $round)
  <div class="ko-round">
    <div class="ko-round-title">{{ $round['label'] }}</div>
    <table class="tbl-matches">
      <thead>
        <tr>
          <th>Joueur A</th>
          <th class="center" style="width:66px;">Score</th>
          <th>Joueur B</th>
          <th class="center" style="width:46px;">Table</th>
          <th style="width:80px;">Arbitre</th>
        </tr>
      </thead>
      <tbody>
        @foreach($round['matches'] as $m)
        <tr>
          <td class="{{ $m['winner'] === 'a' ? 'winner-p' : ($m['winner'] === 'b' ? 'loser-p' : '') }}">{{ $m['player_a'] }}</td>
          <td class="center">
            @if($m['status'] === 'done')<span class="score-cell">{{ $m['score_a'] }}<span class="score-sep">—</span>{{ $m['score_b'] }}</span>
            @elseif($m['status'] === 'live')<span style="color:#c62828;font-weight:700;">● Live</span>
            @else<span style="color:#bbb;">VS</span>@endif
          </td>
          <td class="{{ $m['winner'] === 'b' ? 'winner-p' : ($m['winner'] === 'a' ? 'loser-p' : '') }}">{{ $m['player_b'] }}</td>
          <td class="center" style="color:#666;">{{ $m['table'] ?? '—' }}</td>
          <td style="color:#666;">{{ $m['referee'] ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endforeach
</div>
@endif

{{-- ── Meilleures statistiques ── --}}
<div class="section">
  <div class="section-title">Meilleures statistiques</div>
  <div class="lead-grid">
    @php
      $boards = [
        ['title' => 'Manches gagnées', 'rows' => $topStats['frames_won']],
        ['title' => 'Matchs gagnés',   'rows' => $topStats['matches_won']],
        ['title' => 'Break & runs',    'rows' => $topStats['break_and_runs']],
      ];
    @endphp
    @foreach($boards as $b)
    <div class="lead-card">
      <div class="lead-head">{{ $b['title'] }}</div>
      @forelse($b['rows'] as $r)
      <div class="lead-row">
        <div class="who">{{ $r['name'] }}@if($r['club'])<div class="club">{{ $r['club'] }}</div>@endif</div>
        <div class="num">{{ $r['value'] }}</div>
      </div>
      @empty
      <div class="lead-empty">Aucune donnée</div>
      @endforelse
    </div>
    @endforeach
  </div>
</div>

</div>

<div class="print-footer" style="margin: 22px 20px 0;">
  <span>Club 8 Pool · Icône Pool, Libreville</span>
  <span>{{ $competition->name }} · Généré le {{ now()->format('d/m/Y H:i') }}</span>
</div>

<script>
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
  }
</script>
</body>
</html>
