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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            //user_id
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            //restaurant_id
            $table->foreignId('restaurant_id')->constrained('users')->onDelete('cascade');
            //driver_id
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            //total price
            $table->double('total_price', 10,2);
            //shipping_cost
            $table->double('shipping_cost', 10,2);
            //maintenance_cost
            $table->double('maintenance_cost', 10,2)->nullable();
            //total bill
            $table->double('total_bill', 10,2);
            //payment method
            $table->string('payment_method')->nullable();
            //reference Number
            $table->string('refno')->nullable();
            //status
            $table->string('status')->default('pending');
            //bill Code
            $table->string('billcode')->nullable();
            //Transaction Time
            $table->dateTime('transaction_time')->nullable();
            //shipping address
            $table->text('shipping_address')->nullable();
            //shipping latlong
            $table->string('shipping_latlong')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
