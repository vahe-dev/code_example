<?php

namespace App\Http\Controllers\Internal;

use App\Enums\NotificationType;
use App\Enums\ReservoirBatteryStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Reservoir;
use App\Models\ReservoirMetric;
use App\Models\ReservoirNotificationsFrequency;
use App\Notifications\LowBattery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservoirsController extends Controller
{
    /**
     * Update specific Reservoir's metrics by Id, if need to change all calculations for that reservoir
     *
     * @param int $reservoirId
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateMetrics(Request $request, int $reservoirId): JsonResponse
    {
        ReservoirMetric::where('reservoir_id',$reservoirId)->delete();

        $now = now()->toDateTimeString();

        $batteryStatus = ReservoirBatteryStatus::NORMAL;
        $batteryValue = null;

        $reservoir = Reservoir::find($reservoirId);

        $metrics = collect($request->input('metrics'))
            ->sortBy('date') // Important for get the latest values of $batteryStatus, $batteryValue
            ->map(function ($metric) use ($reservoirId, &$batteryStatus, &$batteryValue) {
                $metric['reservoir_id'] = $reservoirId;
                $metric['q_second'] = $metric['qSecond'] ?? null;
                $metric['q_cube'] = $metric['qCube'] ?? null;
                $metric['height'] = $metric['height'] ?? null;
                $metric['h'] = $metric['h'] ?? null;
                $metric['battery'] = $metric['battery'] ?? null;
                $metric['ping'] = $metric['ping'] ?? null;
                $metric['date'] = isset($metric['date']) && $metric['date'] ? Carbon::parse($metric['date'])->toDateTimeString() : null;

                $batteryValue = $metric['battery'];

                if(is_null($metric['battery'])) {
                    $batteryStatus = ReservoirBatteryStatus::UNDEFINED;
                }
                else if($metric['battery'] < 3500) {
                    $batteryStatus = ReservoirBatteryStatus::LOW;
                }
                else {
                    $batteryStatus = ReservoirBatteryStatus::NORMAL;
                }

                unset($metric['qSecond']);
                unset($metric['qCube']);

                return $metric;
            });

        ReservoirMetric::insert($metrics->toArray());

        if($metrics->count() > 0) {
            $reservoir->last_metric_at = $now;
            $reservoir->q_sum = $metrics->sum('q_cube');
        }

        if ($batteryStatus === ReservoirBatteryStatus::NORMAL) {
            $reservoir->has_low_battery = false;
            $reservoir->has_null_battery = false;
            ReservoirNotificationsFrequency::whereReservoirId($reservoirId)
                ->whereType(NotificationType::LowBattery->value)->delete();
        }
        else {
            $this->notifyReservoirUserAboutLowBattery($reservoirId, $batteryValue);

            if ($batteryStatus === ReservoirBatteryStatus::LOW) {
                $reservoir->has_low_battery = true;
            }
            else {
                $reservoir->has_null_battery = true;
            }
        }

        $reservoir->save();

        return response()->json();
    }

    /**
     * Send a notification to all admins and users who have relation with given reservoir about low battery value.
     *
     * @param int $reservoirId
     * @param float|null $batteryLevel
     * @return void
    */
    protected function notifyReservoirUserAboutLowBattery(int $reservoirId, float|null $batteryLevel): void
    {
        $users = User::whereHas('groups', function ($query) use($reservoirId) {
            $query->whereHas('reservoirs', function ($query) use($reservoirId) {
                $query->whereId($reservoirId);
            });
        })
            ->orWhereHas('roles', function ($query) {
                $query->whereName(Role::ADMIN->value);
            })
            ->get();

        $reservoir = Reservoir::find($reservoirId);

        foreach ($users as $user) {
            $user->notify(new LowBattery($reservoir, $batteryLevel));
        }
    }
}
