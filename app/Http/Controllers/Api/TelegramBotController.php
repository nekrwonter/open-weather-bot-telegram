<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;

class TelegramBotController extends Controller
{
    protected TelegramBotService $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
    }

    public function webhook()
    {
        $this->telegramBotService->boot();
        $this->telegramBotService->listen();

    }
}
