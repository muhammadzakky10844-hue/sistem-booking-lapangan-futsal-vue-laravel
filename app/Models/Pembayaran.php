<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $primaryKey = 'pembayaran_id';
    protected $table = 'pembayarans';

    protected $fillable = [
        'booking_id',
        'metode_pembayaran',
        'bukti_pembayaran',
        'status_verifikasi',
        'payment_gateway',
        'gateway_order_id',
        'gateway_transaction_id',
        'gateway_payment_type',
        'gateway_transaction_status',
        'gateway_fraud_status',
        'paid_at',
        'snap_token',
        'snap_redirect_url',
        'gateway_response',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function booking(){
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
