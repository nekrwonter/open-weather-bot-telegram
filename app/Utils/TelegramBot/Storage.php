<?php


namespace App\Utils\TelegramBot;


use App\Models\User;
use Askoldex\Teletant\Interfaces\StorageInterface;

class Storage implements StorageInterface
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function setScene(string $sceneName)
    {
        $this->user->update([
            'scene' => $sceneName
        ]);
    }

    public function getScene(): string
    {
        return $this->user->scene ?? '';
    }

    public function setTtl(string $sceneName, int $seconds)
    {
        // TODO: Implement setTtl() method.
    }

    public function getTtl(string $sceneName)
    {
        // TODO: Implement getTtl() method.
    }
}
