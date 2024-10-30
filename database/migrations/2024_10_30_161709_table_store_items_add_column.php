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
        //
        Schema::table('store_items', function (Blueprint $table) {
            $table->string('item_place')->nullable()->after('item_quantity')->comment('物品位置');
            $table->text('item_pic_url')->nullable()->after('item_place')->comment('物品圖片');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
