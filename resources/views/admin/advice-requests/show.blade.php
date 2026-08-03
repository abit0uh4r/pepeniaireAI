<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.advice-requests.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-950">← Retour aux demandes</a>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-5xl">Demande #{{ $adviceRequest->id }}</h1>
                    <x-advice-status-badge :status="$adviceRequest->status" />
                </div>
                <p class="mt-3 text-sm text-slate-600">Reçue le {{ $adviceRequest->created_at->translatedFormat('d F Y à H:i') }}</p>
            </div>
            <a href="{{ route('advice.track', ['token' => $adviceRequest->public_token]) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/15 bg-white px-5 py-3 text-sm font-bold text-emerald-950 hover:bg-emerald-50">Voir la page publique ↗</a>
        </div>
    </x-slot>

    <div class="botanical-grid min-h-[calc(100vh-12rem)] py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:px-8">
            <div class="space-y-6">
                <section class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Besoin exprimé</p>
                    <p class="mt-5 whitespace-pre-line text-base leading-8 text-slate-700">{{ $adviceRequest->free_text_description }}</p>
                    <dl class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold text-slate-500">Environnement</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $adviceRequest->environment->label() }}</dd></div>
                        <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold text-slate-500">Exposition</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $adviceRequest->exposure->label() }}</dd></div>
                        <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold text-slate-500">Espace</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $adviceRequest->space_size->label() }}</dd></div>
                        <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold text-slate-500">Animaux</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $adviceRequest->has_pets ? 'Oui' : 'Non' }}</dd></div>
                    </dl>
                </section>

                @if ($adviceRequest->status->value === 'COMPLETED')
                    <section class="rounded-[2rem] bg-emerald-950 p-6 text-white shadow-sm sm:p-8">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-300">Résultat validé</p>
                        <h2 class="mt-4 font-display text-3xl font-semibold">{{ $adviceRequest->space_summary ?: 'Conseil sans candidate' }}</h2>
                        @if ($adviceRequest->general_advice)
                            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-emerald-100">{{ $adviceRequest->general_advice }}</p>
                        @endif
                    </section>

                    <section class="space-y-4">
                        @forelse ($adviceRequest->recommendations as $recommendation)
                            @php($plant = $recommendation->plant)
                            <article class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm sm:p-7">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Rang {{ $recommendation->rank }}</p>
                                        <h3 class="mt-1 font-display text-3xl font-semibold text-emerald-950">{{ $plant?->name ?? 'Plante archivée' }}</h3>
                                        <p class="mt-1 text-xs italic text-slate-500">{{ $plant?->species }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $plant && $plant->is_active && $plant->stock_quantity > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $plant && $plant->is_active && $plant->stock_quantity > 0 ? "Stock actuel : {$plant->stock_quantity}" : 'Indisponible actuellement' }}
                                    </span>
                                </div>
                                <p class="mt-5 border-l-4 border-lime-400 pl-4 text-sm leading-7 text-slate-700">{{ $recommendation->reason }}</p>
                                <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs text-slate-500">Prix observé</dt><dd class="mt-1 font-bold text-emerald-950">{{ \App\Support\MoneyFormatter::formatMad($recommendation->price_snapshot) }}</dd></div>
                                    <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs text-slate-500">Stock observé</dt><dd class="mt-1 font-bold text-emerald-950">{{ $recommendation->stock_quantity_snapshot }}</dd></div>
                                    <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs text-slate-500">Stock courant</dt><dd class="mt-1 font-bold text-emerald-950">{{ $plant?->stock_quantity ?? '—' }}</dd></div>
                                </dl>
                            </article>
                        @empty
                            <div class="rounded-[2rem] border border-dashed border-emerald-950/20 bg-[#fffdf8] p-8 text-center text-sm text-slate-600">Aucune candidate n’était éligible au moment du traitement.</div>
                        @endforelse
                    </section>
                @elseif ($adviceRequest->status->value === 'FAILED')
                    <section class="rounded-[2rem] border border-rose-200 bg-rose-50 p-6 text-rose-900">
                        <p class="text-xs font-bold uppercase tracking-[0.18em]">Échec contrôlé · {{ $adviceRequest->failure_code }}</p>
                        <p class="mt-3 text-sm leading-6">{{ $adviceRequest->failure_message ?: 'Le traitement n’a pas pu aboutir.' }}</p>
                    </section>
                @else
                    <section class="rounded-[2rem] border border-sky-200 bg-sky-50 p-6 text-sky-900">
                        <p class="text-xs font-bold uppercase tracking-[0.18em]">Traitement en cours</p>
                        <p class="mt-3 text-sm leading-6">Le résultat n’est pas encore disponible. Le worker mettra cette fiche à jour.</p>
                    </section>
                @endif
            </div>

            <aside class="space-y-5">
                <section class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Visiteur</p>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div><dt class="text-xs text-slate-500">Nom</dt><dd class="mt-1 font-bold text-emerald-950">{{ $adviceRequest->customer_name ?: 'Non renseigné' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Email</dt><dd class="mt-1 break-all font-bold text-emerald-950">{{ $adviceRequest->customer_email ?: 'Non renseigné' }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Chronologie</p>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div><dt class="text-xs text-slate-500">Création</dt><dd class="mt-1 font-semibold text-emerald-950">{{ $adviceRequest->created_at->format('d/m/Y H:i:s') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Début du traitement</dt><dd class="mt-1 font-semibold text-emerald-950">{{ $adviceRequest->processing_started_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Fin du traitement</dt><dd class="mt-1 font-semibold text-emerald-950">{{ $adviceRequest->processed_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-[2rem] bg-lime-300 p-6 text-emerald-950">
                    <p class="text-xs font-bold uppercase tracking-[0.18em]">Audit</p>
                    <p class="mt-3 text-sm leading-6">Les textes sont affichés avec l’échappement Blade. Les prix et stocks observés restent séparés des valeurs courantes.</p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
