<?php

namespace Hwkdo\IntranetAppDokumente\Commands;

use Illuminate\Console\Command;

class IntranetAppDokumenteCommand extends Command
{
    public $signature = 'intranet-app-dokumente';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
