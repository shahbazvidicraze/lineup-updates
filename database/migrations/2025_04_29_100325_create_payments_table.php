<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->integer('amount'); // Store amount in cents
            $table->string('currency', 3);
            $table->string('status'); // e.g., succeeded, processing, failed
            $table->timestamp('paid_at');
            $table->timestamps(); // Record creation/update time
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
