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
        Schema::table('items', function (Blueprint $table) {
            $table->index('status', 'idx_items_status');
            $table->index('category_id', 'idx_items_category_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status', 'idx_transactions_status');
            $table->index('user_id', 'idx_transactions_user_id');
            $table->index('item_id', 'idx_transactions_item_id');
            $table->index(['user_id', 'status'], 'idx_transactions_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_status');
            $table->dropIndex('idx_items_category_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_status');
            $table->dropIndex('idx_transactions_user_id');
            $table->dropIndex('idx_transactions_item_id');
            $table->dropIndex('idx_transactions_user_status');
        });
    }
};
