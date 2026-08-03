<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Bonjour {{ auth()->user()->name }}</p>
                <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-5xl">Le jardin, en un regard.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Catalogue et demandes de conseil réunis dans un espace simple pour piloter la pépinière.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.advice-requests.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/15 bg-white px-5 py-3 text-sm font-bold text-emerald-950 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Voir les demandes</a>
                <a href="{{ route('admin.plants.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Ajouter une plante</a>
            </div>
        </div>
    </x-slot>

    <div class="botanical-grid min-h-[calc(100vh-12rem)] py-8">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(280px,0.75fr)]">
                <div class="relative overflow-hidden rounded-[2rem] bg-emerald-950 p-7 text-white shadow-sm sm:p-10">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[28px] border-lime-300/20"></div>
                    <div class="pointer-events-none absolute -bottom-24 right-16 h-48 w-48 rounded-full border-[22px] border-emerald-700/60"></div>
                    <div class="relative max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-300">Principe cardinal</p>
                        <h2 class="mt-4 font-display text-4xl font-semibold leading-tight sm:text-5xl">Le catalogue décide. Le conseiller explique.</h2>
                        <p class="mt-5 max-w-xl text-sm leading-7 text-emerald-100">Laravel reste la source de vérité : seules les plantes actives, en stock et éligibles sont transmises au conseiller IA.</p>
                        <div class="mt-8 flex flex-wrap gap-3 text-xs font-bold uppercase tracking-[0.14em] text-emerald-950">
                            <span class="rounded-full bg-lime-300 px-4 py-2">Catalogue contrôlé</span>
                            <span class="rounded-full bg-white/10 px-4 py-2 text-emerald-100 ring-1 ring-inset ring-white/15">Résultats vérifiés</span>
                        </div>
                    </div>
                </div>

                <aside class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-7 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Parcours de démonstration</p>
                    <ol class="mt-6 space-y-5">
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-950 text-xs font-bold text-lime-300">01</span>
                            <div><p class="font-bold text-emerald-950">Vérifier le catalogue</p><p class="mt-1 text-sm leading-6 text-slate-500">Caractéristiques, exposition et stock à jour.</p></div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-950 text-xs font-bold text-lime-300">02</span>
                            <div><p class="font-bold text-emerald-950">Recevoir une demande</p><p class="mt-1 text-sm leading-6 text-slate-500">Le visiteur remplit le formulaire public.</p></div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-950 text-xs font-bold text-lime-300">03</span>
                            <div><p class="font-bold text-emerald-950">Auditer le résultat</p><p class="mt-1 text-sm leading-6 text-slate-500">Le worker traite puis conserve la recommandation.</p></div>
                        </li>
                    </ol>
                </aside>
            </section>

            <section class="grid gap-6 md:grid-cols-2" aria-label="Accès rapides">
                <a href="{{ route('admin.plants.index') }}" class="group rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-7 shadow-sm transition hover:-translate-y-1 hover:border-emerald-700/30 hover:shadow-md sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">01 · Catalogue</p>
                            <h2 class="mt-3 font-display text-3xl font-semibold text-emerald-950">Gérer les plantes</h2>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-lime-300 text-xl text-emerald-950 transition group-hover:rotate-6" aria-hidden="true">↗</span>
                    </div>
                    <p class="mt-5 max-w-lg text-sm leading-7 text-slate-600">Ajoutez les fiches, ajustez les quantités et désactivez les plantes qui ne doivent plus être proposées.</p>
                    <span class="mt-8 inline-flex text-sm font-bold text-emerald-800">Ouvrir le catalogue <span class="ml-2 transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                </a>

                <a href="{{ route('admin.advice-requests.index') }}" class="group rounded-[2rem] bg-[#f4f0e6] p-7 shadow-sm transition hover:-translate-y-1 hover:bg-[#ebe5d6] hover:shadow-md sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">02 · Conseils</p>
                            <h2 class="mt-3 font-display text-3xl font-semibold text-emerald-950">Consulter les demandes</h2>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-950 text-xl text-lime-300 transition group-hover:-rotate-6" aria-hidden="true">↗</span>
                    </div>
                    <p class="mt-5 max-w-lg text-sm leading-7 text-slate-600">Suivez les statuts et vérifiez les recommandations validées avant leur affichage au visiteur.</p>
                    <span class="mt-8 inline-flex text-sm font-bold text-emerald-800">Voir l’historique <span class="ml-2 transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                </a>
            </section>
        </div>
    </div>
</x-app-layout>
