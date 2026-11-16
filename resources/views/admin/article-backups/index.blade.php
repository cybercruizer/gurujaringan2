<x-admin-layout>
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="flex flex-col gap-2">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Administrasi</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Backup artikel</h1>
            <p class="leading-7 text-stone-600">Ekspor artikel beserta kategori, tag, komentar, dan gambar yang digunakan. File ZIP dapat diimpor kembali untuk memulihkan konten.</p>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-stone-950">Ekspor artikel</h2>
            <p class="text-sm leading-6 text-stone-600">Backup mencakup artikel terbit maupun draf, status SEO, kategori, tag, seluruh komentar beserta status moderasi, serta file gambar yang dirujuk artikel.</p>
            <a href="{{ route('admin.article-backups.export') }}" class="w-fit rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">Unduh backup ZIP</a>
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-stone-950">Impor atau pulihkan</h2>
            <p class="text-sm leading-6 text-stone-600">Artikel dengan slug yang sama akan diperbarui. Komentar artikel tersebut akan diganti dengan komentar dari backup; kategori dan tag akan disesuaikan dengan backup.</p>
            <p class="text-sm leading-6 text-amber-800">Peringatan: komentar baru pada artikel yang cocok akan dihapus saat pemulihan. Maksimum ukuran arsip adalah 100 MB.</p>
            <form method="POST" action="{{ route('admin.article-backups.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                @csrf
                <label for="backup" class="text-sm font-semibold text-stone-800">Pilih file backup ZIP</label>
                <input id="backup" name="backup" type="file" accept=".zip,application/zip" required class="block w-full text-sm text-stone-600 file:mr-4 file:rounded-full file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
                @error('backup')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-fit rounded-full border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-100">Impor dan pulihkan artikel</button>
            </form>
        </section>
    </section>
</x-admin-layout>
