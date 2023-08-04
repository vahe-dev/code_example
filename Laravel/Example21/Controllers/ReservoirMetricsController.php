<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\GetReservoirMetricsRequest;
use App\Models\ReservoirMetric;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservoirMetricsController extends Controller
{
    /**
     * Get cube sum by date for filter
     *
     * @param int $reservoirId
     * @param GetReservoirMetricsRequest $request
     * @return mixed
     */
    public function cubeSumByDate(int $reservoirId, GetReservoirMetricsRequest $request)
    {
        return ReservoirMetric::where('reservoir_id', '=', $reservoirId)
            ->when($request->filled('date_before'), function ($query) use ($request) {
                $query->where('date', '<=', $request->input('date_before'));
            })
            ->when($request->filled('date_after'), function ($query) use ($request) {
                $query->where('date', '>=', $request->input('date_after'));
            })
            ->sum('q_cube');
    }

    /**
     * Get data by date for export xlsx file
     *
     * @param int $reservoirId
     * @param GetReservoirMetricsRequest $request
     * @return mixed
     */
    public function exportMetrics(int $reservoirId, GetReservoirMetricsRequest $request)
    {
        $query = ReservoirMetric::whereHas('reservoir', function ($query) use ($reservoirId) {
            $query->whereId($reservoirId)
                ->whereHas('group', function ($query) {
                    if (Auth::user()->hasRole(Role::ADMIN->value)) {
                        return $query;
                    }
                    $query->whereHas('mapping', function ($query) {
                        $query->when(Auth::user()->hasRole(Role::USER->value), fn ($query) => $query->whereUserId(Auth::id()));
                    });
                });
        })
            ->when($request->filled('date_before'), function ($query) use($request) {
                $query->where('date', '<=', $request->input('date_before'));
            })
            ->when($request->filled('date_after'), function ($query) use($request) {
                $query->where('date', '>=', $request->input('date_after'));
            });
            return $query->get(['q_cube', 'h', 'date', 'q_second']);
    }
}
