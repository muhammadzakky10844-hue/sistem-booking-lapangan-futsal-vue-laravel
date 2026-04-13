<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;

class PembayaranApiController extends Controller
{
    // POST /api/pembayaran/upload (public — penyewa upload bukti)
    public function upload(Request $request)
    {
        $request->validate([
            'booking_id'        => 'required|exists:bookings,booking_id',
            'metode_pembayaran' => 'required|string|max:255',
            'bukti_pembayaran'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $file     = $request->file('bukti_pembayaran');
        $namaFile = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('bukti_pembayaran'), $namaFile);

        // Hapus file lama jika ada
        $existing = Pembayaran::where('booking_id', $request->booking_id)->first();
        if ($existing && $existing->status_verifikasi === 'diterima') {
            return response()->json(['message' => 'Pembayaran untuk booking ini sudah lunas.'], 422);
        }

        if ($existing && $existing->bukti_pembayaran) {
            $oldPath = public_path('bukti_pembayaran/' . $existing->bukti_pembayaran);
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $pembayaran = Pembayaran::updateOrCreate(
            ['booking_id' => $request->booking_id],
            [
                'metode_pembayaran' => $request->metode_pembayaran,
                'bukti_pembayaran'  => $namaFile,
                'status_verifikasi' => 'menunggu',
                'payment_gateway' => 'manual',
                'gateway_order_id' => null,
                'gateway_transaction_id' => null,
                'gateway_payment_type' => null,
                'gateway_transaction_status' => null,
                'gateway_fraud_status' => null,
                'paid_at' => null,
                'snap_token' => null,
                'snap_redirect_url' => null,
                'gateway_response' => null,
            ]
        );

        $this->kirimNotifikasiWhatsappAdmin($pembayaran);

        return response()->json(['message' => 'Bukti pembayaran berhasil diupload.', 'pembayaran' => $pembayaran], 201);
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    // POST /api/pembayaran/midtrans/token
    public function createMidtransSnapToken(Request $request)
    {
        if (!$this->isMidtransConfigured()) {
            return response()->json([
                'message' => 'Konfigurasi Midtrans belum lengkap di server.',
            ], 503);
        }

        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
            'return_url' => 'nullable|url|max:500',
        ]);

        $booking = Booking::with('lapangan')->findOrFail($validated['booking_id']);

        if (in_array($booking->status, ['batal', 'selesai'], true)) {
            return response()->json([
                'message' => 'Booking ini tidak dapat dibayarkan karena statusnya tidak aktif.',
            ], 422);
        }

        $existing = Pembayaran::where('booking_id', $booking->booking_id)->first();
        if ($existing && $existing->status_verifikasi === 'diterima') {
            return response()->json([
                'message' => 'Booking ini sudah lunas.',
            ], 422);
        }

        try {
            $this->configureMidtrans();

            $orderId = 'BOOK-' . $booking->booking_id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
            $grossAmount = (int) $booking->total_harga;

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $booking->nama_penyewa,
                    'phone' => $booking->no_hp,
                ],
                'item_details' => [
                    [
                        'id' => 'lapangan-' . $booking->lapangan_id,
                        'price' => $grossAmount,
                        'quantity' => 1,
                        'name' => 'Sewa Lapangan ' . optional($booking->lapangan)->nomor_lapangan,
                    ],
                ],
            ];

            $baseReturnUrl = trim((string) ($validated['return_url'] ?? ''));
            if ($baseReturnUrl === '') {
                $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');
                if ($frontendUrl !== '') {
                    $baseReturnUrl = $frontendUrl . '/pembayaran/' . $booking->booking_id;
                }
            }

            if ($baseReturnUrl !== '') {
                $payload['callbacks'] = [
                    'finish' => $this->appendQuery($baseReturnUrl, ['midtrans_return' => 'success']),
                    'unfinish' => $this->appendQuery($baseReturnUrl, ['midtrans_return' => 'pending']),
                    'error' => $this->appendQuery($baseReturnUrl, ['midtrans_return' => 'error']),
                ];
            }

            $transaction = Snap::createTransaction($payload);
            $snapToken = $transaction->token ?? null;
            $snapRedirectUrl = $transaction->redirect_url ?? null;

            if (empty($snapToken)) {
                throw new \RuntimeException('Gagal membuat Snap token.');
            }

            Pembayaran::updateOrCreate(
                ['booking_id' => $booking->booking_id],
                [
                    'metode_pembayaran' => 'Midtrans',
                    'bukti_pembayaran' => null,
                    'status_verifikasi' => 'menunggu',
                    'payment_gateway' => 'midtrans',
                    'gateway_order_id' => $orderId,
                    'gateway_transaction_id' => null,
                    'gateway_payment_type' => null,
                    'gateway_transaction_status' => 'pending',
                    'gateway_fraud_status' => null,
                    'paid_at' => null,
                    'snap_token' => $snapToken,
                    'snap_redirect_url' => $snapRedirectUrl,
                    'gateway_response' => [
                        'snap_request' => $payload,
                    ],
                ]
            );

            return response()->json([
                'message' => 'Snap token berhasil dibuat.',
                'snap_token' => $snapToken,
                'redirect_url' => $snapRedirectUrl,
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat Snap token Midtrans', [
                'booking_id' => $booking->booking_id,
                'message' => $e->getMessage(),
            ]);

            $statusCode = 500;
            $message = 'Gagal memulai pembayaran online. Coba beberapa saat lagi.';
            $errorText = strtolower($e->getMessage());

            if (str_contains($errorText, 'http status code: 401') || str_contains($errorText, 'unauthorized transaction')) {
                $statusCode = 502;
                $message = 'Akses Midtrans ditolak (401). Cek Server Key dan mode sandbox/production.';
            }

            return response()->json([
                'message' => $message,
            ], $statusCode);
        }
    }

    // POST /api/pembayaran/midtrans/notification
    public function midtransNotification(Request $request)
    {
        if (!$this->isMidtransConfigured()) {
            return response()->json(['message' => 'Midtrans belum dikonfigurasi.'], 503);
        }

        $payload = $request->all();
        $requiredKeys = ['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                return response()->json(['message' => 'Payload notifikasi Midtrans tidak lengkap.'], 422);
            }
        }

        if (!$this->isValidMidtransSignature($payload)) {
            return response()->json(['message' => 'Signature Midtrans tidak valid.'], 403);
        }

        $bookingId = $this->extractBookingIdFromOrderId((string) $payload['order_id']);
        if (!$bookingId) {
            return response()->json(['message' => 'Format order_id Midtrans tidak dikenali.'], 422);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        $this->applyMidtransPayloadToBooking($booking, $payload);

        return response()->json(['message' => 'Notifikasi Midtrans diproses.']);
    }

    // POST /api/pembayaran/midtrans/sync
    public function syncMidtransStatus(Request $request)
    {
        if (!$this->isMidtransConfigured()) {
            return response()->json([
                'message' => 'Konfigurasi Midtrans belum lengkap di server.',
            ], 503);
        }

        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
            'order_id' => 'nullable|string|max:100',
            'transaction_status' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
            'payment_type' => 'nullable|string|max:50',
            'fraud_status' => 'nullable|string|max:50',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $pembayaran = Pembayaran::where('booking_id', $booking->booking_id)->first();

        $orderId = trim((string) ($validated['order_id'] ?? ''));
        if ($orderId === '') {
            $orderId = (string) ($pembayaran->gateway_order_id ?? '');
        }

        if ($orderId === '') {
            return response()->json([
                'message' => 'Order ID Midtrans tidak ditemukan untuk booking ini.',
            ], 422);
        }

        $clientTxStatus = trim((string) ($validated['transaction_status'] ?? ''));
        if ($clientTxStatus !== '') {
            $clientPayload = [
                'order_id' => $orderId,
                'transaction_status' => $clientTxStatus,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_type' => $validated['payment_type'] ?? null,
                'fraud_status' => $validated['fraud_status'] ?? null,
            ];

            $updatedPembayaran = $this->applyMidtransPayloadToBooking($booking, $clientPayload);

            return response()->json([
                'message' => 'Status pembayaran berhasil diproses dari callback Midtrans.',
                'source' => 'client_callback',
                'pembayaran' => $updatedPembayaran,
                'booking_status' => $booking->fresh()->status,
            ]);
        }

        try {
            $this->configureMidtrans();
            $status = Transaction::status($orderId);
            $payload = json_decode(json_encode($status), true) ?: [];

            if (empty($payload['transaction_status'])) {
                return response()->json([
                    'message' => 'Status transaksi Midtrans tidak valid.',
                ], 502);
            }

            if (empty($payload['order_id'])) {
                $payload['order_id'] = $orderId;
            }

            $updatedPembayaran = $this->applyMidtransPayloadToBooking($booking, $payload);

            return response()->json([
                'message' => 'Status Midtrans berhasil disinkronkan.',
                'source' => 'midtrans_api',
                'pembayaran' => $updatedPembayaran,
                'booking_status' => $booking->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            $errorText = strtolower($e->getMessage());

            if (str_contains($errorText, 'http status code: 404') || str_contains($errorText, "transaction doesn't exist")) {
                Log::warning('Status Midtrans belum tersedia (404)', [
                    'booking_id' => $booking->booking_id,
                    'order_id' => $orderId,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Status transaksi Midtrans belum tersedia. Silakan cek lagi sebentar.',
                    'source' => 'midtrans_api',
                    'booking_status' => $booking->status,
                ], 202);
            }

            Log::error('Gagal sinkronisasi status Midtrans', [
                'booking_id' => $booking->booking_id,
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            if (str_contains($errorText, 'http status code: 401') || str_contains($errorText, 'unauthorized transaction')) {
                return response()->json([
                    'message' => 'Akses Midtrans ditolak (401). Cek Server Key dan mode sandbox/production.',
                ], 502);
            }

            return response()->json([
                'message' => 'Gagal sinkronisasi status Midtrans.',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'        => 'nullable|in:menunggu,diterima,ditolak',
            'search'        => 'nullable|string|max:100',
            'uploaded_date' => 'nullable|date',
            'per_page'      => 'nullable|integer|min:5|max:100',
        ]);

        $query = Pembayaran::with('booking.lapangan')->latest();

        if (!empty($validated['status'])) {
            $query->where('status_verifikasi', $validated['status']);
        }
        if (!empty($validated['search'])) {
            $query->whereHas('booking', function ($q) use ($validated) {
                $q->where('nama_penyewa', 'like', '%' . $validated['search'] . '%');
            });
        }
        if (!empty($validated['uploaded_date'])) {
            $query->whereDate('created_at', $validated['uploaded_date']);
        }

        $perPage = $validated['per_page'] ?? 10;

        return response()->json($query->paginate($perPage));
    }

    public function terima(Request $request)
    {
        $request->validate(['pembayaran_id' => 'required|exists:pembayarans,pembayaran_id']);
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'diterima']);
        $pembayaran->booking->update(['status' => 'terkonfirmasi']);
        return response()->json(['message' => 'Pembayaran berhasil diterima.']);
    }

    public function tolak(Request $request)
    {
        $request->validate(['pembayaran_id' => 'required|exists:pembayarans,pembayaran_id']);
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'ditolak']);
        $pembayaran->booking->update(['status' => 'menunggu']);
        return response()->json(['message' => 'Pembayaran ditolak.']);
    }

    private function isMidtransConfigured(): bool
    {
        $serverKey = trim((string) config('services.midtrans.server_key', ''));

        return (bool) config('services.midtrans.enabled', false)
            && !empty($serverKey);
    }

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = trim((string) config('services.midtrans.server_key', ''));
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production', false);
        MidtransConfig::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('services.midtrans.is_3ds', true);
    }

    private function extractBookingIdFromOrderId(string $orderId): ?int
    {
        if (preg_match('/^BOOK-(\d+)-/i', $orderId, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function isValidMidtransSignature(array $payload): bool
    {
        $serverKey = trim((string) config('services.midtrans.server_key', ''));
        $signatureSource =
            (string) $payload['order_id'] .
            (string) $payload['status_code'] .
            (string) $payload['gross_amount'] .
            $serverKey;

        $expectedSignature = hash('sha512', $signatureSource);
        return hash_equals($expectedSignature, (string) $payload['signature_key']);
    }

    private function mapMidtransStatus(string $transactionStatus, string $fraudStatus): array
    {
        $tx = strtolower($transactionStatus);
        $fraud = strtolower($fraudStatus);

        if ($tx === 'settlement' || ($tx === 'capture' && $fraud !== 'challenge')) {
            return [
                'status_verifikasi' => 'diterima',
                'booking_status' => 'terkonfirmasi',
                'is_paid' => true,
            ];
        }

        if ($tx === 'pending' || ($tx === 'capture' && $fraud === 'challenge')) {
            return [
                'status_verifikasi' => 'menunggu',
                'booking_status' => 'menunggu',
                'is_paid' => false,
            ];
        }

        if (in_array($tx, ['deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund', 'chargeback'], true)) {
            return [
                'status_verifikasi' => 'ditolak',
                'booking_status' => 'menunggu',
                'is_paid' => false,
            ];
        }

        return [
            'status_verifikasi' => 'menunggu',
            'booking_status' => 'menunggu',
            'is_paid' => false,
        ];
    }

    private function applyMidtransPayloadToBooking(Booking $booking, array $payload): Pembayaran
    {
        $mapped = $this->mapMidtransStatus(
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? '')
        );

        $pembayaran = Pembayaran::firstOrNew(['booking_id' => $booking->booking_id]);

        // Do not downgrade a payment that is already verified as paid.
        if ($pembayaran->status_verifikasi === 'diterima' && $mapped['status_verifikasi'] === 'menunggu') {
            $mapped['status_verifikasi'] = 'diterima';
            $mapped['booking_status'] = 'terkonfirmasi';
            $mapped['is_paid'] = true;
        }

        $pembayaran->fill([
            'metode_pembayaran' => $this->resolveMetodePembayaran($payload),
            'status_verifikasi' => $mapped['status_verifikasi'],
            'payment_gateway' => 'midtrans',
            'gateway_order_id' => $payload['order_id'] ?? $pembayaran->gateway_order_id,
            'gateway_transaction_id' => $payload['transaction_id'] ?? $pembayaran->gateway_transaction_id,
            'gateway_payment_type' => $payload['payment_type'] ?? $pembayaran->gateway_payment_type,
            'gateway_transaction_status' => $payload['transaction_status'] ?? $pembayaran->gateway_transaction_status,
            'gateway_fraud_status' => $payload['fraud_status'] ?? $pembayaran->gateway_fraud_status,
            'paid_at' => $mapped['is_paid'] ? now() : $pembayaran->paid_at,
            'gateway_response' => $payload,
        ]);
        $pembayaran->save();

        if (!empty($mapped['booking_status']) && $booking->status !== $mapped['booking_status']) {
            $booking->update(['status' => $mapped['booking_status']]);
        }

        return $pembayaran;
    }

    private function resolveMetodePembayaran(array $payload): string
    {
        $paymentType = strtolower((string) ($payload['payment_type'] ?? 'midtrans'));

        if ($paymentType === 'bank_transfer') {
            $bank = null;
            if (!empty($payload['va_numbers']) && is_array($payload['va_numbers'])) {
                $bank = $payload['va_numbers'][0]['bank'] ?? null;
            }
            if (!$bank && !empty($payload['permata_va_number'])) {
                $bank = 'permata';
            }

            return $bank
                ? 'Midtrans - VA ' . strtoupper((string) $bank)
                : 'Midtrans - Bank Transfer';
        }

        if ($paymentType === 'echannel') {
            return 'Midtrans - Mandiri Bill';
        }

        return 'Midtrans - ' . Str::upper(str_replace('_', ' ', $paymentType));
    }

    private function appendQuery(string $url, array $query): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $existingQuery = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existingQuery);
        }

        $mergedQuery = array_merge($existingQuery, $query);

        $rebuilt = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        if (!empty($mergedQuery)) {
            $rebuilt .= '?' . http_build_query($mergedQuery);
        }

        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function kirimNotifikasiWhatsappAdmin(Pembayaran $pembayaran): void
    {
        $enabled = (bool) config('services.fonnte.enabled', false);
        $token = config('services.fonnte.token');
        $target = config('services.fonnte.target');

        if (!$enabled || empty($token) || empty($target)) {
            return;
        }

        try {
            $booking = Booking::with('lapangan')->find($pembayaran->booking_id);
            if (!$booking) {
                return;
            }

            $baseUrl = rtrim((string) config('app.url'), '/');
            $buktiUrl = $baseUrl . '/bukti_pembayaran/' . rawurlencode((string) $pembayaran->bukti_pembayaran);

            $message = implode("\n", [
                'Pembayaran baru masuk.',
                'Penyewa: ' . $booking->nama_penyewa,
                'Lapangan: ' . optional($booking->lapangan)->nomor_lapangan,
                'Jadwal: ' . $booking->tanggal_booking . ' ' . substr((string) $booking->jam_mulai, 0, 5) . '-' . substr((string) $booking->jam_selesai, 0, 5),
                'Metode: ' . $pembayaran->metode_pembayaran,
                'Waktu upload: ' . now()->format('d-m-Y H:i'),
                'Bukti: ' . $buktiUrl,
                'Silakan cek dashboard admin pembayaran.',
            ]);

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::warning('Gagal kirim notifikasi WhatsApp admin', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Exception kirim notifikasi WhatsApp admin', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
