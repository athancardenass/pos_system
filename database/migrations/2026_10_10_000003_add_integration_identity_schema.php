<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->string('crm_external_id', 120)->nullable()->unique();
            $table->boolean('crm_discount_eligible')->default(false);
            $table->string('crm_discount_type', 30)->nullable();
        });

        Schema::create('inventory_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->integer('pos_product_id')->unique();
            $table->string('inventory_sku', 100)->unique();
            $table->timestamps();
            $table->foreign('pos_product_id')->references('product_id')->on('product')->restrictOnDelete();
        });

        Schema::table('inventory_outbox_event', function (Blueprint $table) {
            $table->string('inventory_sku', 100)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_outbox_event', fn (Blueprint $table) => $table->dropColumn('inventory_sku'));
        Schema::dropIfExists('inventory_product_mappings');
        Schema::table('customer', function (Blueprint $table) {
            $table->dropUnique(['crm_external_id']);
            $table->dropColumn(['crm_external_id', 'crm_discount_eligible', 'crm_discount_type']);
        });
    }
};
