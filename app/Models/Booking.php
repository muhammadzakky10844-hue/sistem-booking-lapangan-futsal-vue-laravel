<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    protected $table = 'bookings';
    protected $fillable = [
        'nama_penyewa',
        'no_hp',
        'alamat',
        'lapangan_id',
        'tanggal_booking',
        'jam_mulai',
        'jam_selesai',
        'total_harga',
        'status',
    ];
    
    public function lapangan(){
        return $this->belongsTo(Lapangan::class, 'lapangan_id', 'lapangan_id');
    }

    public function pembayaran(){
        return $this->hasOne(Pembayaran::class, 'booking_id', 'booking_id');
    }
}
