<?php

use App\Models\DeliveryPrincipal\Bot;
use App\Models\LogTelegram;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTelegramLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(LogTelegram::tableName, function (Blueprint $table) {
            $table->unsignedBigInteger(LogTelegram::COLUMNA_BOT_ID)->nullable()->index();
        });
        if ($bot = Bot::getByCode(Bot::BOT_DELIVERY_SIMPLE)) {
            LogTelegram::query()->lazyById()->each(function(LogTelegram $logTelegram) use (&$bot) {
                $logTelegram->bot_id = $bot->id;
                $logTelegram->save();
            });
        }
        Schema::table(LogTelegram::tableName, function (Blueprint $table) {
            $table->unsignedBigInteger(LogTelegram::COLUMNA_BOT_ID)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(LogTelegram::tableName, function (Blueprint $table) {
            $table->dropIndex([LogTelegram::COLUMNA_BOT_ID]);
            $table->dropColumn(LogTelegram::COLUMNA_BOT_ID);
        });
    }
}
