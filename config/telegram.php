<?php

return [
    'bot' => [
        'token' => env('TELEGRAM_BOT_TOKEN', ''),
        'hook_on_first_request' => env('TELEGRAM_BOT_HOOK_ON_FIRST_REQUEST', false)
    ]
];
