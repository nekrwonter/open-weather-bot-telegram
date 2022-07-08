<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class DailyNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bot =  app(TelegramBotService::class);

        return 0;
    }
}
