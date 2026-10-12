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
        Schema::create('scheme_payment_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('kegiatan_id')->unique();
            $table->unsignedBigInteger('price');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('order_id', 50)->unique();
            $table->unsignedInteger('kegiatan_id')->nullable()->index();
            $table->string('scheme_title', 180);
            $table->unsignedBigInteger('amount');
            $table->string('customer_name', 120);
            $table->string('customer_email', 180)->index();
            $table->string('customer_phone', 30);
            $table->string('status', 30)->index();
            $table->string('midtrans_status', 30)->nullable();
            $table->string('midtrans_transaction_id', 80)->nullable()->index();
            $table->string('payment_type', 50)->nullable();
            $table->string('fraud_status', 30)->nullable();
            $table->text('status_message')->nullable();
            $table->text('midtrans_redirect_url')->nullable();
            $table->json('notification_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('scheme_payment_settings');
    }
};
