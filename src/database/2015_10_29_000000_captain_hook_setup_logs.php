<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CaptainHookSetupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('captain_hook_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('webhook_id')->unsigned()->nullable();
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('set null');
            $table->string('url');
            $table->string('payload_format');
            $table->text('payload');
            $table->integer('status');
            $table->text('response');
            $table->string('response_format');
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
        Schema::drop('captain_hook_logs');
    }
}
