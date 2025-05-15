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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->text('optimizer_service_url')->default('https://lineup-optimizer-api.vercel.app/optimize');
            $table->integer('optimizer_timeout')->default(60);
            $table->integer('unlock_price_amount')->default(2);
            $table->string('unlock_currency')->default('usd');
            $table->string('unlock_currency_symbol')->default('$');
            $table->boolean('notify_admin_on_payment')->default(true);
            $table->string('admin_notification_email')->nullable();
            $table->enum('unlock_currency_symbol_position', ['before', 'after'])->default('before');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
