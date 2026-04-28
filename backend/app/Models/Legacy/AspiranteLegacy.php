<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AspiranteLegacy extends Model
{
    protected $connection = 'legacy_sqlite';
    protected $table = 'base_general';

    public $timestamps = false;
    public $incrementing = true;
    protected $primaryKey = 'ID';
    protected $keyType = 'int';

    protected $guarded = [];
}