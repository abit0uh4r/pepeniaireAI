<x-public-layout>
    <div
        x-data="{
            status: @js($adviceRequest->status->value),
            label: @js($adviceRequest->status->label()),
            terminal: @js($adviceRequest->status->isTerminal()),
            statusUrl: @js(route('advice.status', ['token' => $adviceRequest->public_token])),
            error: false,
            timer: null,
            refresh() {
                if (this.terminal) return;

                fetch(this.statusUrl, { headers: { Accept: 'application/json' } })
                    .then(response => {
                        if (!response.ok) throw new Error('status-unavailable');
                        return response.json();
                    })
                    .then(payload => {
                        const becameTerminal = !this.terminal && payload.terminal;
                        this.status = payload.status;
                        this.label = payload.label;
                        this.terminal = payload.terminal;
                        this.error = false;

                        if (becameTerminal) {
                            window.location.reload();
                        }
                    })
                    .catch(() => { this.error = true; });
            },
            init() {
                if (!this.terminal) this.timer = setInterval(() => this.refresh(), 5000);
            }
        }"
        x-init="init()"
        class="botanical-grid min-h-screen bg-[#f4f0e6]"
    >
        <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-6 sm:px-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-emerald-950">
                <x-application-logo class="h-10 w-10" />
                <span class="font-display text-xl font-semibold">Pépinière IA</span>
            </a>
            <a href="{{ route('advice.create') }}" class="rounded-full border border-emerald-950/15 bg-white/80 px-4 py-2 text-sm font-bold text-emerald-950 transition hover:bg-white">Nouvelle demande</a>
        </header>

        <main class="mx-auto max-w-7xl px-5 pb-20 pt-8 sm:px-8 lg:pt-12">
            <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Suivi privé · {{ $adviceRequest->created_at->format('d.m.Y') }}</p>
                    <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-6xl">
                        @if ($adviceRequest->status->value === 'COMPLETED')
                            Votre sélection est prête.
                        @elseif ($adviceRequest->status->value === 'FAILED')
                            Le conseil fait une pause.
                        @else
                            Votre conseil prend racine.
                        @endif
                    </h1>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-emerald-950/10 bg-[#fffdf8] px-4 py-3 shadow-sm">
                    <span class="relative flex h-3 w-3">
                        <span x-show="!terminal" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-70"></span>
                        <span
                            class="relative inline-flex h-3 w-3 rounded-full"
                            :class="{
                                'bg-amber-500': status === 'PENDING',
                                'bg-sky-500': status === 'PROCESSING',
                                'bg-emerald-600': status === 'COMPLETED',
                                'bg-rose-600': status === 'FAILED'
                            }"
                        ></span>
                    </span>
                    <div>
                        <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-500">État actuel</p>
                        <p class="text-sm font-bold text-emerald-950" x-text="label"></p>
                    </div>
                </div>
            </div>

            <div x-cloak x-show="error" class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-900" role="status">
                Le suivi rencontre un délai temporaire. Une nouvelle tentative sera faite automatiquement.
            </div>

            @if ($adviceRequest->status->value === 'COMPLETED')
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <section class="space-y-6">
                        <div class="overflow-hidden rounded-[2.25rem] border border-emerald-950/10 bg-emerald-950 text-white shadow-[0_28px_80px_-45px_rgba(6,78,59,0.65)]">
                            <div class="grid gap-8 p-7 sm:p-10 lg:grid-cols-[0.85fr_1.15fr]">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-lime-300">Lecture de votre espace</p>
                                    <h2 class="mt-4 font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $adviceRequest->space_summary ?: 'Votre espace a été analysé.' }}</h2>
                                </div>
                                <div class="border-t border-white/15 pt-6 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-300">Conseil général</p>
                                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-emerald-50">{{ $adviceRequest->general_advice ?: 'Les recommandations ci-dessous respectent les critères et le stock observés.' }}</p>
                                </div>
                            </div>
                        </div>

                        @forelse ($adviceRequest->recommendations as $recommendation)
                            @php($plant = $recommendation->plant)
                            <article class="overflow-hidden rounded-[2.25rem] border border-emerald-950/10 bg-[#fffdf8] shadow-sm">
                                <div class="grid lg:grid-cols-[180px_minmax(0,1fr)]">
                                    <div class="flex flex-row items-center justify-between bg-lime-300 p-6 lg:flex-col lg:items-start">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Choix</p>
                                            <p class="mt-1 font-display text-6xl font-semibold text-emerald-950">{{ str_pad((string) $recommendation->rank, 2, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                        <svg class="h-20 w-20 text-emerald-900 lg:h-24 lg:w-24" viewBox="0 0 96 96" fill="none" aria-hidden="true">
                                            <path d="M48 83V34" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                                            <path d="M48 57C26 56 15 43 14 22c22-2 34 11 34 35Z" fill="currentColor" opacity=".82"/>
                                            <path d="M48 70c23-1 34-15 36-38-24-1-36 12-36 38Z" fill="currentColor" opacity=".52"/>
                                            <path d="M31 83h34" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="p-6 sm:p-8">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">{{ $plant?->species ?? 'Fiche archivée' }}</p>
                                                <h3 class="mt-1 font-display text-3xl font-semibold text-emerald-950">{{ $plant?->name ?? 'Plante du catalogue' }}</h3>
                                            </div>
                                            @if ($plant !== null && $plant->is_active && $plant->stock_quantity > 0)
                                                <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800">Disponible aujourd’hui · {{ $plant->stock_quantity }}</span>
                                            @else
                                                <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">Disponibilité modifiée</span>
                                            @endif
                                        </div>

                                        <blockquote class="mt-6 border-l-4 border-lime-400 pl-5 text-base leading-7 text-slate-700">« {{ $recommendation->reason }} »</blockquote>

                                        @if ($plant !== null)
                                            <dl class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Entretien</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $plant->maintenance_level->label() }}</dd></div>
                                                <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Arrosage</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $plant->watering_level->label() }}</dd></div>
                                                <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Prix observé</dt><dd class="mt-2 font-semibold text-emerald-950">{{ \App\Support\MoneyFormatter::formatMad($recommendation->price_snapshot) }}</dd></div>
                                                <div class="rounded-2xl bg-[#f4f0e6] p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Stock observé</dt><dd class="mt-2 font-semibold text-emerald-950">{{ $recommendation->stock_quantity_snapshot }} unité(s)</dd></div>
                                            </dl>

                                            @if ($plant->pet_safe !== true && $adviceRequest->has_pets)
                                                <p class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">Prudence avec les animaux : vérifiez la fiche et placez la plante hors de portée.</p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[2rem] border border-dashed border-emerald-950/20 bg-[#fffdf8] p-8 text-center">
                                <p class="font-display text-3xl font-semibold text-emerald-950">Aucune candidate compatible aujourd’hui.</p>
                                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600">Le catalogue et le stock ont été vérifiés. Essayez une nouvelle demande avec des critères plus larges ou revenez après un réassort.</p>
                            </div>
                        @endforelse
                    </section>

                    <aside class="space-y-5">
                        <div class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-6">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Repères fournis</p>
                            <dl class="mt-5 space-y-4 text-sm">
                                <div class="flex justify-between gap-4 border-b border-emerald-950/10 pb-3"><dt class="text-slate-500">Environnement</dt><dd class="font-bold text-emerald-950">{{ $adviceRequest->environment->label() }}</dd></div>
                                <div class="flex justify-between gap-4 border-b border-emerald-950/10 pb-3"><dt class="text-slate-500">Exposition</dt><dd class="font-bold text-emerald-950">{{ $adviceRequest->exposure->label() }}</dd></div>
                                <div class="flex justify-between gap-4 border-b border-emerald-950/10 pb-3"><dt class="text-slate-500">Espace</dt><dd class="font-bold text-emerald-950">{{ $adviceRequest->space_size->label() }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">Entretien</dt><dd class="font-bold text-emerald-950">{{ $adviceRequest->maintenance_availability->label() }}</dd></div>
                            </dl>
                        </div>
                        <div class="rounded-[2rem] bg-emerald-950 p-6 text-white">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-300">À retenir</p>
                            <p class="mt-4 text-sm leading-7 text-emerald-50">Le prix et le stock observés sont des instantanés du moment du conseil. Vérifiez la disponibilité actuelle avant l’achat.</p>
                        </div>
                        <a href="{{ route('advice.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-lime-300 px-5 py-4 text-sm font-bold text-emerald-950 transition hover:bg-lime-200">Recommencer <span class="ml-3 text-lg">→</span></a>
                    </aside>
                </div>
            @elseif ($adviceRequest->status->value === 'FAILED')
                <section class="grid overflow-hidden rounded-[2.5rem] border border-rose-900/10 bg-[#fffdf8] lg:grid-cols-[0.75fr_1.25fr]" role="alert">
                    <div class="flex min-h-64 items-center justify-center bg-rose-100 p-8">
                        <div class="flex h-32 w-32 items-center justify-center rounded-full border-2 border-rose-300 font-display text-6xl text-rose-800">!</div>
                    </div>
                    <div class="p-8 sm:p-12">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Traitement interrompu</p>
                        <h2 class="mt-4 font-display text-4xl font-semibold text-emerald-950">Aucune recommandation incertaine ne sera affichée.</h2>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">Une vérification n’a pas abouti. Votre catalogue et votre stock n’ont pas été modifiés. Vous pouvez relancer une nouvelle demande.</p>
                        <a href="{{ route('advice.create') }}" class="mt-8 inline-flex rounded-2xl bg-emerald-950 px-6 py-4 text-sm font-bold text-white">Nouvelle demande</a>
                    </div>
                </section>
            @else
                <section class="overflow-hidden rounded-[2.5rem] border border-emerald-950/10 bg-[#fffdf8] shadow-sm">
                    <div class="grid lg:grid-cols-[0.8fr_1.2fr]">
                        <div class="relative flex min-h-72 items-center justify-center overflow-hidden bg-emerald-950 p-8">
                            <div class="absolute h-56 w-56 animate-pulse rounded-full border border-lime-300/30"></div>
                            <div class="absolute h-40 w-40 rounded-full border border-lime-300/50"></div>
                            <svg class="relative h-36 w-36 text-lime-300" viewBox="0 0 140 140" fill="none" aria-hidden="true">
                                <path d="M70 122V49" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                                <path d="M70 82C38 80 21 61 20 30c33-2 50 17 50 52Z" fill="currentColor" opacity=".9"/>
                                <path d="M70 103c34-2 51-22 54-56-36-2-53 18-54 56Z" fill="currentColor" opacity=".55"/>
                                <path d="M44 122h52" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="p-7 sm:p-10 lg:p-12">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">{{ $adviceRequest->status->value === 'PENDING' ? 'Demande reçue' : 'Analyse en cours' }}</p>
                            <h2 class="mt-4 font-display text-4xl font-semibold leading-tight text-emerald-950">
                                {{ $adviceRequest->status->value === 'PENDING' ? 'Votre demande attend son tour.' : 'Le catalogue passe au crible.' }}
                            </h2>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">La page se met à jour automatiquement. Vous pouvez la conserver ouverte ou revenir plus tard avec cette adresse privée.</p>
                            <ol class="mt-9 grid gap-3 sm:grid-cols-3">
                                @foreach ([
                                    ['Demande', 'reçue'],
                                    ['Catalogue', $adviceRequest->status->value === 'PROCESSING' ? 'en analyse' : 'à venir'],
                                    ['Conseil', 'à venir'],
                                ] as $index => [$step, $stepStatus])
                                    <li class="rounded-2xl border p-4 {{ ($index === 0 || ($index === 1 && $adviceRequest->status->value === 'PROCESSING')) ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                                        <span class="text-xs font-bold text-emerald-700">0{{ $index + 1 }}</span>
                                        <p class="mt-3 text-sm font-bold text-emerald-950">{{ $step }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $stepStatus }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </section>
            @endif

            <p class="mt-8 text-center text-xs leading-5 text-slate-500">Cette adresse contient votre token privé. Ne la partagez qu’avec une personne de confiance.</p>
        </main>
    </div>
</x-public-layout>
