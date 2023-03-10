<?php

use App\Models\ReservedTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use Yajra\Datatables\Facades\Datatables;

class CalendarController extends BaseController
{
    /**
     * Get all reservations, which have been cancelled
     * */
    public function getCalendarForCall(Request $request)
    {
        $getForCallTable = ReservedTime::with(['user', 'form'])
            ->whereIn('canceled', [1, 2, 5])
            ->orderBy('improved_date', 'desc')
            ->orderBy('improved_hour', 'asc')
            ->get();
        return Datatables::of($getForCallTable)
            ->addColumn('my_improved_date', function ($getForCallTable) {
                return Carbon::parse($getForCallTable->improved_date)->format('d.m.Y');
            })
            ->addColumn('action', function ($getForCallTable) {
                return '<input type="checkbox" name="selected_hour[]" value="' . $getForCallTable->id . '">';
            })
            ->addColumn('my_email', function ($getForCallTable) {
                return '<a data-toggle="tooltip" title="Отправить электронное письмо"  class="forAdminSendEmail">' . (!is_null($getForCallTable->user) ? $getForCallTable->user->email : '-') . '</a>';
            })
            ->addColumn('my_name', function ($getForCallTable) {
                return '<a data-toggle="tooltip" title="Посмотреть персональную информацию" href="/admin/users/data/' . (!is_null($getForCallTable->user) ? $getForCallTable->user->id : '-') . '">' . (!is_null($getForCallTable->user) ? $getForCallTable->user->name : '-') . '</a>';
            })
            ->addColumn('my_phone', function ($getForCallTable) {
                return (is_null($getForCallTable->user) || is_null($getForCallTable->user->details))
                    ? '-'
                    : '<a data-toggle="tooltip" title="Позвонить по skype" href="callto:' . $getForCallTable->user->details->phone . '" class="generate-call" >' . $getForCallTable->user->details->phone . '</a><span data-user-id="' . $getForCallTable->user->id . '" class="userPhone"><i class="fa fa-pencil"></i></span>';
            })
            ->addColumn('my_note', function ($getForCallTable) {
                return '<span data-reserve-id="' . $getForCallTable->id . '" class="reserveNote">' . $getForCallTable->note . '<i class="fa fa-pencil"></i></span>';
            })
            ->addColumn('my_skype', function ($getForCallTable) {
                return (is_null($getForCallTable->skype_login))
                    ? '-'
                    : $getForCallTable->skype_login;
            })
            ->addColumn('my_id', function ($getForCallTable) {
                return '<a data-toggle="tooltip" title="Посмотреть данные о заказе" href="/admin/resConsultation/' . $getForCallTable->id . '">' . $getForCallTable->id . '</a>';;
            })
            ->addColumn('my_cons_status', function ($getForCallTable) {
                return ($getForCallTable->canceled == 1) ? 'Бронь истекла' : (($getForCallTable->canceled == 5) ? 'На прозвон' : 'Отменено');
            })
            ->addColumn('my_diff', function ($getForCallTable) {
                $dth = explode(":", $getForCallTable->improved_hour);
                $dtd = explode("-", $getForCallTable->improved_date);
                $dt = Carbon::now();
                $dt->year = $dtd[0];
                $dt->month = $dtd[1];
                $dt->day = $dtd[2];
                $dt->hour = $dth[0];
                $dt->minute = $dth[1];
                $diff = (Carbon::now()->diff($dt)->d < 2 && Carbon::now()->diff($dt)->invert == 0) ?
                    ((Carbon::now()->diff($dt)->d < 1)
                        ? ((Carbon::now()->diff($dt)->h < 1)
                            ? Carbon::now()->diff($dt)->i . 'м'
                            : Carbon::now()->diff($dt)->h . 'ч ' . Carbon::now()->diff($dt)->i . 'м')
                        : Carbon::now()->diff($dt)->d . 'д ' . Carbon::now()->diff($dt)->h . 'ч ' . Carbon::now()->diff($dt)->i . 'м')
                    : '-';
                return $diff;
            })
            ->filter(function ($instance) use ($request) {
                if ($request->has('isCanceled')) {
                    $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                        return ($row['canceled'] == $request->get('isCanceled')) ? true : false;
                    });
                }
                if ($request->has('dateStart')) {
                    $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                        return (Carbon::parse($row['improved_date'])->gte(Carbon::parse($request->get('dateStart')))) ? true : false;
                    });
                }
                if ($request->has('dateEnd')) {
                    $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                        return (Carbon::parse($row['improved_date'])->lte(Carbon::parse($request->get('dateEnd')))) ? true : false;
                    });
                }
                if ($request->has('keyword')) {
                    $instance->collection = $instance->collection->filter(function ($row) use ($request) {
                        return (Str::contains(strtolower($row['id']), strtolower($request->get('keyword')))
                            || Str::contains(strtolower(isset($row['phone']) ? $row['user']['phone'] : ''), strtolower($request->get('keyword')))
                            || Str::contains(strtolower(isset($row['user']) ? $row['user']['name'] : ''), strtolower($request->get('keyword')))
                            || Str::contains(strtolower(isset($row['email']) ? $row['user']['email'] : ''), strtolower($request->get('keyword')))
                            || Str::contains(strtolower($row['note']), strtolower($request->get('keyword')))
                            || Str::contains(strtolower($row['improved_date']), strtolower($request->get('keyword')))
                            || Str::contains(strtolower($row['improved_hour']), strtolower($request->get('keyword')))) ? true : false;
                    });
                }
            })
            ->rawColumns(['action', 'my_note', 'my_name', 'my_email', 'my_phone', 'my_id'])
            ->setRowClass(function ($getForCallTable) {
                $dth = explode(":", $getForCallTable->improved_hour);
                $dtd = explode("-", $getForCallTable->improved_date);
                $dt = Carbon::now();
                $dt->year = $dtd[0];
                $dt->month = $dtd[1];
                $dt->day = $dtd[2];
                $dt->hour = $dth[0];
                $dt->minute = $dth[1];
                return ($getForCallTable->payed == 1) ? 'success' : 'danger';
            })
            ->make(true);
    }
}