<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">Espace gérant</p>
                <h2 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Plantes archivées</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Retrouvez les fiches retirées du catalogue sans perdre leur historique.</p>
            </div>
            <a href="{{ route('admin.plants.index') }}" class="inline-flex items-center justify-center rounded-full border border-emerald-900/15 bg-white px-5 py-3 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                Retour au catalogue
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#f5f7f1] py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('admin.plants.archived') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Rechercher une fiche archivée</label>
                        <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Nom ou espèce" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Rechercher</button>
                    <a href="{{ route('admin.plants.archived') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Effacer</a>
                </form>
            </section>

            @if ($plants->isEmpty())
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-4xl" aria-hidden="true">🌱</p>
                    <h3 class="mt-4 text-xl font-semibold text-slate-900">Aucune plante archivée</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Les fiches archivées apparaîtront ici et pourront être restaurées.</p>
                </section>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($plants as $plant)
                        <article class="flex flex-col overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">
                            <div class="flex items-start justify-between bg-gradient-to-br from-amber-950 to-amber-800 p-5 text-white">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">{{ $plant->species }}</p>
                                    <h3 class="mt-2 text-xl font-semibold tracking-tight">{{ $plant->name }}</h3>
                                </div>
                                <span class="rounded-full bg-amber-200 px-3 py-1 text-xs font-semibold text-amber-950">Archivée</span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="min-h-12 text-sm leading-6 text-slate-600">{{ $plant->description ?: 'Aucune description renseignée.' }}</p>
                                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Stock conservé</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->stock_quantity }} unité(s)</dd></div>
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold text-slate-900">{{ \App\Support\MoneyFormatter::formatMad($plant->price) }}</dd></div>
                                </dl>
                                <p class="mt-4 text-xs text-slate-500">Archivée le {{ $plant->deleted_at?->format('d/m/Y à H:i') }}</p>
                                <div class="mt-5 border-t border-slate-100 pt-4">
                                    <p class="mb-3 text-xs leading-5 text-slate-500">La restauration enlève l’archive, mais la fiche restera inactive jusqu’à sa réactivation.</p>
                                    <form method="POST" action="{{ route('admin.plants.restore', $plant) }}" onsubmit="return confirm('Restaurer cette plante dans le catalogue ?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Restaurer la fiche</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div>{{ $plants->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
