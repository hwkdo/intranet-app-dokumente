<?php

namespace Hwkdo\IntranetAppDokumente\Models;

use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;

class IntranetAppDokumenteSettings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): IntranetAppDokumenteSettings|null
    {
        return self::orderBy('version', 'desc')->first();
    }
}
