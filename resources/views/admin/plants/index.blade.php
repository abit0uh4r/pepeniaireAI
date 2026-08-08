<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Espace gérant</p>
                <h2 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Catalogue vivant</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Gardez les caractéristiques et le stock qui alimenteront les futurs conseils.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.plants.archived') }}" class="inline-flex items-center justify-center rounded-full border border-emerald-900/15 bg-white px-5 py-3 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Voir les archives
                </a>
                <a href="{{ route('admin.plants.create') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    <span class="mr-2 text-lg leading-none">+</span> Ajouter une plante
                </a>
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
                <form method="GET" action="{{ route('admin.plants.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_180px_auto] md:items-end">
                    <div>
                        <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Rechercher</label>
                        <input id="search" name="search" value="{{ request('search') }}" type="search" placeholder="Nom ou espèce" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label for="status" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">État</label>
                        <select id="status" name="status" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Tous</option>
                            <option value="active" @selected($status === 'active')>Actives</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactives</option>
                        </select>
                    </div>
                    <div>
                        <label for="stock" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Disponibilité</label>
                        <select id="stock" name="stock" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Toutes</option>
                            <option value="available" @selected($stock === 'available')>En stock</option>
                            <option value="out" @selected($stock === 'out')>Rupture</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Filtrer</button>
                        <a href="{{ route('admin.plants.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Effacer</a>
                    </div>
                </form>
            </section>

            @if ($plants->isEmpty())
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-4xl">🌱</p>
                    <h3 class="mt-4 text-xl font-semibold text-slate-900">Aucune plante trouvée</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Ajoutez votre première plante ou modifiez les filtres pour retrouver une fiche existante.</p>
                </section>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($plants as $plant)
                        <article class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between bg-gradient-to-br from-emerald-950 to-emerald-800 p-5 text-white">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">{{ $plant->species }}</p>
                                    <h3 class="mt-2 text-xl font-semibold tracking-tight">{{ $plant->name }}</h3>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $plant->is_active ? 'bg-emerald-200 text-emerald-950' : 'bg-white/15 text-white' }}">
                                    {{ $plant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="min-h-12 text-sm leading-6 text-slate-600">{{ $plant->description ?: 'Aucune description renseignée.' }}</p>
                                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Stock</dt><dd class="mt-1 font-semibold {{ $plant->stock_quantity > 0 ? 'text-emerald-800' : 'text-amber-700' }}">{{ $plant->stock_quantity }} unité(s)</dd></div>
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Prix</dt><dd class="mt-1 font-semibold text-slate-900">{{ \App\Support\MoneyFormatter::formatMad($plant->price) }}</dd></div>
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Environnement</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->environment->label() }}</dd></div>
                                    <div class="rounded-2xl bg-[#f5f7f1] p-3"><dt class="text-xs text-slate-500">Exposition</dt><dd class="mt-1 font-semibold text-slate-900">{{ $plant->exposure->label() }}</dd></div>
                                </dl>
                                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                    <a href="{{ route('admin.plants.edit', $plant) }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">Modifier <span aria-hidden="true">→</span></a>
                                    @if ($plant->is_active)
                                        <form method="POST" action="{{ route('admin.plants.deactivate', $plant) }}" onsubmit="return confirm('Désactiver cette plante du catalogue ?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-amber-700">Désactiver</button>
                                        </form>
                                    @else
                                        <span class="text-xs font-medium text-slate-400">Non proposée</span>
                                    @endif
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
