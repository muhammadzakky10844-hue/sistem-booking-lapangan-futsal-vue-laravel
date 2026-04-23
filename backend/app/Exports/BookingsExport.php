<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $bookings)
    {
    }

    public function collection(): Collection
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'ID Booking',
            'Nama Penyewa',
            'No HP',
            'Lapangan',
            'Tanggal Booking',
            'Jam',
            'Total Harga',
            'Status',
            'Dibuat Pada',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->booking_id,
            $booking->nama_penyewa,
            $booking->no_hp,
            'Lapangan ' . ($booking->lapangan->nomor_lapangan ?? '-'),
            $booking->tanggal_booking,
            substr((string) $booking->jam_mulai, 0, 5) . ' - ' . substr((string) $booking->jam_selesai, 0, 5),
            (int) $booking->total_harga,
            ucfirst((string) $booking->status),
            optional($booking->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
