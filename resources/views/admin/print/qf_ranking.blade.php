<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $competition->name }} — Classement des quart-de-finalistes</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; background: #fff; }

  .cover { padding: 26px 24px 18px; border-bottom: 3px solid #111; margin-bottom: 20px; }
  .cover .kicker { font-size: 9px; letter-spacing: 0.26em; text-transform: uppercase; color: #888; }
  .cover h1 { font-size: 24px; font-weight: 700; margin-top: 6px; }
  .cover .sub { font-size: 12px; color: #555; margin-top: 8px; }

  .warn {
    margin: 0 24px 18px; padding: 10px 14px; border: 1px solid #c99a00;
    background: #fff8e1; color: #8a6d00; font-size: 11px; font-weight: 600;
  }

  .board { padding: 0 24px; max-width: 640px; }
  table { width: 100%; border-collapse: collapse; }
  thead th {
    background: #2e7d5e; color: #fff; font-size: 9px; letter-spacing: 0.14em;
    text-transform: uppercase; padding: 8px 12px; text-align: left; font-weight: 600;
  }
  thead th.center { text-align: center; }
  thead th.right  { text-align: right; }
  tbody tr { border-bottom: 1px solid #e0e0e0; }
  tbody td { padding: 9px 12px; vertical-align: middle; }
  tbody td.center { text-align: center; }
  tbody td.right  { text-align: right; }

  .rank-cell { font-size: 16px; font-weight: 700; width: 54px; text-align: center; }
  .rank-1 { color: #b8860b; } .rank-2 { color: #808080; } .rank-3 { color: #b87333; }
  .podium td { background: #f4faf6; }
  .name { font-weight: 700; font-size: 14px; }
  .in-play { font-size: 9px; color: #c62828; letter-spacing: 0.08em; }
  .frames { font-family: 'Courier New', monospace; }
  .diff-pos { color: #1b5e38; font-weight: 700; }
  .diff-neg { color: #c62828; }

  .empty { padding: 40px; text-align: center; color: #999; }

  .print-footer {
    margin: 26px 24px 0; padding-top: 8px; border-top: 1px solid #ddd;
    font-size: 9px; color: #aaa; display: flex; justify-content: space-between;
  }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    @page { margin: 14mm 12mm; }
  }
</style>
</head>
<body>

<div class="cover">
  <div class="kicker">Club 8 Pool · Phase finale</div>
  <h1>{{ $competition->name }}</h1>
  <div class="sub">Classement des 8 quart-de-finalistes — du 1<sup>er</sup> au 8<sup>e</sup></div>
</div>

<button class="no-print" onclick="window.print()"
        style="margin: 0 24px 16px; padding: 8px 18px; background: #111; color: #fff;
               border: none; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 3px;">
  ⊕ Imprimer / Enregistrer PDF
</button>

@if($provisional)
<div class="warn">⚠ Phase finale incomplète — classement PROVISOIRE (certains matchs de la phase finale ne sont pas encore joués).</div>
@endif

@if(! $hasQf || ! count($rows))
<div class="empty">
  <div style="font-size: 22px;">—</div>
  <div style="margin-top: 10px; letter-spacing: 0.12em;">AUCUN QUART DE FINALE GÉNÉRÉ</div>
  <p style="margin-top: 8px; font-size: 11px;">Le classement sera disponible une fois la phase finale créée.</p>
</div>
@else
<div class="board">
  <table>
    <thead>
      <tr>
        <th class="center">Rg</th>
        <th>Joueur</th>
        <th class="center">Manches G-P</th>
        <th class="right">Diff</th>
      </tr>
    </thead>
    <tbody>
      @php $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉']; @endphp
      @foreach($rows as $r)
      <tr class="{{ $r['rank'] <= 3 ? 'podium' : '' }}">
        <td class="rank-cell {{ $r['rank'] <= 3 ? 'rank-' . $r['rank'] : '' }}">
          {{ $medals[$r['rank']] ?? $r['rank'] . 'e' }}
        </td>
        <td>
          <span class="name">{{ $r['name'] }}</span>
          @if($r['in_play'])<span class="in-play"> · encore en lice</span>@endif
        </td>
        <td class="center frames">{{ $r['won'] }}-{{ $r['lost'] }}</td>
        <td class="right {{ $r['diff'] > 0 ? 'diff-pos' : ($r['diff'] < 0 ? 'diff-neg' : '') }}">
          {{ $r['diff'] > 0 ? '+' : '' }}{{ $r['diff'] }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <p style="font-size: 10px; color: #888; margin-top: 12px; line-height: 1.6;">
    <strong>Méthode :</strong> 1<sup>er</sup> = vainqueur de la finale · 2<sup>e</sup> = finaliste ·
    3<sup>e</sup> = vainqueur du match pour la 3<sup>e</sup> place · 4<sup>e</sup> = perdant de ce match ·
    5<sup>e</sup>–8<sup>e</sup> = perdants des quarts.
    Départage au différentiel de manches sur l'ensemble du tournoi.
  </p>
</div>
@endif

<div class="print-footer">
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
