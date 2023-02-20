<?php

namespace App\Models\DeliveryPrincipal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 * @see Bot::COLUMNA_CODIGO
 * @property string $codigo             codigo del bot actual
 * @see Bot::COLUMNA_TOKEN_BOT
 * @property string $token_bot          token del bot (de ser necesario para envios de mensajes de respuesta)
 * @see Bot::COLUMNA_NOMBRE
 * @property string $nombre
 * @see Bot::COLUMNA_DESCRIPCION
 * @property string $descripcion
 * @see Bot::COLUMNA_TOKEN_API
 * @property string $token_api
 * @property mixed $id
 */
class Bot extends DeliveryPrincipalModel
{
    use HasFactory;
    const tableName = 'bots';
    protected $table = self::tableName;
    /**Columnas*/
    const COLUMNA_ID = 'id';
    const COLUMNA_CODIGO = 'codigo';
    const COLUMNA_NOMBRE = 'nombre';
    const COLUMNA_TOKEN_BOT = 'token_bot';
    const COLUMNA_TOKEN_API = 'token_api';
    const COLUMNA_DESCRIPCION = 'descripcion';
    const COLUMNA_TIPO_CLIENTE_ID = 'tipo_cliente_id';

    /** Bots definidos previamente */
    const BOT_DELIVERY_SIMPLE = 'delivery_simple';

    public static function getByCode($value, $throwOnFail = false): ?self
    {
        $query = self::where(self::COLUMNA_CODIGO, $value);
        if ($throwOnFail) {
            return $query->firstOrFail();
        } else {
            return $query->first();
        }
    }
}
