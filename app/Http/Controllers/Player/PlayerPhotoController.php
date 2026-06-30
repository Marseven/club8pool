<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\PlayerPhotoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerPhotoController extends Controller
{
    public function store(PlayerPhotoRequest $request): RedirectResponse
    {
        $player = Auth::guard('player')->user();

        // Delete old photo
        if ($player->profile_photo_path) {
            Storage::disk('public')->delete($player->profile_photo_path);
        }

        // Store with random filename (never use original filename)
        $ext = $request->file('photo')->extension();
        $path = $request->file('photo')->storeAs(
            'player-photos',
            Str::uuid() . '.' . $ext,
            'public'
        );

        $player->update(['profile_photo_path' => $path]);

        return back()->with('success', 'Photo de profil mise à jour.');
    }
}
