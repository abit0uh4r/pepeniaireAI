<x-public-layout>
    <style>[x-cloak] { display: none !important; }</style>

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
                        this.status = payload.status;
                        this.label = payload.label;
                        this.terminal = payload.terminal;
                        this.error = false;
                        if (this.terminal && this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                    })
                    .catch(() => { this.error = true; });
            },
            init() {
                if (!this.terminal) this.timer = setInterval(() => this.refresh(), 5000);
            }
        }"
        x-init="init()"
        class="min-h-screen bg-[#f5f7f1] px-4 py-10 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-4xl">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Pépinière IA</a>
                <a href="{{ route('advice.create') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-800">Nouvelle demande</a>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <main class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div class="bg-emerald-950 px-6 py-8 text-emerald-50 sm:px-10 sm:py-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300">Suivi sécurisé</p>
                        <h1 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">Suivi de votre demande</h1>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-emerald-100">Cette page se met à jour automatiquement. Vous pouvez la laisser ouverte pendant le traitement.</p>
                    </div>

                    <div class="p-6 sm:p-10">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">État actuel</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950" x-text="label"></p>
                            </div>
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold"
                                :class="{
                                    'bg-amber-100 text-amber-800': status === 'PENDING',
                                    'bg-sky-100 text-sky-800': status === 'PROCESSING',
                                    'bg-emerald-100 text-emerald-800': status === 'COMPLETED',
                                    'bg-rose-100 text-rose-800': status === 'FAILED'
                                }"
                            >
                                <span class="h-2 w-2 rounded-full bg-current" :class="{ 'animate-pulse': !terminal }"></span>
                                <span x-text="status"></span>
                            </span>
                        </div>

                        <div class="mt-10 grid gap-5 sm:grid-cols-3">
                            <div class="relative rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <span class="text-xs font-bold text-emerald-700">01</span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">Demande reçue</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Vos critères sont enregistrés.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <span class="text-xs font-bold text-slate-500">02</span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">Analyse</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Le catalogue est vérifié.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <span class="text-xs font-bold text-slate-500">03</span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">Conseil</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Les résultats seront affichés ici.</p>
                            </div>
                        </div>

                        <div x-cloak x-show="error" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                            Le suivi rencontre un délai temporaire. La page réessaiera automatiquement.
                        </div>

                        <div x-cloak x-show="status === 'FAILED'" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm leading-6 text-rose-800" role="alert">
                            Le traitement n’a pas pu aboutir. Vous pouvez soumettre une nouvelle demande.
                        </div>

                        <div x-cloak x-show="status === 'COMPLETED'" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm leading-6 text-emerald-800" role="status">
                            Votre conseil est prêt. Les recommandations validées apparaîtront ici.
                        </div>
                    </div>
                </main>

                <aside class="rounded-[2rem] border border-emerald-900/10 bg-[#e5eee3] p-6 sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-800">Votre lien privé</p>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">Gardez cette adresse.</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Ce lien contient votre token public sécurisé. Il remplace un compte et ne doit pas être partagé.</p>
                    <div class="mt-8 rounded-2xl bg-white/70 p-4 text-xs leading-5 text-slate-600">
                        Aucun nom, email ou détail de votre demande n’est affiché dans le point de statut.
                    </div>
                    <a href="{{ route('advice.create') }}" class="mt-6 inline-flex items-center text-sm font-semibold text-emerald-900 hover:text-emerald-700">Faire une autre demande <span class="ml-2 text-lg">→</span></a>
                </aside>
            </div>
        </div>
    </div>
</x-public-layout>
