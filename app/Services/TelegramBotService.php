<?php


namespace App\Services;


use App\Models\User;
use App\Utils\TelegramBot\Answer;
use App\Utils\TelegramBot\Qu;

use App\Utils\TelegramBot\Scenes;
use App\Utils\TelegramBot\Storage;
use Askoldex\Teletant\Interfaces\StorageInterface;
use Askoldex\Teletant\Addons\Menux;
use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Entities\Location;
use Askoldex\Teletant\Entities\PollAnswer;
use Askoldex\Teletant\Exception\TeletantException;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;
use Illuminate\Support\Carbon;
use RakibDevs\Weather\Exceptions\WeatherException;
use RakibDevs\Weather\Weather;
use Spatie\Emoji\Emoji;


class TelegramBotService
{
    protected Bot $bot;
    protected UserService $userService;


    public function __construct(Bot $bot, UserService $userService)
    {
        $this->bot = $bot;
        $this->userService = $userService;

    }

    public function boot(): void
    {
        $this->bootEvents();
        $this->bootMiddlewares();
    }

    public function bootStage(): Stage
    {
        $stage = new Stage();
//        $stage->addScene(Scenes::getCityScene());
        $stage->addScenes(...Scenes::getAllScenes());
        return $stage;
    }


    public function bootMiddlewares()
    {

        $this->bot->middlewares([
                                    function (Context $ctx, callable $next) {
                                        $user = $this->userService->registerTelegramUser($ctx->getUserID());
                                        $ctx->getContainer()
                                            ->singleton(User::class, function () use ($user) {
                                                return $user;
                                            });

                                        $next($ctx);
                                    },
                                    function (Context $ctx, callable $next) {
                                        /** @var User $user */
                                        $user = $ctx->getContainer()->get(User::class);
                                        $storage = new Storage($user);
                                        $ctx->setStorage($storage);
                                        $ctx->setStage($this->bootStage());
                                        $next($ctx);
                                    },
                                    $this->bootStage()->middleware()
                                ]);
    }

    public function bootEvents()
    {

        Menux::create('Главное меню',1)->autoRows([
            Menux::Button('Узнать погоду '.Emoji::sun()),
            Menux::Button('Уведовления о погоде '.Emoji::calendar())],1);
        Menux::create('Узнать погоду',2)->autoRows([
            Menux::Button('По названию'.Emoji::memo()),
            Menux::Button('По геолокации'.Emoji::worldMap()),
            Menux::Button('Назад')],1);
        Menux::create('По названию',3)
            ->btn('Назад');
        Menux::create('По геолокации',4)
            ->lbtn('Отправить мою геолокацию'.Emoji::worldMap())
            ->btn('Назад');
        Menux::create('Подписка города',5)
            ->btn('Выбрать как мой город для уведомлений.')
            ->btn('Не нужно');
        Menux::create('Подписка',6)
            ->btn('Да')
            ->btn('Нет');
        Menux::create('Найти для подписки',7)
            ->btn('На главную');
        $this->bot->onCommand('start', function (Context $ctx) {
            $ctx->enter('home');
//            $ctx->replyHTML('Привет, скидывай свою геопозицию и получай погоду! ',Menux::Get('1'));
        });

//        $this->bot->onText('Поиск по названию', function (Context $ctx){
//            $ctx->setStage($this->bootStage());
//            $ctx->enter('city');
//        });


    }

    /**
     * @throws TeletantException
     */
    public function polling()
    {
        try {

            $this->bot->polling();

        }
        catch (\Exception $e){
            info($e);
        }
    }

    /**
     * @throws TeletantException
     */
    public function listen()
    {
        $this->bot->listen();
    }
}
