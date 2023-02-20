<?php

namespace App\Models\DeliveryPrincipal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class DeliveryPrincipalModel extends Model
{
    use HasFactory;
    const tableName = 'forge';
    const CONNECTION_DB = 'db_principal';
    protected $connection = self::CONNECTION_DB;
}
