<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlantRequest;
use App\Http\Requests\Admin\UpdatePlantRequest;
use App\Models\Plant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PlantController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Plant::class);

        $status = $request->string('status')->toString();
        $stock = $request->string('stock')->toString();

        $plants = Plant::query()
            ->search($request->string('search')->toString())
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($stock === 'available', fn ($query) => $query->where('stock_quantity', '>', 0))
            ->when($stock === 'out', fn ($query) => $query->where('stock_quantity', 0))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.plants.index', [
            'plants' => $plants,
            'status' => $status,
            'stock' => $stock,
        ]);
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', Plant::class);

        $plants = Plant::onlyTrashed()
            ->search($request->string('search')->toString())
            ->orderByDesc('deleted_at')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.plants.archived', [
            'plants' => $plants,
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Plant::class);

        return view('admin.plants.create', $this->formOptions());
    }

    public function store(StorePlantRequest $request): RedirectResponse
    {
        Plant::query()->create($request->validated());

        return to_route('admin.plants.index')->with('status', 'La plante a été ajoutée au catalogue.');
    }

    public function edit(Plant $plant): View
    {
        Gate::authorize('view', $plant);

        return view('admin.plants.edit', array_merge(['plant' => $plant], $this->formOptions()));
    }

    public function update(UpdatePlantRequest $request, Plant $plant): RedirectResponse
    {
        $plant->update($request->validated());

        return to_route('admin.plants.index')->with('status', 'La plante a été mise à jour.');
    }

    public function deactivate(Plant $plant): RedirectResponse
    {
        Gate::authorize('update', $plant);

        $plant->update(['is_active' => false]);

        return to_route('admin.plants.index')->with('status', 'La plante a été désactivée.');
    }

    public function restore(Plant $plant): RedirectResponse
    {
        Gate::authorize('restore', $plant);

        $plant->restore();

        return to_route('admin.plants.archived')->with('status', 'La fiche a été restaurée. Elle reste inactive jusqu’à sa réactivation.');
    }

    public function destroy(Plant $plant): RedirectResponse
    {
        Gate::authorize('delete', $plant);

        $plant->update(['is_active' => false]);
        $plant->delete();

        return to_route('admin.plants.index')->with('status', 'La plante a été archivée.');
    }

    /**
     * @return array<string, array<int, object>>
     */
    private function formOptions(): array
    {
        return [
            'environments' => PlantEnvironment::cases(),
            'exposures' => Exposure::cases(),
            'levels' => Level::cases(),
        ];
    }
}
