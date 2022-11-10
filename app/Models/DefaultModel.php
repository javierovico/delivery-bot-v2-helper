<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class DefaultModel extends Model
{
    use HasFactory;
    const tableName = 'forge';
    const CONNECTION_DB = 'mysql';
    protected $connection = self::CONNECTION_DB;
}
