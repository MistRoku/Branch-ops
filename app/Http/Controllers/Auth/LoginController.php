<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * LoginController
 *
 * Handles user authentication including login form display,
 * authentication processing, and logout functionality.
 */
class LoginController extends Controller
{
    /**
     * Show the login form.
     *
     * @return View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @return RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate the form data
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Reject locked accounts before attempting auth
        if ($user && $user->isLocked()) {
            return back()->withErrors([
                'email' => 'Account locked until '.$user->locked_until->format('H:i').'. Try again later.',
            ])->onlyInput('email');
        }

        // Reject deactivated accounts
        if ($user && ! $user->is_active) {
            return back()->withErrors([
                'email' => 'This account has been deactivated.',
            ])->onlyInput('email');
        }

        // Attempt to authenticate the user
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();
            $request->user()->resetLoginAttempts();

            // Redirect to POS terminal (default landing page)
            return redirect()->intended(route('pos.terminal'));
        }

        // Record failed attempt for lockout tracking
        $user?->recordFailedLogin();

        // Authentication failed - redirect back with error
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate the session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
