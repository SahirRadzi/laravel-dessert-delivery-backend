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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            //order_id
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            //reference Number
            $table->string('refno')->nullable();
            //status
            $table->string('status')->default('pending');
            //reason
            $table->string('reason')->nullable();
            //bill Code
            $table->string('billcode')->nullable();
            //amount
            $table->double('amount', 10,2);
            //Transaction Time
            $table->dateTime('transaction_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
