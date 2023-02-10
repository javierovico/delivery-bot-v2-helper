<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property array $output
 * @property array input
 * @property string tipo
 * @property mixed $id
 * @property mixed|null $external_id
 * @property int|null $update_id
 * @method static self findOrFail(array|string|null $peticionId)
 */
class LogTelegram  extends DefaultModel
{
    use HasFactory;
    const tableName = 'log_telegram';
    const COLUMNA_TIPO = 'tipo';
    protected $table = self::tableName;
    /**Columnas*/
    const COLUMNA_ID = 'id';
    const COLUMNA_EXTERNAL_ID = 'external_id';
    const COLUMNA_UPDATE_ID = 'update_id';
    const COLUMNA_INPUT = 'input';
    const COLUMNA_OUTPUT = 'output';

    /**Tipos de logs*/
    const TIPO_HOOK = 'hook';
    const TIPO_LOG_ONLY = 'logOnly';
    const TIPO_SEND_MESSAGE = 'sendMessage';
    const TIPO_RESEND = 'resend';

    /** Evita que haya repetidos y describe brevemente cada uno */
    const TIPO_LOGS = [
        self::TIPO_HOOK => 'Hook recibido por telegram',
        self::TIPO_SEND_MESSAGE => 'Respuesta de api para sendMessage',
    ];

    protected $casts = [
        self::COLUMNA_OUTPUT => 'array',
        self::COLUMNA_INPUT => 'array',
    ];

    protected $attributes = [
        self::COLUMNA_OUTPUT => '{}',
        self::COLUMNA_INPUT => '{}',
    ];

    public static function addLog(array $input, array $output, $tipo = self::TIPO_HOOK, $externalId = null, $upateId = null): LogTelegram
    {
        $nuevo = new self();
        $nuevo->output = $output;
        $nuevo->tipo = $tipo;
        $nuevo->input = $input;
        $nuevo->external_id = $externalId;
        $nuevo->update_id = $upateId;
        $nuevo->save();
        return $nuevo;
    }

}
