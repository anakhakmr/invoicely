<?php

namespace App\Livewire\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutClient
{
    /**
     * Log the current client out of the application.
     */
    public function __invoke(): Redirector|RedirectResponse
    {
        Auth::guard('client')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('client.login');
    }
}
