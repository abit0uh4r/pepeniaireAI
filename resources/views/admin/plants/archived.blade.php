<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.plants.index') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Retour au catalogue</a>
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">Espace gérant</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Plantes archivées</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Retrouvez les fiches masquées du catalogue et restaurez-les sans perdre leur historique.</p>
            </div>
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
                        <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Rechercher une fiche</label>
                        <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Nom ou espèce" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Rechercher</button>
                        <a href="{{ route('admin.plants.archived') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Effacer</a>
                    </div>
                </form>
            </section>

            @forelse ($plants as $plant)
                <article class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 bg-amber-50 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Fiche archivée</p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">{{ $plant->name }}</h2>
                            <p class="mt-1 text-sm italic text-slate-500">{{ $plant->species }}</p>
                        </div>
                        <p class="text-xs font-medium text-amber-800">Archivée le {{ $plant->deleted_at?->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div class="p-5">
                        <p class="text-sm leading-6 text-slate-600">{{ $plant->description ?: 'Aucune description renseignée.' }}</p>
                        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-4">
                            <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Stock</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->stock_quantity }} unité(s)</dd></div>
                            <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold text-slate-900">{{ \App\Support\MoneyFormatter::formatMad($plant->price) }}</dd></div>
                            <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Environnement</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->environment->label() }}</dd></div>
                            <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Exposition</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->exposure->label() }}</dd></div>
                        </dl>
                        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">La restauration la remet active dans le catalogue. Elle restera éligible uniquement si son stock et ses critères sont compatibles.</p>
                            <form method="POST" action="{{ route('admin.plants.restore', $plant) }}" onsubmit="return confirm('Restaurer cette plante dans le catalogue ?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2 sm:w-auto">Restaurer</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-4xl">🌿</p>
                    <h2 class="mt-4 text-xl font-semibold text-slate-900">Aucune plante archivée</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Les plantes archivées apparaîtront ici sans être supprimées définitivement.</p>
                </section>
            @endforelse

            @if ($plants->hasPages())
                <div>{{ $plants->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
