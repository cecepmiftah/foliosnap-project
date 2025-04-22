<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {

            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)->first();

            if (! $user) {
                // Check if the user is soft-deleted
                // and restore if necessary
                $user = User::withTrashed()
                    ->where('email', $googleUser->email)
                    ->first();

                if ($user) {
                    // Restore the user and update the google_id
                    // and deleted_at fields
                    $user->restore();
                    $user->update([
                        'google_id' => $googleUser->id,
                        'deleted_at' => null,
                    ]);
                    Auth::login($user);
                    return redirect()->intended('/');
                } else {
                    // If the user does not exist, create a new one
                    // Generate a unique username
                    $username = strtolower(str_replace(' ', '', $googleUser->name));

                    // Check if the username already exists
                    if (User::where('username', $username)->exists()) {
                        $username .= rand(1, 100);
                    }

                    $nameParts = explode(' ', $googleUser->getName(), 2);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? '';

                    $user = User::create([
                        'first_name' => $firstName ?? $googleUser->getName(),
                        'last_name' => $lastName,
                        'username' => $username,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'provider' => 'google',
                    ]);
                }
            }

            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {

            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'request_ip' => request()->ip(),
                'request_data' => request()->all(),
                'time' => now()->toDateTimeString(),
            ]);

            return redirect('/login')->withErrors(['googleError', 'Error Saat mencoba login dengan google']);
        }
    }
}
