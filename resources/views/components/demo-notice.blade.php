<div {{ $attributes->merge(['class' => 'demo-notice']) }} role="note">
    <x-icon name="flask-conical" />
    <p><strong>Data contoh.</strong> {{ $slot->isEmpty() ? 'Angka pada tahun ini adalah simulasi untuk uji coba aplikasi, bukan APBDes resmi desa.' : $slot }}</p>
</div>
