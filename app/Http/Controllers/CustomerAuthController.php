<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('account.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique('customers', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => __('Enter a valid phone number (digits only, 7–15 characters, optional leading +).'),
            'phone.unique' => __('An account with this phone number already exists. Try logging in.'),
        ]);

        $customer = Customer::create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'password' => $data['password'],
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account.index')
            ->with('success', __('Welcome, :name! Your account is ready.', ['name' => $customer->name]));
    }

    public function showLogin()
    {
        return view('account.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $ok = Auth::guard('customer')->attempt([
            'phone' => trim($credentials['phone']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $ok) {
            throw ValidationException::withMessages([
                'phone' => __('Those credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('menu.index');
    }
}
