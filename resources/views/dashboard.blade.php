<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Administration</p>
            <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight text-emerald-950 sm:text-5xl">Bienvenue, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Gérez le catalogue puis consultez les demandes de conseil traitées.</p>
        </div>
    </x-slot>

    <div class="botanical-grid min-h-[calc(100vh-12rem)] py-8">
        <div class="mx-auto grid max-w-5xl gap-6 px-4 sm:grid-cols-2 sm:px-6 lg:px-8">
            <a href="{{ route('admin.plants.index') }}" class="rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] p-8 shadow-sm transition hover:-translate-y-1 hover:border-emerald-700/30">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Catalogue</p>
                <h2 class="mt-4 font-display text-3xl font-semibold text-emerald-950">Gérer les plantes</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">Ajoutez les fiches, mettez à jour le stock et désactivez les plantes indisponibles.</p>
                <span class="mt-8 inline-flex text-sm font-bold text-emerald-800">Ouvrir le catalogue →</span>
            </a>

            <a href="{{ route('admin.advice-requests.index') }}" class="rounded-[2rem] bg-emerald-950 p-8 text-white shadow-sm transition hover:-translate-y-1 hover:bg-emerald-900">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-lime-300">Conseils</p>
                <h2 class="mt-4 font-display text-3xl font-semibold">Consulter les demandes</h2>
                <p class="mt-3 text-sm leading-7 text-emerald-100">Suivez les traitements et vérifiez les recommandations conservées par Laravel.</p>
                <span class="mt-8 inline-flex text-sm font-bold text-lime-300">Voir l’historique →</span>
            </a>
        </div>
    </div>
</x-app-layout>
