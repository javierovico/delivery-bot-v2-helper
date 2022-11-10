<?php

use App\Models\LogTelegram;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogTelegramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(LogTelegram::CONNECTION_DB)->create(LogTelegram::tableName, function (Blueprint $table) {
            $table->id(LogTelegram::COLUMNA_ID);
            $table->unsignedBigInteger(LogTelegram::COLUMNA_EXTERNAL_ID)->unique();
            $table->string(LogTelegram::COLUMNA_TIPO,100);
            $table->json(LogTelegram::COLUMNA_INPUT);
            $table->json(LogTelegram::COLUMNA_OUTPUT);
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
        Schema::connection(LogTelegram::CONNECTION_DB)->dropIfExists(LogTelegram::tableName);
    }
}
