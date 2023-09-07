<?php

namespace App\Http\Controllers;

use App\Exports\ExportNotifications;
use App\Http\Requests\ListAllNotificationsRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationsController extends Controller
{
    /**
     * Retrieve a paginated list of notifications based on the given request.
     *
     * @param ListAllNotificationsRequest $request The request object containing the filter criteria.
     *
     * @return LengthAwarePaginator The paginated list of notifications.
     */
    public function index(ListAllNotificationsRequest $request): LengthAwarePaginator
    {
        $query = Notification::with('reservoir')
            ->whereHas('reservoir', function ($query) use($request) {
                if ($request->notification_type === 'App\\Notifications\\LowBattery') {
                    $query->where('is_unavailable', '!=', 1)
                    ->where(function ($query) {
                        $query->where('battery_value', '=', 1)->orWhere('has_low_battery', '=', 1);
                    });
                } elseif ($request->notification_type === 'App\\Notifications\\NoUpdatesFromReservoir') {
                    $query->where('is_unavailable', '=', 1);
                } else {
                    $query->where('battery_value', '=', 1)->orWhere('has_low_battery', '=', 1)->orWhere('is_unavailable', '=', 1);
                }
            })
            ->whereNotifiableType(User::class)
            ->whereNotifiableId(Auth::id());

        $request->whenFilled('notification_type', fn (string $type) => $query->whereType($type));

        if($request->input('archived')) {
            $query->whereNotNull('read_at');
        } else {
            $query->whereReadAt(null);
        }
        $query->orderBy('created_at', 'DESC');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($request->input('limit', 15));
        $paginator->getCollection()->transform(function(Notification $notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'resource' => $notification->specific_resource_type::find($notification->specific_resource_id),
                'created_at' => $notification->created_at
            ];
        });

        return $paginator;
    }
}
