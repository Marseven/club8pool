/**
 * Human-readable labels for all enum/status values used across the app.
 */

export const competitionStatus = {
  draft:        'Brouillon',
  registration: 'Inscriptions ouvertes',
  in_progress:  'En cours',
  finished:     'Terminée',
};

export const competitionStatusChip = {
  draft:        '',
  registration: 'felt',
  in_progress:  'live',
  finished:     'felt',
};

export const matchStatus = {
  pending:   'En attente',
  scheduled: 'Programmé',
  live:      'En cours',
  done:      'Terminé',
  disputed:  'Litigieux',
};

export const registrationStatus = {
  pending:    'En attente',
  confirmed:  'Confirmé',
  paid:       'Payé',
  cancelled:  'Annulé',
  waitlisted: 'Liste d\'attente',
};

export const registrationStatusChip = {
  pending:    '',
  confirmed:  'felt',
  paid:       'felt',
  cancelled:  'live',
  waitlisted: '',
};

export const tableStatus = {
  idle:  'Libre',
  live:  'En cours',
  maint: 'Maintenance',
};

export const competitionFormat = {
  single_elim:    'Élimination directe',
  double_elim:    'Double élimination',
  pools:          'Poules + phase finale',
  round_robin:    'Round-robin',
  simple:         'Format simple',
  teams:          'Par équipes',
};

export const round = {
  R32: '32e de finale',
  R16: '8e de finale',
  QF:  'Quart de finale',
  SF:  'Demi-finale',
  F:   'Finale',
};

/** Generic fallback: return label or the raw value if unknown. */
export const label = (map, value) => map[value] ?? value;
