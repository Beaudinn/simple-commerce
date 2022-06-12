<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_shipped')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->json('items')->nullable();
            $table->integer('grand_total')->default(0);
            $table->integer('rush_total')->default(0);
            $table->integer('items_total')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('shipping_total')->default(0);
            $table->integer('coupon_total')->default(0);
	        $table->integer('upsell_total')->default(0);

	        $table->string('shipping_company_name')->nullable();
	        $table->string('shipping_first_name')->nullable();
	        $table->string('shipping_last_name')->nullable();
	        $table->string('shipping_phone')->nullable();
	        $table->string('shipping_postal_code')->nullable();
	        $table->string('shipping_house_number')->nullable();
	        $table->string('shipping_addition')->nullable();
	        $table->string('shipping_street')->nullable();
	        $table->string('shipping_city')->nullable();
	        $table->string('shipping_country')->default('NL');
	        $table->string('billing_company_name')->nullable();
	        $table->string('billing_first_name')->nullable();
	        $table->string('billing_last_name')->nullable();
	        $table->string('billing_phone')->nullable();
	        $table->string('billing_postal_code')->nullable();
	        $table->string('billing_house_number')->nullable();
	        $table->string('billing_addition')->nullable();
	        $table->string('billing_street')->nullable();
	        $table->string('billing_city')->nullable();
	        $table->string('billing_country')->default('NL');
            $table->boolean('use_shipping_address_for_billing')->default(false);
	        $table->timestamp('delivery_at')->nullable();
	        $table->timestamp('shipping_method')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('coupon')->nullable();
            $table->json('gateway')->nullable();
            $table->json('data')->nullable();
            $table->dateTime('paid_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
