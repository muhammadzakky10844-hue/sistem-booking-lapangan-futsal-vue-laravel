<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('nama_penyewa');
            $table->string('no_hp');
            $table->text('alamat')->nullable();
            $table->unsignedBigInteger('lapangan_id');
            $table->foreign('lapangan_id')->references('lapangan_id')->on('lapangans')->onDelete('cascade');
            $table->date('tanggal_booking');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('total_harga');
            $table->enum('status',['menunggu', 'terkonfirmasi', 'selesai', 'batal'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
