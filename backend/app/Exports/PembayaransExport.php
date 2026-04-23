<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PembayaransExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $pembayarans)
    {
    }

    public function collection(): Collection
    {
        return $this->pembayarans;
    }

    public function headings(): array
    {
        return [
            'ID Pembayaran',
            'ID Booking',
            'Nama Penyewa',
            'Lapangan',
            'Metode Pembayaran',
            'Status Verifikasi',
            'Status Gateway',
            'Referensi Gateway',
            'Waktu Transaksi',
        ];
    }

    public function map($pembayaran): array
    {
        $booking = $pembayaran->booking;
        $lapanganNomor = $booking?->lapangan?->nomor_lapangan;

        return [
            $pembayaran->pembayaran_id,
            $pembayaran->booking_id,
            $booking?->nama_penyewa ?? '-',
            'Lapangan ' . ($lapanganNomor ?? '-'),
            $pembayaran->metode_pembayaran ?? '-',
            ucfirst((string) $pembayaran->status_verifikasi),
            $pembayaran->gateway_transaction_status ?? '-',
            $pembayaran->gateway_order_id ?? ($pembayaran->gateway_transaction_id ?? '-'),
            optional($pembayaran->paid_at ?? $pembayaran->updated_at ?? $pembayaran->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
