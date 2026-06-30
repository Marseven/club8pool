<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
  player: { type: Object, required: true },
  registrations: { type: Array, default: () => [] },
  recent_matches: { type: Array, default: () => [] },
  next_match: { type: Object, default: null },
  rating: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const photoInput = ref(null);

// Initials fallback
const initials = computed(() => {
  const fn = props.player.first_name ?? '';
  const ln = props.player.last_name ?? '';
  if (fn && ln) return (fn[0] + ln[0]).toUpperCase();
  return (props.player.name ?? '?')[0].toUpperCase();
});

// Photo upload
function uploadPhoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('photo', file);
  router.post('/joueur/photo', fd, { forceFormData: true });
}

// Logout
function logout() {
  router.post('/joueur/logout');
}

// Status chip variant for registrations
function regChipVariant(status) {
  return (status === 'confirmed' || status === 'paid') ? 'felt' : '';
}

function regStatusLabel(status) {
  const map = {
    pending: 'EN ATTENTE',
    confirmed: 'CONFIRMÉ',
    paid: 'PAYÉ',
    cancelled: 'ANNULÉ',
    waitlisted: 'LISTE D\'ATTENTE',
  };
  return map[status] ?? status.toUpperCase();
}

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<template>
  <Head title="Mon espace · Club 8 Pool" />
  <PublicNav />

  <div style="background: var(--ink); min-height: 100vh; padding-bottom: 64px; color: var(--chalk);">
    <div class="container" style="padding-top: 40px;">

      <!-- Flash messages -->
      <div v-if="flash.success"
           style="background: rgba(31,138,91,0.15); border: 1px solid rgba(31,138,91,0.35); color: var(--felt-2);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.success }}
      </div>
      <div v-if="flash.error"
           style="background: rgba(229,72,77,0.1); border: 1px solid rgba(229,72,77,0.3); color: var(--live);
                  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;">
        {{ flash.error }}
      </div>

      <!-- ─── HEADER CARD ─── -->
      <div style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 12px;
                  padding: 32px; margin-bottom: 32px;">
        <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">

          <!-- Avatar -->
          <div style="flex-shrink: 0;">
            <img
              v-if="player.profile_photo_path"
              :src="`/storage/${player.profile_photo_path}`"
              :alt="player.name"
              style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
                     border: 2px solid var(--line-strong);"
            />
            <div v-else
                 style="width: 80px; height: 80px; border-radius: 50%; background: var(--felt);
                        display: flex; align-items: center; justify-content: center;
                        font-family: var(--font-display-a); font-size: 28px; font-weight: 700; color: #fff;">
              {{ initials }}
            </div>
          </div>

          <!-- Name & stats -->
          <div style="flex: 1; min-width: 0;">
            <h1 class="disp-a" style="font-size: 28px; margin: 0 0 4px;">{{ player.name }}</h1>
            <div class="mono" style="font-size: 11px; color: var(--mute); letter-spacing: 0.1em; margin-bottom: 16px;">
              @{{ player.login_name }}
            </div>

            <!-- Stats row -->
            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
              <div>
                <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Victoires</div>
                <div class="disp-a" style="font-size: 22px; color: var(--felt-2);">{{ player.wins ?? 0 }}</div>
              </div>
              <div>
                <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Défaites</div>
                <div class="disp-a" style="font-size: 22px;">{{ player.losses ?? 0 }}</div>
              </div>
              <div v-if="player.win_rate != null">
                <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Win rate</div>
                <div class="disp-a" style="font-size: 22px;">{{ player.win_rate }}%</div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
            <!-- Hidden file input -->
            <input
              ref="photoInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              style="display: none;"
              @change="uploadPhoto"
            />
            <button
              type="button"
              class="btn"
              style="font-size: 12px;"
              @click="photoInput.click()"
            >
              Changer ma photo
            </button>
            <Link href="/joueur/password/change" class="btn" style="font-size: 12px; text-align: center;">
              Changer mon mot de passe
            </Link>
            <button type="button" class="btn" style="font-size: 12px;" @click="logout">
              Se déconnecter ↪
            </button>
          </div>
        </div>
      </div>

      <!-- ─── RATING ELO ─── -->
      <div v-if="rating"
           style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 12px;
                  padding: 24px 32px; margin-bottom: 32px; display: flex; align-items: center; gap: 20px;">
        <div>
          <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.18em; text-transform: uppercase; margin-bottom: 6px;">Rating Elo</div>
          <div style="display: flex; align-items: baseline; gap: 10px;">
            <span class="disp-a" style="font-size: 36px; color: var(--felt-2);">{{ rating.value }}</span>
            <Chip v-if="rating.provisional" style="font-size: 10px;">provisoire</Chip>
          </div>
        </div>
        <div style="margin-left: auto; text-align: right;">
          <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Parties jouées</div>
          <div class="disp-a" style="font-size: 22px;">{{ rating.games_played }}</div>
        </div>
      </div>

      <!-- ─── MES COMPÉTITIONS ─── -->
      <section style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">MES COMPÉTITIONS</h2>

        <div v-if="registrations.length === 0"
             style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px;
                    padding: 24px; color: var(--mute); font-size: 14px;">
          Aucune inscription.
        </div>

        <div v-else style="display: flex; flex-direction: column; gap: 10px;">
          <Link
            v-for="r in registrations"
            :key="r.id"
            :href="`/joueur/competitions/${r.competition.id}`"
            style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px;
                   padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;
                   gap: 16px; text-decoration: none; color: inherit; transition: border-color 0.15s;"
            onmouseenter="this.style.borderColor='var(--line-strong)'"
            onmouseleave="this.style.borderColor='var(--line)'"
          >
            <div>
              <div style="font-weight: 600; margin-bottom: 4px;">{{ r.competition.name }}</div>
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.1em; text-transform: uppercase;">
                {{ r.competition.status }}
              </div>
            </div>
            <Chip :variant="regChipVariant(r.status)">{{ regStatusLabel(r.status) }}</Chip>
          </Link>
        </div>
      </section>

      <!-- ─── PROCHAIN MATCH ─── -->
      <section style="margin-bottom: 40px;">
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">PROCHAIN MATCH</h2>

        <div v-if="!next_match"
             style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px;
                    padding: 24px; color: var(--mute); font-size: 14px;">
          Aucun match à venir.
        </div>

        <div v-else
             style="background: var(--ink-3); border: 1px solid var(--line-strong); border-radius: 10px; padding: 24px 32px;">
          <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: center;">
            <div>
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Adversaire</div>
              <div style="font-size: 18px; font-weight: 600;">{{ next_match.opponent?.name ?? '—' }}</div>
            </div>
            <div v-if="next_match.table">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Table</div>
              <div style="font-size: 18px; font-weight: 600;">{{ next_match.table }}</div>
            </div>
            <div v-if="next_match.scheduled_at" style="margin-left: auto;">
              <div class="mono" style="font-size: 10px; color: var(--mute); letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 4px;">Heure prévue</div>
              <div class="mono" style="font-size: 14px; color: var(--chalk-2);">{{ formatDate(next_match.scheduled_at) }}</div>
            </div>
          </div>
          <div v-if="next_match.competition" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--line);">
            <span class="mono" style="font-size: 11px; color: var(--mute);">{{ next_match.competition.name }}</span>
          </div>
        </div>
      </section>

      <!-- ─── DERNIERS MATCHS ─── -->
      <section>
        <h2 class="disp-a" style="font-size: 20px; margin: 0 0 16px; letter-spacing: 0.04em;">DERNIERS MATCHS</h2>

        <div v-if="recent_matches.length === 0"
             style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px;
                    padding: 24px; color: var(--mute); font-size: 14px;">
          Aucun match joué.
        </div>

        <div v-else style="background: var(--ink-3); border: 1px solid var(--line); border-radius: 10px; overflow: hidden;">
          <div
            v-for="(m, i) in recent_matches"
            :key="m.id"
            :style="{
              display: 'flex', alignItems: 'center', gap: '16px',
              padding: '14px 24px',
              borderTop: i > 0 ? '1px solid var(--line)' : 'none',
              flexWrap: 'wrap',
            }"
          >
            <!-- Opponent -->
            <div style="flex: 1; min-width: 120px;">
              <div style="font-size: 14px; font-weight: 600;">{{ m.opponent?.name ?? '—' }}</div>
              <div class="mono" style="font-size: 10px; color: var(--mute); margin-top: 2px; letter-spacing: 0.08em;">
                {{ m.competition?.name ?? '' }}
              </div>
            </div>

            <!-- Score -->
            <div class="mono" style="font-size: 18px; font-weight: 700; letter-spacing: 0.05em; min-width: 60px; text-align: center;">
              {{ m.my_score ?? '—' }} — {{ m.op_score ?? '—' }}
            </div>

            <!-- Result chip -->
            <div style="min-width: 90px; text-align: center;">
              <Chip v-if="m.result === 'win'" variant="felt">VICTOIRE</Chip>
              <Chip v-else-if="m.result === 'loss'" style="color: var(--live);">DÉFAITE</Chip>
              <Chip v-else-if="m.is_draw">NUL</Chip>
              <Chip v-else>—</Chip>
            </div>

            <!-- Phase/round -->
            <div class="mono" style="font-size: 10px; color: var(--mute); text-align: right; letter-spacing: 0.08em; text-transform: uppercase;">
              {{ m.phase ?? '' }}<span v-if="m.round"> · {{ m.round }}</span>
            </div>

            <!-- Date -->
            <div class="mono" style="font-size: 11px; color: var(--mute); white-space: nowrap;">
              {{ formatDate(m.ended_at) }}
            </div>
          </div>
        </div>
      </section>

    </div><!-- .container -->
  </div>
</template>
