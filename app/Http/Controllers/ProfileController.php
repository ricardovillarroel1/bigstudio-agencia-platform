<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's billing information.
     */
    public function updateBilling(Request $request): RedirectResponse
    {
        $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'giro' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:500'],
        ]);

        Cliente::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'razon_social' => $request->razon_social,
                'empresa' => $request->razon_social,
                'rut' => $request->rut,
                'giro' => $request->giro,
                'direccion' => $request->direccion,
                'estado' => 'activo',
            ]
        );

        $request->user()->update(['datos_facturacion_completos' => true]);

        return Redirect::route('profile.edit')->with('status', 'billing-updated');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('avatars', 'public');

        $user->forceFill(['profile_photo_path' => $path])->save();

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    /**
     * Remove the user's profile photo.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }

        return Redirect::route('profile.edit')->with('status', 'photo-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
