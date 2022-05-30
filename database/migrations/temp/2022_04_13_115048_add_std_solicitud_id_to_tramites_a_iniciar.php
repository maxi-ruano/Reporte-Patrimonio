<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStdSolicitudIdToTramitesAIniciar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tramites_a_iniciar', function (Blueprint $table) {
            $table->integer('std_solicitud_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tramites_a_iniciar', function (Blueprint $table) {
            $table->dropColumn('std_solicitud_id');
        });

    }
}
