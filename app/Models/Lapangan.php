<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lapangan extends Model
{
    protected $primaryKey = 'lapangan_id';
    protected $table = 'lapangans';
    protected $fillable = [
        'nomor_lapangan',
        'harga_per_jam',
        'deskripsi',
        'gambar',
        'status',
    ];

    public function bookings(){
        return $this->hasMany(Booking::class);
    }
}
