<?php


namespace App\Services;


use App\Models\User;

class UserService
{
    public function registerTelegramUser(int $telegramID): User
    {
        return User::firstOrCreate([
            'telegram_id' => $telegramID
        ]);
    }
    public function saveLast(int $telegramID,string $city): User
    {
        $user = User::where('telegram_id',$telegramID);
        $user->update(['last_city'=>$city]);

    }
}
