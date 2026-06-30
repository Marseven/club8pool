<?php

namespace App\Services;

use App\Models\User;

class RefereeLoginResolver
{
    /**
     * Normalize a login identifier for comparison.
     */
    public function normalize(string $input): string
    {
        $s = trim($input);
        $s = mb_strtolower($s);
        // Remove accents
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        // Collapse multiple spaces
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /**
     * Resolve a referee (User) from a login identifier.
     * Returns the User if exactly one active referee matches, null otherwise.
     * Sets $error message on ambiguity or not found.
     */
    public function resolve(string $input, ?string &$error = null): ?User
    {
        $normalized = $this->normalize($input);

        if (empty($normalized)) {
            $error = 'Identifiant requis.';
            return null;
        }

        // 1. Exact match on login_slug
        $bySlug = User::where('login_slug', $normalized)
            ->where('role', 'referee')
            ->where('is_referee_active', true)
            ->get();
        if ($bySlug->count() === 1) {
            return $bySlug->first();
        }

        // 2. Exact match on name (normalized)
        $byName = User::whereRaw('LOWER(name) = ?', [$normalized])
            ->where('role', 'referee')
            ->where('is_referee_active', true)
            ->get();
        if ($byName->count() === 1) {
            return $byName->first();
        }
        if ($byName->count() > 1) {
            $error = 'Plusieurs arbitres correspondent à cet identifiant. Contactez l\'organisation pour obtenir votre identifiant exact.';
            return null;
        }

        $error = 'Identifiant introuvable ou compte arbitre inactif. Vérifiez votre identifiant ou contactez l\'organisation.';
        return null;
    }
}
