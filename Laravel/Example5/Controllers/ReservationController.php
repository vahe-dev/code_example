<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Traits\File;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    use File;

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('reservations')->where(function ($query) use ($request) {
                    return $query->where('title', $request->title)
                        ->where('building_id', $request->buildingId);
                })],
            'durations' => 'required',
            'times' => 'required',
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->getMessageBag()]);
        }

        $newReservation = new Reservation();

        if (isset($request->buildingId)) $newReservation->building_id = (int)$request->buildingId;
        if (isset($request->type_id)) $newReservation->type_id = (int)$request->type_id;
        if (isset($request->createdBy)) $newReservation->created_by = (int)$request->createdBy;
        if (isset($request->title)) $newReservation->title = $request->title;
        if (isset($request->description)) $newReservation->description = $request->description;
        if (isset($request->startDate)) $newReservation->start_date = $request->startDate;
        if (isset($request->endDate)) $newReservation->end_date = $request->endDate;

        if ($newReservation->save()) {
            //save image
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $this->setDirectory('public/images/reservations/' . $newReservation->id);
                $uploadImageResult = $this->upload($image);
                if ($uploadImageResult['success']) {
                    $newReservation->image = $uploadImageResult['name'];
                    $newReservation->save();
                }
            }

            if ($request->durations) {
                $newReservation->durations()->sync($request->durations);
            }

            if ($request->times) {
                $newReservation->times()->sync($request->times);
            }

            return response()->json(['status' => true, 'reservation' => $newReservation]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateData(Request $request, int $id)
    {
        $oldImage = '';
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('reservations')->where(function ($query) use ($request) {
                    return $query->where('title', $request->title)
                        ->where('building_id', $request->buildingId);
                })->ignore($id)
            ],
            'durations' => 'required',
            'times' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->getMessageBag()]);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) return response()->json(['status' => false, 'message' => 'Invalid data']);

        if (isset($request->buildingId)) $reservation->building_id = (int)$request->buildingId;
        if (isset($request->createdBy)) $reservation->created_by = (int)$request->createdBy;
        if (isset($request->title)) $reservation->title = $request->title;
        if (isset($request->startDate)) $reservation->start_date = $request->startDate;
        if (isset($request->endDate)) $reservation->end_date = $request->endDate;
        if (isset($request->description)) {
            $reservation->description = $request->description;
        } else {
            if ($reservation->description) {
                $reservation->description = null;
            }
        }

        if ($request->hasFile('image')) {
            $this->setDirectory('public/images/reservations/' . $reservation->id);
            $image = $request->file('image');
            $uploadImageResult = $this->upload($image);
            if ($uploadImageResult['success']) {
                if ($reservation->image) {
                    $oldImage = $reservation->image;
                }

                $reservation->image = $uploadImageResult['name'];
            }
        } else if (!$request->image) {
            if ($reservation->image) {
                $this->setDirectory('public/images/reservations/' . $reservation->id);
                if ($this->delete($reservation->image)) {
                    $reservation->image = null;
                }
            }
        }

        if ($reservation->save()) {
            if ($request->hasFile('image')) {
                if ($oldImage) {
                    $this->setDirectory('public/images/reservations/' . $reservation->id);
                    $this->delete($oldImage);
                }
            }

            if ($request->durations) {
                $reservation->durations()->sync($request->durations);
            }

            if ($request->times) {
                $reservation->times()->sync($request->times);
            }

            return response()->json(['status' => true, 'reservation' => $reservation]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id)
    {
        $toDeleteReservation = Reservation::find($id);
        if (!$toDeleteReservation) {
            return response()->json(['success' => false]);
        }

        if ($toDeleteReservation->delete()) {
            if ($toDeleteReservation->image) {
                $this->setDirectory('public/images/reservations/' . $toDeleteReservation->id);
                $this->delete($toDeleteReservation->image);
            }
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
