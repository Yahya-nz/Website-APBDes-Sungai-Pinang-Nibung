<?php

namespace App\Http\Controllers;

use App\Http\Requests\DatasetRequest;
use App\Models\ActivityLog;
use App\Models\Dataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat dataset pada halaman pengelolaan data: memperbarui informasi sumber atau membatalkan impor.
 */
class DatasetController extends Controller
{
    public function update(DatasetRequest $request, Dataset $dataset): RedirectResponse
    {
        $updatedLines = DB::transaction(function () use ($request, $dataset): int {
            $data = $request->safe()->only(['name', 'source', 'source_date']);
            $dataset->update($data);
            $updatedLines = $request->boolean('apply_to_lines') ? $dataset->budgetLines()->update(['source' => $data['source'], 'source_date' => $data['source_date']]) : 0;
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'year' => $dataset->year,
                'action' => 'dataset',
                'description' => mb_substr('Memperbarui sumber dataset '.$dataset->name.($updatedLines > 0 ? " dan {$updatedLines} baris anggarannya" : ''), 0, 500),
            ]);

            return $updatedLines;
        });

        $message = 'Informasi sumber dataset disimpan'.($updatedLines > 0 ? " dan diterapkan ke {$updatedLines} baris anggaran." : '.');

        return redirect()->route('admin.budgets.index', ['year' => $dataset->year])->with('success', $message);
    }

    /**
     * Menghapus dataset beserta baris anggarannya, misalnya untuk membatalkan impor berkas yang salah.
     * Catatan realisasi pada baris tersebut ikut terhapus; riwayat aktivitas tetap menyimpan jejaknya.
     */
    public function destroy(Request $request, Dataset $dataset): RedirectResponse
    {
        $deletedLines = DB::transaction(function () use ($request, $dataset): int {
            $deletedLines = $dataset->budgetLines()->delete();
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'year' => $dataset->year,
                'action' => 'delete',
                'description' => mb_substr("Menghapus dataset {$dataset->name} beserta {$deletedLines} baris anggarannya", 0, 500),
            ]);
            $dataset->delete();

            return $deletedLines;
        });

        return redirect()->route('admin.budgets.index', ['year' => $dataset->year])->with('success', "Dataset {$dataset->name} dan {$deletedLines} baris anggarannya dihapus.");
    }
}
