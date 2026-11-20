<?php

namespace App\Models;

use CodeIgniter\Model;

class WeatherLogModel extends Model
{
    protected $table            = 'weather_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'city',
        'temperature',
        'fetched_at',
    ];
}
