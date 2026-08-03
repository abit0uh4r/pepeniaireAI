<div class="space-y-8">
    @php
        $selectedExposures = old('exposure', isset($plant) ? $plant->exposureValues()->map(fn ($item) => $item->value)->all() : []);
        $petSafe = old('pet_safe', isset($plant) && $plant->pet_safe !== null ? ($plant->pet_safe ? '1' : '0') : '');
    @endphp
    <section>
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Identité</p>
            <p class="mt-1 text-sm text-slate-500">Ces informations restent la référence du catalogue.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nom commercial" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $plant->name ?? '')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="species" value="Espèce / nom botanique" />
                <x-text-input id="species" name="species" type="text" class="mt-1 block w-full" :value="old('species', $plant->species ?? '')" required />
                <x-input-error :messages="$errors->get('species')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Une description utile au visiteur">{{ old('description', $plant->description ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100 pt-8">
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Compatibilité</p>
            <p class="mt-1 text-sm text-slate-500">Les règles de conseil utiliseront ces valeurs avant toute IA.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="environment" value="Environnement" />
                <select id="environment" name="environment" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" required>
                    @foreach ($environments as $environment)
                        <option value="{{ $environment->value }}" @selected(old('environment', isset($plant) ? $plant->environment?->value : '') === $environment->value)>{{ $environment->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('environment')" class="mt-2" />
            </div>
            <fieldset>
                    <legend class="block font-medium text-sm text-gray-700">Expositions compatibles</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    @foreach ($exposures as $exposure)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-emerald-300">
                            <input type="checkbox" name="exposure[]" value="{{ $exposure->value }}" @checked(in_array($exposure->value, $selectedExposures, true)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            {{ $exposure->label() }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('exposure')" class="mt-2" />
                <x-input-error :messages="$errors->get('exposure.*')" class="mt-2" />
            </fieldset>
            <div>
                <x-input-label for="watering_level" value="Niveau d’arrosage" />
                <select id="watering_level" name="watering_level" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" required>
                    @foreach ($levels as $level)
                        <option value="{{ $level->value }}" @selected(old('watering_level', isset($plant) ? $plant->watering_level?->value : '') === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('watering_level')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="maintenance_level" value="Niveau d’entretien" />
                <select id="maintenance_level" name="maintenance_level" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" required>
                    @foreach ($levels as $level)
                        <option value="{{ $level->value }}" @selected(old('maintenance_level', isset($plant) ? $plant->maintenance_level?->value : '') === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('maintenance_level')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100 pt-8">
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Stock et vente</p>
            <p class="mt-1 text-sm text-slate-500">Le stock courant est la seule disponibilité utilisée par le futur moteur de conseil.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="adult_height_cm" value="Hauteur adulte (cm)" />
                <x-text-input id="adult_height_cm" name="adult_height_cm" type="number" min="0" class="mt-1 block w-full" :value="old('adult_height_cm', $plant->adult_height_cm ?? '')" />
                <x-input-error :messages="$errors->get('adult_height_cm')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="adult_width_cm" value="Largeur adulte (cm)" />
                <x-text-input id="adult_width_cm" name="adult_width_cm" type="number" min="0" class="mt-1 block w-full" :value="old('adult_width_cm', $plant->adult_width_cm ?? '')" />
                <x-input-error :messages="$errors->get('adult_width_cm')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="price" value="Prix (MAD)" />
                <x-text-input id="price" name="price" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('price', $plant->price ?? '')" required />
                <x-input-error :messages="$errors->get('price')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="stock_quantity" value="Stock" />
                <x-text-input id="stock_quantity" name="stock_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('stock_quantity', $plant->stock_quantity ?? 0)" required />
                <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="pet_safe" value="Sécurité animale" />
                <select id="pet_safe" name="pet_safe" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="" @selected((string) $petSafe === '')>Inconnue</option>
                    <option value="1" @selected((string) $petSafe === '1')>Sûre</option>
                    <option value="0" @selected((string) $petSafe === '0')>À éviter</option>
                </select>
                <x-input-error :messages="$errors->get('pet_safe')" class="mt-2" />
            </div>
            <label class="flex items-center gap-3 pt-7 text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plant->is_active ?? true)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                Proposer cette plante dans les prochains conseils
            </label>
        </div>
    </section>
</div>
