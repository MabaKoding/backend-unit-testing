<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debit_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_card_id')
                ->constrained('debit_cards')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->integer('amount');
            $table->string('currency_code');
            $table->timestamps();
            $table->softDeletes();
            
            // $table->unsignedInteger('debit_card_id');
            // $table->foreign('debit_card_id')
            //     ->references('id')
            //     ->on('debit_cards')
            //     ->onUpdate('cascade')
            //     ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('debit_card_transactions');
        Schema::enableForeignKeyConstraints();
    }
}
