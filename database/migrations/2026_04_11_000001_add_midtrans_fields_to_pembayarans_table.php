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
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('status_verifikasi');
            $table->string('gateway_order_id')->nullable()->unique()->after('payment_gateway');
            $table->string('gateway_transaction_id')->nullable()->after('gateway_order_id');
            $table->string('gateway_payment_type')->nullable()->after('gateway_transaction_id');
            $table->string('gateway_transaction_status')->nullable()->after('gateway_payment_type');
            $table->string('gateway_fraud_status')->nullable()->after('gateway_transaction_status');
            $table->timestamp('paid_at')->nullable()->after('gateway_fraud_status');
            $table->string('snap_token')->nullable()->after('paid_at');
            $table->string('snap_redirect_url')->nullable()->after('snap_token');
            $table->longText('gateway_response')->nullable()->after('snap_redirect_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropUnique('pembayarans_gateway_order_id_unique');
            $table->dropColumn([
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
            ]);
        });
    }
};
