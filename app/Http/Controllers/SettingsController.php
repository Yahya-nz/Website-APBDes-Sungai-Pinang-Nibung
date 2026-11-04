<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.settings', ['user' => $request->user()]);
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['password' => $request->validated('password')])->save();
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'password', 'description' => 'Mengganti kata sandi akun']);

        return redirect()->route('admin.settings')->with('success', 'Kata sandi berhasil diganti.');
    }
}
