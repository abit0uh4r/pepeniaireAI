<x-public-layout>
    <div class="relative overflow-hidden">
        <div class="botanical-grid absolute inset-0 -z-20"></div>
        <div class="absolute -right-24 top-32 -z-10 h-96 w-96 rounded-full bg-lime-200/50 blur-3xl"></div>
        <div class="absolute -left-32 top-[38rem] -z-10 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>

        <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-6 sm:px-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-emerald-950">
                <x-application-logo class="h-11 w-11" />
                <span class="font-display text-xl font-semibold">Pépinière IA</span>
            </a>
            <nav class="flex items-center gap-3" aria-label="Navigation principale">
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-emerald-950/15 bg-white/70 px-4 py-2 text-sm font-semibold text-emerald-950 backdrop-blur transition hover:bg-white">Administration</a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-emerald-950 hover:text-emerald-700 sm:inline">Espace gérant</a>
                @endauth
                <a href="{{ route('advice.create') }}" class="rounded-full bg-emerald-950 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-emerald-950/15 transition hover:-translate-y-0.5 hover:bg-emerald-800 sm:px-5 sm:text-sm" aria-label="Demander conseil">
                    <span class="sm:hidden">Conseil</span>
                    <span class="hidden sm:inline">Demander conseil</span>
                </a>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-5 pb-20 pt-12 sm:px-8 lg:grid-cols-[1.08fr_0.92fr] lg:items-center lg:pb-28 lg:pt-20">
                <div class="min-w-0">
                    <p class="inline-flex items-center gap-2 rounded-full border border-emerald-900/15 bg-white/70 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-emerald-900 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                        Conseil botanique, stock réel
                    </p>
                    <h1 class="mt-7 max-w-4xl font-display text-[2.65rem] font-semibold leading-[0.98] tracking-[-0.035em] text-emerald-950 sm:text-7xl lg:text-[5.5rem]">
                        La bonne plante, pour
                        <span class="relative inline-block text-emerald-700">
                            votre vrai espace.
                            <svg class="absolute -bottom-2 left-0 h-3 w-full text-lime-500" viewBox="0 0 240 12" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M2 9C65 2 164 2 238 7" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </h1>
                    <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-700">Décrivez votre lumière, votre espace et votre rythme. Laravel élimine les plantes incompatibles, puis le conseiller classe uniquement celles qui sont réellement disponibles.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('advice.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-950 px-6 py-4 text-sm font-bold text-white shadow-xl shadow-emerald-950/15 transition hover:-translate-y-0.5 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-4">
                            Trouver mes plantes
                            <span class="ml-3 text-lg" aria-hidden="true">→</span>
                        </a>
                        <a href="#methode" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/15 bg-white/70 px-6 py-4 text-sm font-bold text-emerald-950 backdrop-blur transition hover:bg-white">Voir la méthode</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm font-medium text-slate-600">
                        <span class="inline-flex items-center gap-2"><span class="text-emerald-700">✓</span> Sans compte</span>
                        <span class="inline-flex items-center gap-2"><span class="text-emerald-700">✓</span> Stock vérifié</span>
                        <span class="inline-flex items-center gap-2"><span class="text-emerald-700">✓</span> Lien privé</span>
                    </div>
                </div>

                <div class="relative mx-auto min-w-0 w-full max-w-xl lg:mx-0">
                    <div class="absolute -left-5 top-16 h-24 w-24 rotate-12 rounded-[2rem] bg-lime-300"></div>
                    <div class="relative rotate-1 overflow-hidden rounded-[2.5rem] border border-emerald-950/10 bg-emerald-950 p-6 text-white shadow-[0_35px_100px_-40px_rgba(6,78,59,0.7)] sm:p-8">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">Fiche conseil · 01</span>
                            <span class="rounded-full bg-lime-300 px-3 py-1 text-xs font-bold text-emerald-950">En stock</span>
                        </div>
                        <div class="mt-12">
                            <svg class="h-44 w-full text-emerald-200" viewBox="0 0 380 180" fill="none" aria-hidden="true">
                                <path d="M190 170V48" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                                <path d="M190 105C136 103 103 70 99 19c55-4 88 27 91 86Z" fill="#BEF264"/>
                                <path d="M190 141c58-3 94-38 100-96-62-4-96 29-100 96Z" fill="#6EE7B7"/>
                                <path d="M143 170h94" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                            <p class="mt-6 text-sm text-emerald-200">Sélectionnée après 7 contrôles déterministes</p>
                            <h2 class="mt-2 font-display text-4xl font-semibold">Une plante qui tient ses promesses.</h2>
                        </div>
                        <div class="mt-8 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-2xl bg-white/10 px-2 py-3"><span class="block text-emerald-300">Lumière</span><strong class="mt-1 block">Douce</strong></div>
                            <div class="rounded-2xl bg-white/10 px-2 py-3"><span class="block text-emerald-300">Entretien</span><strong class="mt-1 block">Faible</strong></div>
                            <div class="rounded-2xl bg-white/10 px-2 py-3"><span class="block text-emerald-300">Stock</span><strong class="mt-1 block">Vérifié</strong></div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -right-4 -rotate-3 rounded-2xl border border-emerald-950/10 bg-[#fffdf8] px-5 py-4 shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Source de vérité</p>
                        <p class="mt-1 font-display text-lg font-semibold text-emerald-950">Laravel + MySQL</p>
                    </div>
                </div>
            </section>

            <section id="methode" class="border-y border-emerald-950/10 bg-[#fffdf8]">
                <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-24">
                    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[0.7fr_1.3fr]">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Notre méthode</p>
                            <h2 class="mt-4 font-display text-4xl font-semibold leading-tight text-emerald-950 sm:text-5xl">L’intelligence artificielle reste à sa place.</h2>
                            <p class="mt-5 text-base leading-7 text-slate-600">Elle explique et ordonne. Elle ne touche jamais au catalogue, au stock ni à l’historique.</p>
                        </div>
                        <ol class="grid gap-4 sm:grid-cols-3">
                            <li class="rounded-[2rem] border border-emerald-950/10 bg-[#f4f0e6] p-6">
                                <span class="font-display text-4xl text-lime-600">01</span>
                                <h3 class="mt-8 font-display text-2xl font-semibold text-emerald-950">Vous décrivez</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Environnement, exposition, place disponible, entretien et animaux.</p>
                            </li>
                            <li class="rounded-[2rem] border border-emerald-950/10 bg-emerald-950 p-6 text-white">
                                <span class="font-display text-4xl text-lime-300">02</span>
                                <h3 class="mt-8 font-display text-2xl font-semibold">Laravel vérifie</h3>
                                <p class="mt-3 text-sm leading-6 text-emerald-100">Les candidates incompatibles ou indisponibles sont écartées avant l’IA.</p>
                            </li>
                            <li class="rounded-[2rem] border border-emerald-950/10 bg-lime-200 p-6">
                                <span class="font-display text-4xl text-emerald-800">03</span>
                                <h3 class="mt-8 font-display text-2xl font-semibold text-emerald-950">Vous choisissez</h3>
                                <p class="mt-3 text-sm leading-6 text-emerald-950/75">Chaque recommandation explique pourquoi elle convient, prix et stock observés inclus.</p>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28">
                <div class="overflow-hidden rounded-[2.5rem] bg-lime-300 px-6 py-12 sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Quelques minutes suffisent</p>
                        <h2 class="mt-3 max-w-3xl font-display text-4xl font-semibold leading-tight text-emerald-950 sm:text-5xl">Votre espace mérite mieux qu’une plante choisie au hasard.</h2>
                    </div>
                    <a href="{{ route('advice.create') }}" class="mt-8 inline-flex shrink-0 items-center rounded-2xl bg-emerald-950 px-6 py-4 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-800 lg:ml-10 lg:mt-0">Commencer <span class="ml-3 text-lg">→</span></a>
                </div>
            </section>
        </main>

        <footer class="border-t border-emerald-950/10 bg-[#fffdf8]">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p class="font-semibold text-emerald-950">Pépinière IA · Conseil fondé sur le réel</p>
                <p>Laravel reste la source de vérité.</p>
            </div>
        </footer>
    </div>
</x-public-layout>
