<?php

namespace App\Services;

use App\Http\Requests\BudgetLineRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;
use ZipArchive;

class BudgetImporter
{
    public const HEADERS = ['year', 'type', 'category', 'description', 'amount', 'source', 'verification_status', 'source_date', 'realization'];

    public function import(UploadedFile $file, array $defaults, User $user): int
    {
        $rows = $this->read($file);
        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'Berkas harus berisi header dan minimal satu baris data.']);
        }
        $headers = array_map(fn ($cell) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell))), array_shift($rows));
        foreach (['year', 'type', 'category', 'description', 'amount'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw ValidationException::withMessages(['file' => "Kolom wajib tidak ditemukan: {$required}. Gunakan template yang disediakan."]);
            }
        }
        if (count(array_unique($headers)) !== count($headers)) {
            throw ValidationException::withMessages(['file' => 'Header kolom tidak boleh duplikat.']);
        }
        $valid = [];
        $seen = [];
        foreach ($rows as $index => $cells) {
            if (collect($cells)->every(fn ($cell) => $cell === null || trim((string) $cell) === '')) {
                continue;
            }
            $data = [];
            foreach ($headers as $column => $header) {
                if (in_array($header, self::HEADERS, true)) {
                    $data[$header] = isset($cells[$column]) ? trim((string) $cells[$column]) : null;
                }
            }
            foreach (['year', 'source', 'source_date'] as $key) {
                if (empty($data[$key])) {
                    $data[$key] = $defaults[$key] ?? null;
                }
            }
            $data['verification_status'] = $data['verification_status'] ?? 'unverified';
            if ($data['verification_status'] === '') {
                $data['verification_status'] = 'unverified';
            }
            foreach (['realization', 'source_date'] as $key) {
                if (empty($data[$key]) && ($data[$key] ?? null) !== '0') {
                    $data[$key] = null;
                }
            }
            $validator = Validator::make($data, BudgetLineRequest::budgetRules());
            if ($validator->fails()) {
                throw ValidationException::withMessages(['file' => 'Baris '.($index + 2).': '.$validator->errors()->first()]);
            }
            if (isset($defaults['year']) && (int) $data['year'] !== (int) $defaults['year']) {
                throw ValidationException::withMessages(['file' => 'Baris '.($index + 2).': tahun anggaran '.$data['year'].' tidak sesuai dengan tahun yang dipilih pada formulir ('.$defaults['year'].'). Samakan tahun pada berkas atau ubah pilihan tahun sebelum impor.']);
            }
            $key = mb_strtolower(implode('|', [$data['year'], $data['type'], $data['category'], $data['description']]));
            if (isset($seen[$key]) || BudgetLine::where('year', $data['year'])->where('type', $data['type'])->where('category', $data['category'])->where('description', $data['description'])->exists()) {
                throw ValidationException::withMessages(['file' => 'Baris '.($index + 2).': data duplikat. Ubah data yang sudah ada melalui menu edit agar total tidak terhitung dua kali.']);
            }
            $seen[$key] = true;
            $valid[] = $validator->validated();
        }
        if (count($valid) === 0) {
            throw ValidationException::withMessages(['file' => 'Tidak ada baris data yang dapat diimpor.']);
        }
        DB::transaction(function () use ($valid, $user, $defaults, $file): void {
            $isDemo = filter_var($defaults['is_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $groups = collect($valid)->groupBy('year');
            foreach ($groups as $year => $lines) {
                $dataset = Dataset::create(['name' => mb_substr($file->getClientOriginalName(), 0, 255), 'year' => $year, 'source' => $defaults['source'] ?? $lines->first()['source'], 'source_date' => $defaults['source_date'] ?? null, 'is_demo' => $isDemo, 'user_id' => $user->id]);
                foreach ($lines as $line) {
                    BudgetLine::create($line + ['dataset_id' => $dataset->id, 'is_demo' => $isDemo]);
                }
                ActivityLog::create(['user_id' => $user->id, 'year' => $year, 'action' => 'import', 'description' => 'Mengimpor '.$lines->count().' baris data dari '.$dataset->name]);
            }
        });

        return count($valid);
    }

    private function read(UploadedFile $file): array
    {
        try {
            if (mb_strtolower($file->getClientOriginalExtension()) === 'xlsx') {
                $zip = new ZipArchive;
                if ($zip->open($file->getRealPath()) !== true) {
                    throw ValidationException::withMessages(['file' => 'Berkas Excel tidak valid.']);
                }
                $expanded = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $expanded += (int) $zip->statIndex($i)['size'];
                }
                $zip->close();
                if ($expanded > 15000000) {
                    throw ValidationException::withMessages(['file' => 'Isi Excel terlalu besar. Maksimal 15 MB setelah diekstrak.']);
                }
                $reader = new Xlsx;
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);
                $book = $reader->load($file->getRealPath());
                $sheet = $book->getSheet(0);
                if ($sheet->getHighestDataRow() > 5001 || Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 26) {
                    $book->disconnectWorksheets();
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris dan 26 kolom per impor.']);
                }
                $rows = $sheet->toArray(null, false, false, false);
                $book->disconnectWorksheets();

                return $rows;
            }
            $handle = fopen($file->getRealPath(), 'r');
            $first = fgets($handle);
            rewind($handle);
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $rows[] = $row;
                if (count($rows) > 5001) {
                    fclose($handle);
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris per impor.']);
                }
            }
            fclose($handle);

            return $rows;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'Berkas tidak dapat dibaca. Gunakan template CSV atau Excel (.xlsx) yang valid.']);
        }
    }
}
