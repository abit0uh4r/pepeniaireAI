<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-emerald-950/10 bg-[#fffdf8]/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between py-3">
            <div class="flex items-center gap-8">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3 text-emerald-950">
                    <x-application-logo class="h-10 w-10" />
                    <div class="hidden sm:block">
                        <p class="font-display text-lg font-semibold leading-none">Pépinière IA</p>
                        <p class="mt-1 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-emerald-700">Console gérant</p>
                    </div>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-950 text-white' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-950' }}">Vue d’ensemble</a>
                    <a href="{{ route('admin.plants.index') }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs('admin.plants.*') ? 'bg-emerald-950 text-white' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-950' }}">Catalogue</a>
                    <a href="{{ route('admin.advice-requests.index') }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs('admin.advice-requests.*') ? 'bg-emerald-950 text-white' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-950' }}">Demandes</a>
                </div>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="rounded-full border border-emerald-950/10 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">Voir le site ↗</a>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full bg-[#f4f0e6] py-1.5 pl-2 pr-3 text-sm font-bold text-emerald-950 transition hover:bg-lime-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-lime-300 text-xs">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a1 1 0 0 1 1.4 0L10 10.6l3.4-3.4a1 1 0 1 1 1.4 1.4l-4.1 4.1a1 1 0 0 1-1.4 0L5.2 8.6a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Se déconnecter</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <button @click="open = ! open" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-emerald-950/10 text-emerald-950 md:hidden" :aria-expanded="open.toString()" aria-label="Ouvrir le menu">
                <svg x-show="!open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16M4 12h16M4 17h16" stroke-width="2" stroke-linecap="round"/></svg>
                <svg x-cloak x-show="open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 6 12 12M18 6 6 18" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>

    <div x-cloak x-show="open" x-transition class="border-t border-emerald-950/10 bg-[#fffdf8] px-4 py-4 md:hidden">
        <div class="space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-4 py-3 text-sm font-bold {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-950 text-white' : 'text-emerald-950' }}">Vue d’ensemble</a>
            <a href="{{ route('admin.plants.index') }}" class="block rounded-xl px-4 py-3 text-sm font-bold {{ request()->routeIs('admin.plants.*') ? 'bg-emerald-950 text-white' : 'text-emerald-950' }}">Catalogue</a>
            <a href="{{ route('admin.advice-requests.index') }}" class="block rounded-xl px-4 py-3 text-sm font-bold {{ request()->routeIs('admin.advice-requests.*') ? 'bg-emerald-950 text-white' : 'text-emerald-950' }}">Demandes</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-4 py-3 text-sm font-bold text-emerald-950">Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-xl px-4 py-3 text-left text-sm font-bold text-rose-700">Se déconnecter</button>
            </form>
        </div>
    </div>
</nav>
