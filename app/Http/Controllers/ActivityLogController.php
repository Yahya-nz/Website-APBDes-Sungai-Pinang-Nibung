<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Riwayat lengkap perubahan data: siapa mengubah apa, dan kapan.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = in_array($request->query('aksi'), array_keys(ActivityLog::ACTIONS), true) ? $request->query('aksi') : null;

        return view('admin.activities', [
            'logs' => ActivityLog::query()->with('user:id,name')->when($action, fn ($query) => $query->where('action', $action))->latest('id')->paginate(25)->withQueryString(),
            'counts' => ActivityLog::query()->selectRaw('action, count(*) as total')->groupBy('action')->pluck('total', 'action'),
            'action' => $action,
            'payload' => ['year' => (int) (BudgetLine::max('year') ?? now()->year)],
        ]);
    }
}
