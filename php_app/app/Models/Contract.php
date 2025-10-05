<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'user_id', 'title', 'content', 'internal_serial', 'client_contract_no',
        'client_name', 'client_id_number', 'client_phone', 'client_address', 
        'investment_amount', 'capital_amount', 'profit_percent', 'profit_interval_months',
        'withdrawal_notice_days', 'start_date_h', 'end_date_h', 'commission_percent',
        'exit_notice_days', 'penalty_amount', 'signature_path', 'template_text',
        'status', 'manager_note', 'approved_at', 'rejected_at'
    ];

    protected $casts = [
        'investment_amount' => 'decimal:2',
        'capital_amount' => 'decimal:2',
        'profit_percent' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
