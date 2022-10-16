<?php

namespace App\Console\Commands;

use App\Consumer;
use Illuminate\Console\Command;

class StartConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consumer:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start kafka consumer';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Consumer::consume();
    }
}
