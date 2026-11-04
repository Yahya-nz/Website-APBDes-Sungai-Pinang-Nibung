<?php

namespace App\Support;

use App\Models\Complaint;
use App\Models\Realization;
use App\Models\User;

/**
 * Isi lonceng notifikasi: pengeluaran terbaru untuk semua pengunjung,
 * ditambah pengaduan yang belum ditanggapi untuk perangkat desa.
 */
final class NotificationFeed
{
    /**
     * @return array{items: list<array<string, mixed>>, latest_id: string|null}
     */
    public static function for(?User $user): array
    {
        $items = Realization::query()->with('budgetLine:id,description,category')->latest('spent_on')->latest('id')->limit(8)->get()
            ->map(fn (Realization $item): array => [
                'id' => 'r'.$item->id,
                'kind' => 'realization',
                'title' => 'Pengeluaran baru: '.($item->budgetLine?->description ?? 'kegiatan'),
                'detail' => 'Rp'.number_format((float) $item->amount, 0, ',', '.').' · '.$item->description,
                'date' => $item->spent_on->toDateString(),
                'sort' => $item->spent_on->format('Ymd').sprintf('%08d', $item->id),
                'url' => route('realizations.index', ['year' => $item->spent_on->year]),
                'is_demo' => $item->is_demo,
            ]);

        if ($user?->can('manage-data')) {
            $complaints = Complaint::query()->where('status', 'new')->latest('id')->limit(5)->get()
                ->map(fn (Complaint $complaint): array => [
                    'id' => 'c'.$complaint->id,
                    'kind' => 'complaint',
                    'title' => Complaint::TOPICS[$complaint->topic].' baru '.$complaint->ticket,
                    'detail' => mb_strimwidth($complaint->message, 0, 90, '…'),
                    'date' => $complaint->created_at->toDateString(),
                    'sort' => $complaint->created_at->format('Ymd').sprintf('%08d', $complaint->id),
                    'url' => route('admin.complaints.index', ['status' => 'new']),
                    'is_demo' => $complaint->is_demo,
                ]);
            $items = $items->concat($complaints);
        }

        $items = $items->sortByDesc('sort')->values();

        return ['items' => $items->map(fn (array $item) => collect($item)->except('sort')->all())->all(), 'latest_id' => $items->first()['id'] ?? null];
    }
}
