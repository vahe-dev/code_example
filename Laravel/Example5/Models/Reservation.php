<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'building_id',
        'title',
        'type_id',
        'image',
        'description',
        'created_by',
        'start_date',
        'end_date'
    ];

    /**
     * Get the building.
     */
    public function building()
    {
        return $this->belongsTo('App\Models\Building', 'building_id');
    }

    /**
     * Get the resident reservations.
     */
    public function residentReservations()
    {
        return $this->hasMany('App\Models\ReservationResident');
    }

    /**
     * Get the reservation durations.
     */
    public function durations()
    {
        return $this->belongsToMany('App\Models\Duration', 'reservation_duration');
    }

    /**
     * Get the reservation times.
     */
    public function times()
    {
        return $this->belongsToMany('App\Models\Time', 'reservation_time');
    }
}
