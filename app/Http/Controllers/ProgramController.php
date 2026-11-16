<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Program;
use App\Models\ProgramVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rencana program desa: dikelola perangkat desa, dilihat dan dinilai warga lewat voting.
 */
class ProgramController extends Controller
{
    /** Cookie berisi kunci acak pemilih; dipakai agar satu perangkat hanya punya satu suara per program. */
    private const VOTER_COOKIE = 'pemilih_program';

    /**
     * Halaman publik daftar rencana program beserta hasil voting.
     */
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), array_keys(Program::STATUSES), true) ? $request->query('status') : null;
        $programs = Program::query()->withVoteCounts()->when($status, fn ($query) => $query->where('status', $status))->orderByDesc('year')->latest('id')->paginate(10)->withQueryString();
        $voterKey = $this->voterKey($request);

        return view('programs.index', [
            'programs' => $programs,
            'myVotes' => $voterKey ? ProgramVote::query()->where('voter_key', $voterKey)->whereIn('program_id', $programs->pluck('id'))->pluck('choice', 'program_id') : collect(),
            'counts' => Program::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
            'payload' => ['year' => $this->latestYear()],
        ]);
    }

    /**
     * Menyimpan atau mengubah suara warga. Tidak perlu login; satu perangkat satu suara per program.
     */
    public function vote(Request $request, Program $program): RedirectResponse
    {
        $choice = $request->validate(['choice' => ['required', Rule::in(array_keys(Program::CHOICES))]], [], ['choice' => 'pilihan'])['choice'];
        $back = route('programs.index', array_filter(['status' => is_string($request->input('status_filter')) ? $request->input('status_filter') : null])).'#program-'.$program->id;
        if (! $program->isVotingOpen()) {
            return redirect()->to($back)->withErrors(['choice' => 'Voting untuk program ini sudah ditutup.']);
        }

        $token = $request->cookie(self::VOTER_COOKIE);
        if (! is_string($token) || strlen($token) < 32) {
            $token = Str::random(40);
        }
        Cookie::queue(self::VOTER_COOKIE, $token, 60 * 24 * 365 * 2);

        $vote = ProgramVote::query()->firstOrNew(['program_id' => $program->id, 'voter_key' => hash('sha256', $token)]);
        $changed = $vote->exists && $vote->choice !== $choice;
        $vote->choice = $choice;
        $vote->save();

        $label = Program::CHOICES[$choice];
        $message = $changed ? "Pilihan Anda diubah menjadi {$label}." : "Terima kasih, suara Anda ({$label}) untuk program “{$program->title}” sudah tercatat.";

        return redirect()->to($back)->with('success', $message);
    }

    /**
     * Daftar program untuk perangkat desa.
     */
    public function manage(Request $request): View
    {
        return view('admin.programs.index', [
            'programs' => Program::query()->withVoteCounts()->orderByDesc('year')->latest('id')->paginate(15)->withQueryString(),
            'payload' => ['year' => $this->latestYear()],
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.form', ['program' => new Program(['year' => $this->latestYear() + 1, 'status' => 'proposed', 'voting_open' => true]), 'categories' => $this->categories(), 'payload' => ['year' => $this->latestYear()]]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        $program = DB::transaction(function () use ($request): Program {
            $program = Program::create($this->data($request) + ['user_id' => $request->user()->id]);
            $this->audit($request, 'Menambahkan rencana program '.$program->title, $program->year);

            return $program;
        });

        return redirect()->route('admin.programs.index')->with('success', "Rencana program “{$program->title}” ditambahkan dan langsung tampil untuk warga.");
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.form', ['program' => $program, 'categories' => $this->categories(), 'payload' => ['year' => $this->latestYear()]]);
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        DB::transaction(function () use ($request, $program): void {
            $program->update($this->data($request));
            $this->audit($request, 'Memperbarui rencana program '.$program->title, $program->year);
        });

        return redirect()->route('admin.programs.index')->with('success', "Perubahan program “{$program->title}” disimpan.");
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        DB::transaction(function () use ($request, $program): void {
            $this->audit($request, 'Menghapus rencana program '.$program->title.' beserta '.$program->votes()->count().' suara warga', $program->year);
            $program->delete();
        });

        return redirect()->route('admin.programs.index')->with('success', "Program “{$program->title}” dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function data(ProgramRequest $request): array
    {
        return $request->safe()->except('voting_open') + ['voting_open' => $request->boolean('voting_open')];
    }

    private function voterKey(Request $request): ?string
    {
        $token = $request->cookie(self::VOTER_COOKIE);

        return is_string($token) && strlen($token) >= 32 ? hash('sha256', $token) : null;
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        return BudgetLine::query()->where('type', 'expense')->distinct()->orderBy('category')->pluck('category')->all();
    }

    private function audit(Request $request, string $description, ?int $year): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'year' => $year, 'action' => 'program', 'description' => mb_substr($description, 0, 500)]);
    }

    private function latestYear(): int
    {
        return (int) (BudgetLine::max('year') ?? now()->year);
    }
}
