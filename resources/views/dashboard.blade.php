<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Bonjour {{ auth()->user()->name }}</p>
                <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-5xl">Le jardin, en un regard.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Stock, demandes et recommandations : les signaux utiles pour piloter la pépinière aujourd’hui.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.advice-requests.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/15 bg-white px-5 py-3 text-sm font-bold text-emerald-950 transition hover:bg-emerald-50">Voir les demandes</a>
                <a href="{{ route('admin.plants.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">Ajouter une plante</a>
            </div>
        </div>
    </x-slot>

    <div class="botanical-grid min-h-[calc(100vh-12rem)] py-8">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicateurs">
                <article class="rounded-[2rem] border border-emerald-950/10 bg-emerald-950 p-6 text-white shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-300">Catalogue actif</p>
                    <p class="mt-8 font-display text-5xl font-semibold">{{ $activePlants }}</p>
                    <p class="mt-2 text-sm text-emerald-100">plantes proposées</p>
                </article>
                <article class="rounded-[2rem] border border-emerald-950/10 bg-lime-300 p-6 text-emerald-950 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-800">À surveiller</p>
                    <p class="mt-8 font-display text-5xl font-semibold">{{ $lowStockPlants }}</p>
                    <p class="mt-2 text-sm text-emerald-950/70">stocks entre 1 et 5</p>
                </article>
                <article class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">En mouvement</p>
                    <p class="mt-8 font-display text-5xl font-semibold text-emerald-950">{{ $pendingRequests }}</p>
                    <p class="mt-2 text-sm text-slate-600">demandes en attente</p>
                </article>
                <article class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Conseils rendus</p>
                    <p class="mt-8 font-display text-5xl font-semibold text-emerald-950">{{ $completedRequests }}</p>
                    <p class="mt-2 text-sm text-slate-600">demandes terminées</p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]">
                <div class="overflow-hidden rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] shadow-sm">
                    <div class="flex items-center justify-between border-b border-emerald-950/10 px-6 py-5 sm:px-8">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Flux récent</p>
                            <h2 class="mt-1 font-display text-2xl font-semibold text-emerald-950">Dernières demandes</h2>
                        </div>
                        <a href="{{ route('admin.advice-requests.index') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Tout voir →</a>
                    </div>
                    @if ($recentRequests->isEmpty())
                        <div class="px-6 py-14 text-center">
                            <p class="font-display text-2xl font-semibold text-emerald-950">Le carnet est encore vierge.</p>
                            <p class="mt-2 text-sm text-slate-500">Les nouvelles demandes apparaîtront ici.</p>
                        </div>
                    @else
                        <div class="divide-y divide-emerald-950/10">
                            @foreach ($recentRequests as $request)
                                <a href="{{ route('admin.advice-requests.show', $request) }}" class="grid gap-3 px-6 py-4 transition hover:bg-lime-50 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:px-8">
                                    <div>
                                        <p class="font-bold text-emerald-950">{{ $request->customer_name ?: 'Visiteur anonyme' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $request->environment->label() }} · {{ $request->exposure->label() }} · {{ $request->created_at->diffForHumans() }}</p>
                                    </div>
                                    <x-advice-status-badge :status="$request->status" />
                                    <span class="text-xs font-bold text-slate-500">{{ $request->recommendations_count }} choix</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="rounded-[2rem] bg-emerald-950 p-7 text-white shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-300">Principe cardinal</p>
                    <h2 class="mt-4 font-display text-3xl font-semibold leading-tight">Le catalogue décide. Le conseiller explique.</h2>
                    <p class="mt-4 text-sm leading-7 text-emerald-100">Aucune recommandation ne peut inventer une plante, contourner un stock nul ou modifier une quantité.</p>
                    <div class="mt-8 rounded-2xl bg-white/10 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-300">Parcours de démo</p>
                        <ol class="mt-4 space-y-3 text-sm text-emerald-50">
                            <li class="flex gap-3"><span class="font-bold text-lime-300">01</span> Vérifier le catalogue</li>
                            <li class="flex gap-3"><span class="font-bold text-lime-300">02</span> Soumettre un conseil public</li>
                            <li class="flex gap-3"><span class="font-bold text-lime-300">03</span> Observer le worker</li>
                            <li class="flex gap-3"><span class="font-bold text-lime-300">04</span> Auditer le résultat ici</li>
                        </ol>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
