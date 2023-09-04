<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\Reservoir;
use App\Models\ReservoirMetric;
use App\Models\ReservoirNotificationsFrequency;
use App\Models\User;
use App\Notifications\NoUpdatesFromReservoir;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLastUpdatesOfReservoirs implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $admins = User::whereHas('roles', fn ($query) => $query->whereName(Role::ADMIN->value) )->get();

        $reservoirsIds = Reservoir::select('id')->get()->pluck('id')->toArray();
        $metrics = ReservoirMetric::with('reservoir.group.mapping.user')
            ->select('reservoir_id', DB::raw('MAX(date) as date'))
            ->whereIn('reservoir_id', $reservoirsIds)
            ->whereNotNull('date')
            ->groupBy('reservoir_id')
            ->get();

        $unavailableReservoirs = $availableReservoirs = [];

        foreach ($metrics as $metric) {
            if (is_null($metric->date)) {
                Log::info('ReservoirID - ' . $metric->reservoir_id . ', Date - ' . $metric->date);
            }
            if($metric->date->addHours(1) < now()) {
                foreach ($metric->reservoir->group->mapping as $map) {
                    $map->user->notify(new NoUpdatesFromReservoir($metric->reservoir, $metric->date->toDateTimeString()));
                }

                foreach ($admins as $admin) {
                    $admin->notify(new NoUpdatesFromReservoir($metric->reservoir, $metric->date->toDateTimeString()));
                }

                $unavailableReservoirs[] = $metric->reservoir_id;
            }

            else {
                $availableReservoirs[] = $metric->reservoir_id;
            }
        }

        Reservoir::whereIn('id', $unavailableReservoirs)->update(['is_unavailable' => true, 'has_low_battery' => false, 'has_null_battery' => false]);
        Reservoir::whereIn('id', $availableReservoirs)->update(['is_unavailable' => false]);
        ReservoirNotificationsFrequency::whereIn('reservoir_id', $availableReservoirs)->whereType(NotificationType::NoUpdatesFromReservoir->value)->delete();
    }
}
