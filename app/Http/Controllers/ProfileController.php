<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Wallet;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'wallets' => $this->getWallets($request),
            'licenses' => $this->getLicenses($request),
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

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Get all the user's wallets
     */
    public function getWallets(Request $request)
    {
        $wallets = Wallet::with('user:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()->get();

        return $wallets;
    }

    /**
     * Add a wallet to a user
     */
    public function addWallet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
        ]);

        try {
            $request->user()->wallets()->create($validated);
        } catch (UniqueConstraintViolationException $e) {
            // handle the exception
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete a wallet from a user
     */
    public function deleteWallet(Wallet $wallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);

        $wallet->delete();

        return Redirect::route('profile.edit');
    }

    public function getLicenses(Request $request)
    {
        $user = $request->user()->load('licenses');

        return $user->licenses;
    }

    /**
     * Add a license to a user
     */
    public function addLicense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'key' => 'required|string',
        ]);

        $license = License::where('name', $validated['name'])->first();

        if (!$license) {
            throw ValidationException::withMessages([
                'name' => "Invalid license name. Try again or verify with your purchase receipt or an admin.",
            ]);
        }

        $licenseValid = false;
        if (str_starts_with($license->product, 'bulk__')) {
            $licenseValid = $this->isValidBulkKey($license, $validated['key']);
        } else {
            $licenseValid = $this->isValidGumroadKey($license, $validated['key']);
        }

        if (!$licenseValid) {
            throw ValidationException::withMessages([
                'key' => "Invalid license key. Try again or verify with your purchase receipt or an admin.",
            ]);
        }

        // TODO: We should probably save the license key to the user profile so we can re-validate on login
        // Probably just re-validate the refunded, disputed, and chargebacked fields on login to prevent
        // a user from registering the license then refunding it and being able to still use the app

        $request->user()->licenses()->syncWithoutDetaching([$license->id]);

        return Redirect::route('profile.edit');
    }

    private function isValidBulkKey(License $license, string $key): bool
    {
        $licenseKey = LicenseKey::where('license_id', $license->id)
            ->where('key', $key)
            ->first();

        if (!$licenseKey) {
            throw ValidationException::withMessages([
                'key' => "Invalid license key. Try again or verify with your purchase receipt or an admin.",
            ]);
        }

        $licenseValid = $licenseKey->usage_count === 0;

        $licenseKey->increment('usage_count');

        return $licenseValid;
    }

    private function isValidGumroadKey(License $license, string $key): bool
    {
        $token = config('auth.gumroad_api_token');

        $response = Http::acceptJson()->withToken($token)
        ->post('https://api.gumroad.com/v2/licenses/verify', [
            'product_id' => $license->product,
            'license_key' => $key,
        ]);

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'key' => "Unable to verify license key. Try again or verify with your purchase receipt or an admin.",
            ]);
        }

        $data = $response->json();

        $licenseValid = $data['success'] &&
            $data['uses'] == 1 &&
            !$data['purchase']['refunded'] &&
            !$data['purchase']['disputed'] &&
            !$data['purchase']['chargebacked'];

        return $licenseValid;
    }
}
