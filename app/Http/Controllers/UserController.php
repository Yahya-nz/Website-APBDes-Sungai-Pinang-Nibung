<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Hak akses pengelola: akun perangkat desa yang boleh mengimpor dan memperbarui data.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->addSelect(['last_activity_at' => ActivityLog::query()->select('created_at')->whereColumn('user_id', 'users.id')->latest('id')->limit(1)])
            ->withCasts(['last_activity_at' => 'datetime'])
            ->orderByDesc('is_admin')->orderBy('name')
            ->get();

        return view('admin.users', ['users' => $users, 'payload' => ['year' => (int) (BudgetLine::max('year') ?? now()->year)]]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill(['is_admin' => true])->save();
            $this->audit($request, 'Menambahkan akun pengelola '.$user->name.' ('.$user->email.')');

            return $user;
        });

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} dibuat. Sampaikan kata sandi awalnya secara langsung dan minta diganti di menu Pengaturan.");
    }

    /**
     * Mencabut atau memulihkan akses pengelola. Akun tidak dihapus agar riwayat aktivitasnya tetap terbaca.
     */
    public function access(Request $request, User $user): RedirectResponse
    {
        $grant = $request->validate(['access' => ['required', 'boolean']])['access'];
        $grant = filter_var($grant, FILTER_VALIDATE_BOOLEAN);

        if (! $grant && $user->is($request->user())) {
            throw ValidationException::withMessages(['access' => 'Anda tidak dapat mencabut akses akun Anda sendiri.']);
        }

        DB::transaction(function () use ($request, $user, $grant): void {
            $user->forceFill(['is_admin' => $grant, 'remember_token' => Str::random(60)])->save();
            $this->audit($request, ($grant ? 'Memulihkan' : 'Mencabut').' akses pengelola '.$user->name);
        });

        return redirect()->route('admin.users.index')->with('success', $grant ? "Akses pengelola {$user->name} dipulihkan." : "Akses pengelola {$user->name} dicabut. Akun ini tidak bisa lagi masuk ke halaman pengelola.");
    }

    private function audit(Request $request, string $description): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'user', 'description' => mb_substr($description, 0, 500)]);
    }
}
