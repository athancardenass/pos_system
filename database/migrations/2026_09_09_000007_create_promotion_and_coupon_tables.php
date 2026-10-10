<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promotion engine rule tables.
     *
     * promotion = rule-based discount (auto-applied at checkout, product/category/cart scope)
     * coupon    = code-based discount entered by the cashier (one-time or limited use)
     *
     * The legacy manual `discount` table is untouched: it stays the cashier's manual
     * discount picker. These tables sit next to it and are consumed by PromotionService.
     *
     * Schema::hasTable() guards: the sandbox and the canonical htdocs copy share one
     * MariaDB server, so a table created from the other copy must not re-run.
     */
    public function up(): void
    {
        if (! Schema::hasTable('promotion')) {
            Schema::create('promotion', function (Blueprint $table) {
                $table->integer('promotion_id', true);
                $table->string('name', 100);
                $table->enum('type', ['percentage', 'fixed', 'bundle_price', 'buy_x_get_y']);
                // Percent (0.01-100) or peso amount; null for the quantity-driven types.
                $table->decimal('value', 10, 2)->nullable();
                $table->enum('scope', ['product', 'category', 'cart'])->default('product');
                // product_id when scope=product, category_id when scope=category, null for cart.
                $table->integer('scope_id')->nullable();
                // bundle_price only: bundle_qty units for bundle_price pesos total.
                $table->integer('bundle_qty')->nullable();
                $table->decimal('bundle_price', 10, 2)->nullable();
                // buy_x_get_y only: every x_qty units bought makes y_qty units free.
                $table->integer('x_qty')->nullable();
                $table->integer('y_qty')->nullable();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'starts_at', 'ends_at'], 'promotion_active_window_index');
                $table->index(['scope', 'scope_id'], 'promotion_scope_index');
            });
        }

        if (! Schema::hasTable('coupon')) {
            Schema::create('coupon', function (Blueprint $table) {
                $table->integer('coupon_id', true);
                // Always stored uppercase (case-insensitive matching happens at write time).
                $table->string('code', 40)->unique('coupon_code_unique');
                $table->string('description', 255)->nullable();
                $table->enum('type', ['percentage', 'fixed']);
                $table->decimal('value', 10, 2);
                $table->decimal('min_purchase', 10, 2)->default(0);
                // null = unlimited uses; used_count is bumped under a row lock at checkout.
                $table->integer('max_uses')->nullable();
                $table->integer('used_count')->default(0);
                // null = unlimited per customer (walk-ins skip the check entirely).
                $table->integer('per_customer_limit')->nullable();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'starts_at', 'ends_at'], 'coupon_active_window_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon');
        Schema::dropIfExists('promotion');
    }
};
