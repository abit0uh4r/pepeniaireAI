<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AdviceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AdviceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AdviceRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AdviceRequest::class);

        $search = trim($request->string('search')->toString());
        $statusValue = strtoupper($request->string('status')->toString());
        $status = AdviceRequestStatus::tryFrom($statusValue);

        $adviceRequests = AdviceRequest::query()
            ->withCount('recommendations')
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status->value))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.advice-requests.index', [
            'adviceRequests' => $adviceRequests,
            'statuses' => AdviceRequestStatus::cases(),
            'selectedStatus' => $status?->value ?? '',
            'search' => $search,
        ]);
    }

    public function show(AdviceRequest $adviceRequest): View
    {
        Gate::authorize('view', $adviceRequest);

        $adviceRequest->load('recommendations.plant');

        return view('admin.advice-requests.show', [
            'adviceRequest' => $adviceRequest,
        ]);
    }
}
