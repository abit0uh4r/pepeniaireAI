<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Journal des conseils</p>
            <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-5xl">Demandes visiteurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Suivez chaque traitement, retrouvez les critères reçus et auditez les recommandations persistées.</p>
        </div>
    </x-slot>

    <div class="botanical-grid min-h-[calc(100vh-12rem)] py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-5 shadow-sm">
                <form method="GET" action="{{ route('admin.advice-requests.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
                    <div>
                        <label for="search" class="mb-2 block text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Visiteur</label>
                        <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Nom du visiteur" class="block w-full rounded-2xl border-emerald-950/15 bg-white px-4 py-3 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                    </div>
                    <div>
                        <label for="status" class="mb-2 block text-xs font-bold uppercase tracking-[0.15em] text-slate-500">État du traitement</label>
                        <select id="status" name="status" class="block w-full rounded-2xl border-emerald-950/15 bg-white px-4 py-3 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Tous les états</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-emerald-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">Filtrer</button>
                        <a href="{{ route('admin.advice-requests.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/15 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-50">Effacer</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] shadow-sm">
                <div class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_160px_110px_32px] gap-4 border-b border-emerald-950/10 bg-emerald-950 px-7 py-4 text-xs font-bold uppercase tracking-[0.14em] text-emerald-200 md:grid">
                    <span>Demande</span>
                    <span>Contexte</span>
                    <span>État</span>
                    <span>Résultat</span>
                    <span></span>
                </div>

                @forelse ($adviceRequests as $request)
                    <a href="{{ route('admin.advice-requests.show', $request) }}" class="grid gap-4 border-b border-emerald-950/10 px-5 py-5 transition last:border-b-0 hover:bg-lime-50 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_160px_110px_32px] md:items-center md:px-7">
                        <div>
                            <p class="font-bold text-emerald-950">{{ $request->customer_name ?: 'Visiteur anonyme' }}</p>
                            <p class="mt-2 text-[0.68rem] font-bold uppercase tracking-wider text-slate-400">{{ $request->created_at->format('d.m.Y · H:i') }}</p>
                        </div>
                        <div class="text-sm">
                            <p class="font-semibold text-slate-700">{{ $request->environment->label() }} · {{ $request->space_size->label() }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $request->exposure->label() }} · entretien {{ mb_strtolower($request->maintenance_availability->label()) }}</p>
                        </div>
                        <x-advice-status-badge :status="$request->status" class="w-fit" />
                        <div>
                            <p class="font-display text-2xl font-semibold text-emerald-950">{{ $request->recommendations_count }}</p>
                            <p class="text-xs text-slate-500">recommandation(s)</p>
                        </div>
                        <span class="hidden text-xl text-emerald-700 md:block" aria-hidden="true">→</span>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center">
                        <p class="font-display text-3xl font-semibold text-emerald-950">Aucune demande trouvée.</p>
                        <p class="mt-2 text-sm text-slate-500">Modifiez les filtres ou attendez une nouvelle demande publique.</p>
                    </div>
                @endforelse
            </section>

            <div>{{ $adviceRequests->links() }}</div>
        </div>
    </div>
</x-app-layout>
