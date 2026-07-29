<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.plants.index') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Retour au catalogue</a>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Ajouter une plante</h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#f5f7f1] py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.plants.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                @csrf
                @include('admin.plants._form')
                <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('admin.plants.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Annuler</a>
                    <button type="submit" class="rounded-xl bg-emerald-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Enregistrer la plante</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
