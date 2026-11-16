@extends('layouts.app')
@section('title', $program->exists ? 'Ubah Program' : 'Tambah Program')
@section('heading', $program->exists ? 'Ubah Rencana Program' : 'Tambah Rencana Program')
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.programs.index') }}">Rencana program</a><span>/</span><span>{{ $program->exists ? 'Ubah' : 'Tambah' }}</span></nav>
        <h1>{{ $program->exists ? 'Ubah Rencana Program' : 'Tambah Rencana Program' }}</h1>
        <p>Tuliskan program dengan bahasa yang mudah dipahami warga, karena warga akan memberi suara setuju atau tidak setuju.</p>
    </div>
</div>
<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <form class="card-body form-grid" method="POST" action="{{ $program->exists ? route('admin.programs.update', $program) : route('admin.programs.store') }}" style="padding-top: 20px">
            @csrf
            @if($program->exists) @method('PATCH') @endif
            <div class="field span-2"><label for="program-title">Nama program</label><input class="input" id="program-title" name="title" maxlength="150" value="{{ old('title', $program->title) }}" required placeholder="Misalnya: Pembangunan jalan rabat beton Dusun III">@error('title')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field span-2"><label for="program-description">Uraian</label><textarea class="textarea" id="program-description" name="description" minlength="15" maxlength="2000" required placeholder="Apa yang akan dikerjakan, untuk siapa, dan manfaatnya">{{ old('description', $program->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="program-category">Bidang <span class="optional">opsional</span></label><input class="input" id="program-category" name="category" list="program-categories" maxlength="150" value="{{ old('category', $program->category) }}"><datalist id="program-categories">@foreach($categories as $category)<option value="{{ $category }}">@endforeach</datalist></div>
            <div class="field"><label for="program-location">Lokasi <span class="optional">opsional</span></label><input class="input" id="program-location" name="location" maxlength="150" value="{{ old('location', $program->location) }}" placeholder="Misalnya: Dusun III"></div>
            <div class="field"><label for="program-year">Tahun rencana</label><input class="input" id="program-year" type="number" name="year" min="2000" max="2100" value="{{ old('year', $program->year) }}" required>@error('year')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="program-budget">Perkiraan anggaran (Rp) <span class="optional">opsional</span></label><input class="input" id="program-budget" type="number" name="estimated_budget" min="0" step="0.01" inputmode="decimal" value="{{ old('estimated_budget', $program->estimated_budget) }}" data-money-preview><span class="money-preview" data-money-output></span>@error('estimated_budget')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="program-status">Status</label><select class="select" id="program-status" name="status">@foreach(\App\Models\Program::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $program->status) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="program-ends">Batas voting <span class="optional">opsional</span></label><input class="input" id="program-ends" type="date" name="voting_ends_at" value="{{ old('voting_ends_at', $program->voting_ends_at?->toDateString()) }}">@error('voting_ends_at')<span class="field-error">{{ $message }}</span>@enderror</div>
            <label class="check span-2"><input type="checkbox" name="voting_open" value="1" @checked(old('voting_open', $program->exists ? $program->voting_open : true))><span>Buka voting untuk warga</span></label>
            <div class="form-actions span-2"><a class="btn btn-secondary" href="{{ route('admin.programs.index') }}">Batal</a><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan program</button></div>
        </form>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Tentang voting</h2></div></header>
        <div class="card-body prose">
            <p>Warga memilih <strong>Setuju</strong> atau <strong>Tidak setuju</strong> tanpa login. Satu perangkat satu suara per program, dan pilihannya bisa diubah selama voting dibuka.</p>
            <p>Hasil ditampilkan sebagai jumlah suara dan persentase. Tutup voting dengan menghapus centang, atau isi batas voting agar tertutup otomatis setelah tanggal tersebut.</p>
            @if($program->exists)<p>Saat ini: <strong>{{ $program->results()['total'] }} suara</strong> tercatat.</p>@endif
        </div>
    </article>
</section>
@endsection
