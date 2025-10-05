<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'user_id', 'title', 'content', 'client_name', 'client_id_number', 'client_phone', 'client_address', 'investment_amount', 'signature_path', 'status', 'internal_serial'
    ];
}
