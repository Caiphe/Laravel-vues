<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('price', 'products_price_idx');
            $table->index('created_at', 'products_created_at_idx');
            $table->index(['category_id', 'price'], 'products_category_price_idx');
            $table->index(['category_id', 'created_at'], 'products_category_created_at_idx');
        });
    }
    
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_price_idx');
            $table->dropIndex('products_created_at_idx');
            $table->dropIndex('products_category_price_idx');
            $table->dropIndex('products_category_created_at_idx');
        });
    }
};
