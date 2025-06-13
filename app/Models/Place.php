<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;

    protected $fillable = ['mapbox_id', 'name', 'latitude', 'longitude'];

    public function flares()
    {
        return $this->hasMany(Flare::class);
    }
}