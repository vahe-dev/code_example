<?php

namespace App\Http\Controllers\Internal;

use App\Enums\NotificationType;
use App\Enums\ReservoirBatteryStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Reservoir;
use App\Models\ReservoirMetric;
use App\Models\ReservoirNotificationsFrequency;
use App\Models\User;
use App\Notifications\LowBattery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservoirsController extends Controller
{
    /**
     * Update specific Reservoir's metrics by id, if you need to change all calculations for that reservoir
     *
     * @param int $reservoirId
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateMetrics(Request $request, int $reservoirId): JsonResponse
    {
        $firstMetricDate = null;
        $metricsDataFromFtp = $request->input('metrics');
        $deviceCode = Reservoir::where('id', $reservoirId)->value('device_code');

        if ($deviceCode == 200) {
            ReservoirMetric::where('reservoir_id',$reservoirId)
                ->where('date', '>=', config('constant.CURRENT_YEAR'))
                ->delete();

        } else {
            foreach ($metricsDataFromFtp as $metric) {
                if (isset($metric['date']) && $metric['date']) {
                    $firstMetricDate = Carbon::parse($metric['date'])->toDateTimeString();
                    break;
                }
            }
            ReservoirMetric::where('reservoir_id',$reservoirId)
                ->where('date', '>=', $firstMetricDate)
                ->delete();
        }

        $now = now()->toDateTimeString();

        $batteryStatus = ReservoirBatteryStatus::NORMAL;
        $batteryValue = null;

        $reservoir = Reservoir::find($reservoirId);

        $metrics = collect($request->input('metrics'))
            ->sortBy('date')
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
            })
            ->map(function ($metric) use (&$cumulativeSum) {
                // Calculate the cumulative sum and store it in cube_sum
                $cumulativeSum += $metric['q_cube'];
                $metric['cube_sum'] = round($cumulativeSum, 1);

                return $metric;
            })
            ->reject(function ($metric) {
                return empty($metric['date']);
            });

        foreach ($metrics->chunk(500) as $chunk) {
            ReservoirMetric::insert($chunk->toArray());
        }

        if($metrics->count() > 0) {
            $reservoir->last_metric_at = $now;
            $reservoir->q_sum = $metrics->sum('q_cube');
            $reservoir->q_sum = round($reservoir->q_sum, 1);
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
