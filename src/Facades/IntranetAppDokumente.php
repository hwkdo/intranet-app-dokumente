<?php

namespace Hwkdo\IntranetAppDokumente\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\IntranetAppDokumente\IntranetAppDokumente
 */
class IntranetAppDokumente extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\IntranetAppDokumente\IntranetAppDokumente::class;
    }
}
