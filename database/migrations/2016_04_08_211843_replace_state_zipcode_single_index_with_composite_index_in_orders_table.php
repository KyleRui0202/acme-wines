<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReplaceStateZipcodeSingleIndexWithCompositeIndexInOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_state_index');
            $table->dropIndex('orders_zipcode_index');
            $table->index(['state', 'zipcode'], 'orders_state_zipcode_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_state_zipcode_index');
            $table->index('state', 'orders_state_index');
            $table->index('zipcode', 'orders_zipcode_index');
        });
    }
}
