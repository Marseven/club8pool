<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const props = defineProps({
  competition: Object,
  days: Array,
});

const selected = ref(props.days?.[0]?.date ?? null);

const selectedDay = computed(() => props.days?.find(d => d.date === selected.value) ?? null);

const fmtDate = (d) => {
  if (!d) return '—';
  const months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
  const [y, m, day] = d.split('-');
  return `${parseInt(day)} ${months[parseInt(m) - 1]} ${y}`;
};

const excelUrl = computed(() => selected.value ? `/admin/exports/excel?date=${selected.value}` : null);
const pdfUrl   = computed(() => selected.value ? `/admin/exports/pdf?date=${selected.value}` : null);
</script>

<template>
  <Head title="Exports · Admin" />
  <div style="display: flex; min-height: 100vh; background: var(--ink);">
    <AdminSidebar active="export" />
    <main style="flex: 1; display: flex; flex-direction: column; min-width: 0;">

      <header style="display: flex; justify-content: space-between; align-items: center;
                     padding: 20px 32px; border-bottom: 1px solid var(--line);">
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute);">EXPORTS · RÉSULTATS PAR JOURNÉE</div>
          <div class="disp-a" style="font-size: 24px; margin-top: 6px;">
            {{ competition?.name ?? 'Aucune compétition active' }}
          </div>
        </div>
      </header>

      <!-- No competition -->
      <div v-if="!competition"
           style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--mute); padding: 60px;">
        <div style="text-align: center;">
          <div class="disp-a" style="font-size: 28px; color: var(--mute-2);">—</div>
          <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; margin-top: 12px;">AUCUNE COMPÉTITION ACTIVE</div>
        </div>
      </div>

      <!-- No matches yet -->
      <div v-else-if="days.length === 0"
           style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--mute); padding: 60px;">
        <div style="text-align: center; max-width: 400px;">
          <div class="disp-a" style="font-size: 28px; color: var(--mute-2);">—</div>
          <div class="mono" style="font-size: 11px; letter-spacing: 0.18em; margin-top: 12px;">AUCUN MATCH TERMINÉ</div>
          <p style="font-size: 13px; color: var(--mute); margin-top: 10px; line-height: 1.6;">
            Les exports seront disponibles une fois les premiers matchs complétés.
          </p>
        </div>
      </div>

      <!-- Main content -->
      <div v-else style="padding: 32px; display: flex; flex-direction: column; gap: 24px; max-width: 760px;">

        <!-- Day selector -->
        <div>
          <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">
            SÉLECTIONNER UNE JOURNÉE
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <button
              v-for="d in days"
              :key="d.date"
              @click="selected = d.date"
              :style="{
                padding: '8px 16px',
                border: '1px solid ' + (selected === d.date ? 'var(--felt-2)' : 'var(--line)'),
                background: selected === d.date ? 'var(--felt)' : 'var(--ink-2)',
                color: selected === d.date ? '#fff' : 'var(--chalk)',
                cursor: 'pointer',
                fontSize: '12px',
                fontWeight: selected === d.date ? 700 : 400,
                transition: 'all 0.15s',
              }"
            >
              {{ fmtDate(d.date) }}
              <span :style="{ color: selected === d.date ? 'rgba(255,255,255,0.7)' : 'var(--mute)', fontSize: '10px', marginLeft: '4px' }">
                ({{ d.match_count }} match{{ d.match_count > 1 ? 's' : '' }})
              </span>
            </button>
          </div>
        </div>

        <!-- Selected day info + export buttons -->
        <div v-if="selectedDay" style="border: 1px solid var(--line); background: var(--ink-2); padding: 24px;">
          <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
              <div class="mono" style="font-size: 9px; letter-spacing: 0.22em; color: var(--mute);">JOURNÉE SÉLECTIONNÉE</div>
              <div class="disp-a" style="font-size: 28px; margin-top: 8px; color: var(--chalk);">
                {{ fmtDate(selectedDay.date) }}
              </div>
              <div class="mono" style="font-size: 12px; color: var(--felt-2); margin-top: 6px;">
                {{ selectedDay.match_count }} match{{ selectedDay.match_count > 1 ? 's' : '' }} terminé{{ selectedDay.match_count > 1 ? 's' : '' }}
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px; min-width: 220px;">
              <!-- Excel -->
              <a
                :href="excelUrl"
                style="display: flex; align-items: center; gap: 12px;
                       padding: 14px 18px;
                       border: 1px solid var(--line-strong);
                       background: var(--ink-3);
                       text-decoration: none;
                       color: var(--chalk);"
              >
                <span style="font-size: 22px; line-height: 1;">⊞</span>
                <div>
                  <div style="font-size: 13px; font-weight: 700;">Télécharger Excel</div>
                  <div class="mono" style="font-size: 9px; letter-spacing: 0.14em; color: var(--mute); margin-top: 2px;">.XLSX · FEUILLE FORMATÉE</div>
                </div>
              </a>

              <!-- PDF print -->
              <a
                :href="pdfUrl"
                target="_blank"
                rel="noopener"
                style="display: flex; align-items: center; gap: 12px;
                       padding: 14px 18px;
                       border: 1px solid var(--line-strong);
                       background: var(--ink-3);
                       text-decoration: none;
                       color: var(--chalk);"
              >
                <span style="font-size: 22px; line-height: 1;">⊟</span>
                <div>
                  <div style="font-size: 13px; font-weight: 700;">Imprimer / PDF</div>
                  <div class="mono" style="font-size: 9px; letter-spacing: 0.14em; color: var(--mute); margin-top: 2px;">OUVRE LA PAGE D'IMPRESSION</div>
                </div>
              </a>
            </div>
          </div>
        </div>

        <!-- Info -->
        <div style="padding: 14px 18px; border: 1px dashed var(--line); font-size: 12px; color: var(--mute); line-height: 1.7;">
          <strong style="color: var(--chalk);">Excel</strong> — Télécharge un fichier .xlsx avec tous les résultats formatés : joueurs, scores, arbitres, tables, horaires et durées.<br />
          <strong style="color: var(--chalk);">PDF</strong> — Ouvre une page d'impression dans un nouvel onglet. Utilisez <em>Fichier → Imprimer → Enregistrer en PDF</em> dans votre navigateur.
        </div>

      </div>

      <!-- ── Export compétition complète ── -->
      <div v-if="competition" style="padding: 0 32px 32px;">
        <div class="mono" style="font-size: 10px; letter-spacing: 0.22em; color: var(--mute); margin-bottom: 12px;">
          EXPORT COMPÉTITION COMPLÈTE
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; max-width: 900px;">
          <!-- Rapport complet -->
          <div style="border: 1px solid var(--felt-2); background: rgba(45,168,118,0.04); padding: 22px;
                      display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
            <div>
              <div class="disp-a" style="font-size: 20px;">Rapport complet</div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 6px; line-height: 1.6;">
                Synthèse, poules, phase finale, podium et meilleures statistiques dans un seul document.
              </div>
            </div>
            <a
              :href="`/admin/competitions/${competition.id}/rapport`"
              target="_blank"
              rel="noopener"
              style="display: flex; align-items: center; gap: 12px;
                     padding: 14px 18px;
                     border: 1px solid var(--felt-2);
                     background: var(--felt);
                     text-decoration: none;
                     color: #fff;
                     white-space: nowrap;"
            >
              <span style="font-size: 22px; line-height: 1;">▦</span>
              <div>
                <div style="font-size: 13px; font-weight: 700;">Rapport complet PDF</div>
                <div class="mono" style="font-size: 9px; letter-spacing: 0.14em; color: rgba(255,255,255,0.75); margin-top: 2px;">TOUT LE TOURNOI</div>
              </div>
            </a>
          </div>

          <!-- Poules & Matchs -->
          <div style="border: 1px solid var(--line); background: var(--ink-2); padding: 22px;
                      display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
            <div>
              <div class="disp-a" style="font-size: 20px;">Poules & Matchs</div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 6px; line-height: 1.6;">
                Classements de toutes les poules et l'ensemble des matchs de la compétition.
              </div>
            </div>
            <a
              href="/admin/exports/competition-pdf"
              target="_blank"
              rel="noopener"
              style="display: flex; align-items: center; gap: 12px;
                     padding: 14px 18px;
                     border: 1px solid var(--line-strong);
                     background: var(--ink-3);
                     text-decoration: none;
                     color: var(--chalk);
                     white-space: nowrap;"
            >
              <span style="font-size: 22px; line-height: 1;">⊟</span>
              <div>
                <div style="font-size: 13px; font-weight: 700;">PDF Poules & Matchs</div>
                <div class="mono" style="font-size: 9px; letter-spacing: 0.14em; color: var(--mute); margin-top: 2px;">POULES UNIQUEMENT</div>
              </div>
            </a>
          </div>

          <!-- Classement 8 quart-de-finalistes -->
          <div style="border: 1px solid var(--line); background: var(--ink-2); padding: 22px;
                      display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
            <div>
              <div class="disp-a" style="font-size: 20px;">Classement 1-8</div>
              <div style="font-size: 12px; color: var(--mute); margin-top: 6px; line-height: 1.6;">
                Classement final des 8 quart-de-finalistes, du 1<sup>er</sup> au 8<sup>e</sup>.
              </div>
            </div>
            <a
              :href="`/admin/competitions/${competition.id}/export/classement-qf`"
              target="_blank"
              rel="noopener"
              style="display: flex; align-items: center; gap: 12px;
                     padding: 14px 18px;
                     border: 1px solid var(--line-strong);
                     background: var(--ink-3);
                     text-decoration: none;
                     color: var(--chalk);
                     white-space: nowrap;"
            >
              <span style="font-size: 22px; line-height: 1;">🏆</span>
              <div>
                <div style="font-size: 13px; font-weight: 700;">Classement des quarts</div>
                <div class="mono" style="font-size: 9px; letter-spacing: 0.14em; color: var(--mute); margin-top: 2px;">1<sup>er</sup> AU 8<sup>e</sup></div>
              </div>
            </a>
          </div>
        </div>
      </div>


    </main>
  </div>
</template>
