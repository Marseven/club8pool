<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Str;

class PlayerLoginResolver
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
     * Resolve a player from a login identifier.
     * Returns the Player if exactly one match, null otherwise.
     * Sets $error message on ambiguity or not found.
     */
    public function resolve(string $input, ?string &$error = null): ?Player
    {
        $normalized = $this->normalize($input);

        if (empty($normalized)) {
            $error = 'Identifiant requis.';
            return null;
        }

        // 1. Exact match on login_slug
        $bySlug = Player::where('login_slug', $normalized)->get();
        if ($bySlug->count() === 1) {
            return $bySlug->first();
        }

        // 2. Exact match on login_name (normalized)
        $byName = Player::whereRaw('LOWER(login_name) = ?', [$normalized])->get();
        if ($byName->count() === 1) {
            return $byName->first();
        }
        if ($byName->count() > 1) {
            $error = 'Plusieurs joueurs correspondent à cet identifiant. Contactez l\'organisation pour obtenir votre identifiant exact.';
            return null;
        }

        // 3. Exact match on first_name (fallback)
        $byFirst = Player::whereRaw('LOWER(first_name) = ?', [$normalized])
            ->whereNotNull('password')
            ->get();
        if ($byFirst->count() === 1) {
            return $byFirst->first();
        }
        if ($byFirst->count() > 1) {
            $error = 'Plusieurs joueurs ont ce prénom. Utilisez votre identifiant exact ou contactez l\'organisation.';
            return null;
        }

        $error = 'Identifiant introuvable. Vérifiez votre prénom ou contactez l\'organisation.';
        return null;
    }

    /**
     * Generate a unique login_slug for a player.
     */
    public function generateSlug(string $loginName): string
    {
        $base = Str::slug($loginName, '-');
        if (empty($base)) {
            $base = 'joueur';
        }

        $slug = $base;
        $counter = 2;
        while (Player::where('login_slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }
        return $slug;
    }
}
