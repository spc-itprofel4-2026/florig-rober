<?php

namespace App\Models;

use CodeIgniter\Model;

class SuspiciousTransactionRecordModel extends Model
{
    protected $table         = 'fraud_review_records';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['transaction_id', 'user_id', 'risk_score', 'alert_status', 'alert_reason'];
}
