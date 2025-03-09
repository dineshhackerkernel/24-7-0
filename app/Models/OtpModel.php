<?php

namespace App\Models;

use CodeIgniter\Model;

class OtpModel extends Model
{
    protected $table = 'otps';
    protected $primaryKey = 'id';
    protected $allowedFields = ['phone', 'otp', 'created_at'];
}
