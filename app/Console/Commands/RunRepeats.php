<?php

namespace App\Console\Commands;

use App\Support\RepeatRunner;
use Illuminate\Console\Command;

class RunRepeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-repeats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create weekly repeat orders due today (add to cron: * * * * * cd app && php artisan schedule:run).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $result = RepeatRunner::runDue();
        $this->info("Repeat orders created: {$result['created']}, skipped: {$result['skipped']}.");

        return self::SUCCESS;
    }
}
