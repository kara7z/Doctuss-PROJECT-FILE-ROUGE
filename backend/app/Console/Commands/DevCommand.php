<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    protected $signature = 'dev';
    protected $description = 'Run full development environment';

    public function handle()
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        $commands = [
            'php artisan serve',
            'php artisan queue:listen --tries=1 --timeout=0',
            'npm run dev',
        ];

        if (!$isWindows) {
            $commands[] = 'php artisan pail --timeout=0';
        }

        $this->info("Starting dev environment...");

        $processes = [];

        foreach ($commands as $cmd) {
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(null);
            $process->start();

            $processes[] = $process;
        }

        while (true) {
            sleep(1);
        }
    }
}
