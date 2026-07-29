<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AdviceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AdviceRequest;
use App\Models\Plant;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', AdviceRequest::class);

        $statusCounts = AdviceRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('dashboard', [
            'activePlants' => Plant::query()->active()->count(),
            'lowStockPlants' => Plant::query()->active()->whereBetween('stock_quantity', [1, 5])->count(),
            'pendingRequests' => (int) $statusCounts->get(AdviceRequestStatus::PENDING->value, 0)
                + (int) $statusCounts->get(AdviceRequestStatus::PROCESSING->value, 0),
            'completedRequests' => (int) $statusCounts->get(AdviceRequestStatus::COMPLETED->value, 0),
            'recentRequests' => AdviceRequest::query()
                ->withCount('recommendations')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
