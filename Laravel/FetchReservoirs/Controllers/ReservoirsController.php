<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAllReservoirsRequest;
use App\Models\Reservoir;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ReservoirsController extends Controller
{
    /**
     * Show all reservoirs.
     * Can be filtered by search query(reservoir name or group name) and company ID.
     * Limit can be passed from client side.
     *
     * @param ListAllReservoirsRequest $request
     * @return LengthAwarePaginator
    */
    public function index(ListAllReservoirsRequest $request): LengthAwarePaginator
    {
        $query = Reservoir::with([
            'group' => fn ($query) => $query->select('id', 'parent_id', 'name'),
            'group.parentGroup' => fn ($query) => $query->select('id', 'name')
        ]);

        $request->whenFilled('query', function (string $searchQuery) use ($query) {
            // Escape special characters in the search query and replace consecutive spaces
            $searchQuery = preg_replace('/\s+/', '\\s+', preg_quote($searchQuery, '/'));

            $query->where(function ($query) use ($searchQuery){
                $query->orWhere('reservoirs.name', 'REGEXP', $searchQuery)
                    ->orWhere('reservoirs.serial_no', 'REGEXP', $searchQuery)
                    ->orWhereHas('group', function ($query) use ($searchQuery) {
                        $query->where('groups.name', 'REGEXP', $searchQuery);
                    });
            });
        });

        $request->whenFilled('company_id', function (string $groupId) use ($query){
            $query
                ->whereHas('group', function ($query) use ($groupId){
                    $query->whereHas('parentGroup', function ($query) use ($groupId){
                        $query->whereId($groupId);
                    });
                });
        });

        return $query
            ->select(
                'reservoirs.id',
                'reservoirs.group_id',
                'reservoirs.device_id',
                'reservoirs.name',
                'reservoirs.serial_no',
                'reservoirs.status',
                'reservoirs.folder_path'
            )
            ->orderBy('id', 'desc')
            ->paginate($request->input('limit', 100));
    }
}
