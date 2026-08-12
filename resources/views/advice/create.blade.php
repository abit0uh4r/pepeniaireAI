<x-public-layout>
    <div class="min-h-screen bg-[#f5f7f1] px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <a href="{{ url('/') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Pépinière IA</a>
                    <p class="mt-6 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Conseil personnalisé</p>
                    <h1 class="mt-2 max-w-2xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">Décrivez votre espace, on s’occupe du reste.</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">Quelques repères suffisent pour préparer une sélection de plantes réellement disponibles. Aucun compte n’est nécessaire.</p>
                </div>
                <div class="rounded-3xl bg-emerald-950 px-5 py-4 text-sm text-emerald-50 shadow-sm sm:max-w-xs">
                    <p class="font-semibold">Un conseil fondé sur le réel</p>
                    <p class="mt-1 text-emerald-200">Le catalogue et le stock de la pépinière restent la source de vérité.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('advice.store') }}" class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                @csrf
                <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                    <aside class="bg-emerald-950 p-6 text-emerald-50 sm:p-8 lg:p-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">01 — Votre contexte</p>
                        <h2 class="mt-4 text-2xl font-semibold tracking-tight">Les bons critères, au bon moment.</h2>
                        <p class="mt-3 text-sm leading-6 text-emerald-100">Ces réponses servent à écarter les plantes incompatibles avant toute interprétation du texte libre.</p>
                        <div class="mt-10 space-y-5 text-sm text-emerald-100">
                            <div class="flex gap-3"><span class="text-xl">☀</span><span>La lumière et l’environnement guident la première sélection.</span></div>
                            <div class="flex gap-3"><span class="text-xl">⌂</span><span>La taille disponible évite les choix trop encombrants.</span></div>
                            <div class="flex gap-3"><span class="text-xl">♡</span><span>La présence d’animaux est prise en compte avec prudence.</span></div>
                        </div>
                    </aside>

                    <div class="space-y-8 p-6 sm:p-8 lg:p-10">
                        <section>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="environment" class="block text-sm font-semibold text-slate-800">Environnement</label>
                                    <select id="environment" name="environment" required class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        <option value="">Choisir…</option>
                                        @foreach ($environments as $environment)
                                            <option value="{{ $environment->value }}" @selected(old('environment') === $environment->value)>{{ $environment->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('environment')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="exposure" class="block text-sm font-semibold text-slate-800">Exposition principale</label>
                                    <select id="exposure" name="exposure" required class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        <option value="">Choisir…</option>
                                        @foreach ($exposures as $exposure)
                                            <option value="{{ $exposure->value }}" @selected(old('exposure') === $exposure->value)>{{ $exposure->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('exposure')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="space_size" class="block text-sm font-semibold text-slate-800">Taille de l’espace</label>
                                    <select id="space_size" name="space_size" required class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        <option value="">Choisir…</option>
                                        @foreach ($spaceSizes as $spaceSize)
                                            <option value="{{ $spaceSize->value }}" @selected(old('space_size') === $spaceSize->value)>{{ $spaceSize->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('space_size')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="maintenance_availability" class="block text-sm font-semibold text-slate-800">Entretien possible</label>
                                    <select id="maintenance_availability" name="maintenance_availability" required class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        <option value="">Choisir…</option>
                                        @foreach ($levels as $level)
                                            <option value="{{ $level->value }}" @selected(old('maintenance_availability') === $level->value)>{{ $level->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('maintenance_availability')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-8">
                            <label for="free_text_description" class="block text-sm font-semibold text-slate-800">Parlez-nous de votre projet</label>
                            <p class="mt-1 text-sm text-slate-500">Lumière ressentie, habitudes, style recherché, contraintes particulières…</p>
                            <textarea id="free_text_description" name="free_text_description" rows="6" required minlength="20" maxlength="5000" class="mt-3 block w-full rounded-2xl border-slate-300 leading-6 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Ex. Mon salon reçoit une lumière douce le matin…">{{ old('free_text_description') }}</textarea>
                            <x-input-error :messages="$errors->get('free_text_description')" class="mt-2" />
                        </section>

                        <section class="border-t border-slate-100 pt-8">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Optionnel</p>
                            <div class="mt-4 grid max-w-xl gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="customer_name" class="block text-sm font-semibold text-slate-800">Votre prénom</label>
                                    <input id="customer_name" name="customer_name" type="text" maxlength="120" value="{{ old('customer_name') }}" class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="customer_phone" class="block text-sm font-semibold text-slate-800">Téléphone (optionnel)</label>
                                    <input id="customer_phone" name="customer_phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="30" value="{{ old('customer_phone') }}" placeholder="06 12 34 56 78" class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <div class="border-t border-slate-100 pt-6">
                            <label class="flex items-start gap-3 text-sm leading-6 text-slate-600">
                                <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-1 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                <span>J’ai compris que le conseil est indicatif et fondé sur les informations du catalogue.</span>
                            </label>
                            <x-input-error :messages="$errors->get('consent')" class="mt-2" />
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Envoyer ma demande <span class="ml-2 text-lg">→</span></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-public-layout>
