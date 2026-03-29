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
    ];

    public function booking(){
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
