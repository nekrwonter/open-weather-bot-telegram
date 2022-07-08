<?php


namespace App\Utils\TelegramBot;


use Askoldex\Teletant\Context;

class Answer
{
    public static function permissionDenied(): callable
    {
        return function (Context $ctx) {
            $ctx->replyHTML(__('bot.permission-denied'));
        };
    }
}
