<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\LicenseKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display a listing of the users for admin.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with(['wallets', 'licenses'])->get();

        $licenses = License::all();

        return Inertia::render('Admin/admin', [
            'users' => $users,
            'licenses' => $licenses,
        ]);
    }

    public function updateAdminStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->isAdmin = $request->isAdmin;
        $user->save();

        return redirect(route('admin'));
    }

    public function addUserLicense(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $licenseId = $request->licenseId;

        $user->licenses()->attach($licenseId);

        return redirect(route('admin'));
    }

    public function removeUserLicense(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $licenseId = $request->licenseId;

        $user->licenses()->detach($licenseId);

        return redirect(route('admin'));
    }

    public function createLicense(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'regex:/^[^\s]*$/'],
            'product' => ['required', 'string', 'regex:/^[^\s]*$/'],
            'years' => 'required|array',
            'years.*' => 'integer', // each member of years must be an integer
            'isBulk' => 'sometimes|boolean',
            'keyCount' => 'sometimes|integer|min:0'
        ]);

        $licenseInput = [
            'name' => $validated['name'],
            'product' => $validated['product'],
            'years' => $validated['years'],
        ];

        $license = License::create($licenseInput);

        if ($validated['isBulk'] === true) {
            $keyCount = $validated['keyCount'];
            for ($i = 0; $i < $keyCount; $i++) {
                LicenseKey::create([
                    'license_id' => $license->id,
                    // 'key' is generated in model's boot function
                ]);
            }
        }

        return redirect(route('admin'));
    }

    public function downloadLicenseKeysCSV($licenseId)
    {
        $license = License::with('keys')->findOrFail($licenseId);

        $headers = ['License Name', 'License Key', 'Used'];

        $rowNumber = 2; // Start at 2 to skip the header row

        $csvContent = $license->keys
            ->map(function ($key) use ($license, &$rowNumber) {
                $values = [
                    $license->name,
                    $key->key,
                    $key->usage_count,
                ];

                $rowNumber++;
                return implode(',', $values);
            })
            ->prepend(implode(',', $headers))  // Add headers at the beginning
            ->implode("\n");  // Each item in a new line

        // Return a streamed download response
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'license_keys.csv', [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="license_keys.csv"',
            ]);
    }

    // NOTE: This function isn't surfaced anywhere in the UI yet I do
    // not see a reason to actually delete license outside of testing
    // public function deleteLicense($licenseId)
    // {
    //     $license = License::find($licenseId);

    //     // TODO: If license is a bulk license also delete keys

    //     if ($license) {
    //         $license->delete();
    //     }

    //     return redirect(route('admin'));
    // }

    public function deleteUser($userId)
    {
        $user = User::find($userId);

        if ($user) {
            $user->delete();
        }
    }

    public function updateUserPassword(Request $request, $userId)
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults()],
        ]);

        $user = User::find($userId);
        if ($user) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        } else {
            throw ValidationException::withMessages([
                'user' => "Could not find user with id: ".$userId,
            ]);
        }
    }

    public function impersonateUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            Auth::login($user);
        } else {
            throw ValidationException::withMessages([
                'user' => "Could not find user with id: ".$userId,
            ]);
        }

        return redirect(route('profile.edit'));
    }
}
