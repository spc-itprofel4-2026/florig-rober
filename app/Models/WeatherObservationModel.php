<?php

namespace App\Models;

use CodeIgniter\Model;

class WeatherObservationModel extends Model
{
    protected $table         = 'weather_observations';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['city', 'temperature', 'humidity', 'condition', 'observed_at'];
}
