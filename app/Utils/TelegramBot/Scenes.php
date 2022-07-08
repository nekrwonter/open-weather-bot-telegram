<?php


namespace App\Utils\TelegramBot;


use App\Models\User;
use App\Services\UserService;
use Askoldex\Teletant\Addons\Keyboard;
use Askoldex\Teletant\Addons\Menux;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Entities\ChosenInlineResult;
use Askoldex\Teletant\States\Scene;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RakibDevs\Weather\Exceptions\WeatherException;
use RakibDevs\Weather\Weather;
use Spatie\Emoji\Emoji;

class Scenes
{


    public static function getHomeScene(): Scene
    {

        $scene = new Scene('home');
        $scene->onEnter(function (Context $ctx) {
            $ctx->replyHTML('Привет, скидывай свою геопозицию и получай погоду! ', Menux::Get(1));
        });
        $scene->onHears('Узнать погоду '.Emoji::sun(),function (Context $ctx){
            $ctx->enter('variantGetWeather');
        });
        $scene->onHears('Уведовления о погоде '.Emoji::calendar(),function (Context $ctx){
            $ctx->enter('subscribe');
        });
        return  $scene;
    }

    public static function chooseVariantScene(): Scene{
        $scene = new Scene('variantGetWeather');

        $scene->onEnter(function (Context $ctx) {
            $ctx->replyHTML('Выбери подходящий вариант.',Menux::Get(2));
        });

        $scene->onHears('По названию'.Emoji::memo(),function (Context $ctx){
            $ctx->enter('city');
        });
        $scene->onHears('По геолокации'.Emoji::worldMap(),function (Context $ctx){
            $ctx->enter('location');
        });
        $scene->onHears('Назад',function (Context $ctx){
            if ($ctx->getText() === 'Назад'){
                $ctx->enter('home');
            }
        });
        $scene->onLeave(function (Context $ctx) {

//            $ctx->replyHTML('Выбирете пункт меню. '.Emoji::sun(),$menu);
        });
        return  $scene;
    }

    public static function getCityScene(): Scene
    {
        $scene = new Scene('city');
        $scene->onEnter(function (Context $ctx) {
            $ctx->replyHTML('Отправь мне название города.',Menux::Get(3));
        });
        $scene->onHears('Назад',function (Context $ctx){
            if ($ctx->getText() === 'Назад'){
                $ctx->enter('variantGetWeather');
            }
        });
        $scene->onUpdate('callback_query',function (Context $ctx){
            $message = $ctx->CallbackQuery()->data();

            $user = User::all()->where('telegram_id','=',$ctx->getUserID());
            if('Выберете день'.Emoji::calendar()===$ctx->CallbackQuery()->message()->text()){
                try {
                    $city = explode(' cityname: ',$message);
                    $wt = new Weather();
                    $info = $wt->get3HourlyByCity($city[1]);
                    $weather = array();
                    $str = $city[1].PHP_EOL.PHP_EOL;
                    $buttons = [];

                    foreach ($info->list as $list){
                        $weather = [];
                        foreach ($list->weather as $w){
                            array_push($weather,$w->description);
                        }
                        Carbon::setLocale('ru');

                        $date = Carbon::parse($list->dt)->isoFormat('dddd, D MMM');
                        if($date === $city[0]){
                            $str.=Str::title(Carbon::parse($list->dt)->isoFormat('dddd, D MMM, HH:mm')).PHP_EOL.
                                'Ожидается: '.PHP_EOL.PHP_EOL.implode(',',$weather).'.'.PHP_EOL.
                                'Температура: '.PHP_EOL.
                                'Текущая - '.$list->main->temp.' °C '.Emoji::thermometer().PHP_EOL.
                                'Чуствуется как - '.$list->main->feels_like.' °C '.Emoji::thermometer().PHP_EOL.
                                'Мин.  - '.$list->main->temp_min.' °C '.Emoji::thermometer().PHP_EOL.
                                'Макс. - '.$list->main->temp_max.' °C '.Emoji::thermometer().PHP_EOL.
                                'Вероятность осадков: '.$list->clouds->all.'% '.Emoji::CHARACTER_CLOUD_WITH_RAIN.PHP_EOL.
                                'Скорость ветра: '.$list->wind->speed.' м/с '.Emoji::windFace().PHP_EOL.PHP_EOL;
                        }

                    }
                    try {
                        $ctx->editSelf($str,Menux::Get(9));

                    }
                    catch (\Exception $e){
                        Menux::Create('Назад к дням',9)->inline()->autoRows([Menux::Button('Назад к дням',"$city[1]")],1);
                        $ctx->editSelf($str,Menux::Get(9));

                    }



                } catch (WeatherException $e){

//                    $ctx->replyHTML('Такого места не найдено, либо где-то ошибка в написании. '.Emoji::thinkingFace());
                }
            }
            if('Назад к дням'===$ctx->CallbackQuery()->message()->text()){
                $city = $message;
                $ctx->editSelf('Выберете день'.Emoji::calendar(),Menux::Get(8));
            }
            else{
                try {
                    $city = Str::words($message,1,'');
                    $wt = new Weather();
                    $info = $wt->get3HourlyByCity($city);
                    $weather = array();
                    $str = "";
                    $buttons = [];

                    foreach ($info->list as $list){
                        $weather = [];
                        foreach ($list->weather as $w){
                            array_push($weather,$w->description);
                        }
                        Carbon::setLocale('ru');
                        $date = Carbon::parse($list->dt)->isoFormat('dddd, D MMM');
                        $button = Menux::Button($date,"{$date} cityname: {$city}");
                        if (!in_array($button,$buttons))
                            array_push($buttons,$button);
                    }
                    try {
                        $ctx->editSelf('Выберете день'.Emoji::calendar(),Menux::Get(8));

                    }
                    catch (\Exception $e){
                        Menux::Create('Выбор дня',8)->inline()->autoRows($buttons,2);
                        $ctx->editSelf('Выберете день'.Emoji::calendar(),Menux::Get(8));

                    }





                } catch (WeatherException $e){

//                    $ctx->replyHTML('Такого места не найдено, либо где-то ошибка в написании. '.Emoji::thinkingFace());
                }
            }

        });


        $scene->onHears('{city:string}', function (Context $ctx){
            $city = $ctx->getText();

                $btn=Menux::Create('Погода на 5 дней')->inline()->btn('Погода на 5 дней',"{$city} на 5 дней");
                try {
                    $wt = new Weather();
                    $info = $wt->getCurrentByCity($city);
                    $weather = array();
                    foreach ($info->weather as $w){
                        array_push($weather,$w->description);
                    }

                    $ctx->replyHTML('Погода в '.$info->name.'.'.PHP_EOL.PHP_EOL.
                                    'Cегодня: '.PHP_EOL.PHP_EOL.implode(',',$weather).'.'.PHP_EOL.PHP_EOL.
                                    'Температура: '.PHP_EOL.
                                    'Текущая - '.$info->main->temp.' °C '.Emoji::thermometer().PHP_EOL.
                                    'Чуствуется как - '.$info->main->feels_like.' °C '.Emoji::thermometer().PHP_EOL.
                                    'Мин.  - '.$info->main->temp_min.' °C '.Emoji::thermometer().PHP_EOL.
                                    'Макс. - '.$info->main->temp_max.' °C '.Emoji::thermometer().PHP_EOL.PHP_EOL.
                                    'Вероятность осадков: '.$info->clouds->all.'% '.Emoji::CHARACTER_CLOUD_WITH_RAIN.PHP_EOL.
                                    'Скорость ветра: '.$info->wind->speed.' м/с '.Emoji::windFace().PHP_EOL.PHP_EOL.
                                    'Страна: '.Emoji::countryFlag($info->sys->country),$btn
                    );
                    try {
                        $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
                        $user->last_city = $city;
                        $user->save();
                    }
                    catch (\Exception $e){
                        info($e);
                    }

                } catch (WeatherException $e){

                    $ctx->replyHTML('Такого места не найдено, либо где-то ошибка в написании. '.Emoji::thinkingFace());
                }

        });

        $scene->onLeave(function (Context $ctx) {

        });

        return $scene;
    }

    public static function locationScene(): Scene{
        $scene = new Scene('location');

        $scene->onEnter(function (Context $ctx) {
            $ctx->replyHTML('Отправь мне геолокацию.',Menux::Get(4));
        });

        $scene->onMessage('location',function (Context $ctx){
            try {
                $location = $ctx->getMessage()->location();
                $wt = new Weather();
                $city = $wt->getGeoByCord($location->latitude(), $location->longitude())[0];
                $info = $wt->getCurrentByCity($city->name);
                $weather = array();
                foreach ($info->weather as $w){
                    array_push($weather,$w->description);
                }
                $ctx->replyHTML('Погода в '.$info->name.'.'.PHP_EOL.PHP_EOL.
                                'Cегодня:'.PHP_EOL.implode(',',$weather).'.'.PHP_EOL.PHP_EOL.
                                'Температура: '.PHP_EOL.
                                'Текущая - '.$info->main->temp.' °C '.Emoji::thermometer().PHP_EOL.
                                'Чуствуется как - '.$info->main->feels_like.' °C '.Emoji::thermometer().PHP_EOL.
                                'Мин.  - '.$info->main->temp_min.' °C '.Emoji::thermometer().PHP_EOL.
                                'Макс. - '.$info->main->temp_max.' °C '.Emoji::thermometer().PHP_EOL.PHP_EOL.
                                'Вероятность осадков: '.$info->clouds->all.'% '.Emoji::CHARACTER_CLOUD_WITH_RAIN.PHP_EOL.PHP_EOL.
                                'Скорость ветра: '.$info->wind->speed.' м/с '.Emoji::windFace().PHP_EOL.PHP_EOL.
                                'Страна: '.Emoji::countryFlag($info->sys->country)
                );
                try {
                    $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
                    $user->last_city = $info->name;
                    $user->save();
                }
                catch (\Exception $e){
                    info($e);
                }
            }
            catch (\Exception $e){
                $ctx->replyHTML('Такого места не найдено, либо где-то ошибка в написании. '.Emoji::thinkingFace());

            }

        });

        $scene->onHears('Назад',function (Context $ctx){
            if ($ctx->getText() === 'Назад'){
                $ctx->enter('variantGetWeather');
            }
        });

        $scene->onLeave(function (Context $ctx) {

//            $ctx->replyHTML('Выбирете пункт меню. '.Emoji::sun(),$menu);
        });
        return  $scene;
    }

    public static function subscribeScene(): Scene{
        $scene = new Scene('subscribe');

        $scene->onEnter(function (Context $ctx) {
            $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
            $last_city = $user->last_city;
            if($last_city!==null){
                $ctx->replyHTML('Ваш город '.$last_city.'?',Menux::Get(6));

            }
            else{
                $ctx->enter('findToSubscribe');
            }
        });
        $scene->onHears('Да',function (Context $ctx){
            if ($ctx->getText() === 'Да'){
                $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
                $user->city = $user->last_city;
                $user->save();
                $ctx->replyHTML('Город установлен как ваш. Теперь вы будете получать каждый день уведомления о погоде автоматически!');
                $ctx->enter('home');
            }
        });
        $scene->onHears('Нет',function (Context $ctx){
            if ($ctx->getText() === 'Нет'){
                $ctx->enter('findToSubscribe');
            }
        });
        $scene->onHears('Назад',function (Context $ctx){
            if ($ctx->getText() === 'Назад'){
                $ctx->enter('home');
            }
        });
        return  $scene;
    }

    public static function findToSubscribe(): Scene
    {
        $scene = new Scene('findToSubscribe');

        $scene->onEnter(function (Context $ctx) {
            $ctx->replyHTML('Попробуем найти ваш город. Напишите название или скиньте геолокацию',Menux::Get(7));
        });
        $scene->onMessage('location',function (Context $ctx){
            $location = $ctx->getMessage()->location();
            $wt = new Weather();
            $city = $wt->getGeoByCord($location->latitude(), $location->longitude())[0];
            $info = $wt->getCurrentByCity($city);
            try {
                $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
                $user->city = $info->name;
                $user->save();
                $ctx->replyHTML('Город установлен как ваш. Теперь вы будете получать каждый день уведомления о погоде автоматически!');
            }
            catch (\Exception $e){
                $ctx->replyHTML('Произошка ошибка. Попробуйте ещё раз.');
                info($e);
            }
        });

        $scene->onMessage('text', function (Context $ctx){
            $city = $ctx->getText();
            if($city === 'На главную'){
                $ctx->enter('home');
            }
            else{
                try {
                    $wt = new Weather();
                    $info = $wt->getCurrentByCity($city);

                    try {
                        $user = User::all()->firstOrFail('telegram_id','=',$ctx->getUserID());
                        $user->city = $info->name;
                        $user->save();
                        $ctx->replyHTML('Город установлен как ваш. Теперь вы будете получать каждый день уведомления о погоде автоматически!');
                    }
                    catch (\Exception $e){
                        $ctx->replyHTML('Произошка ошибка. Попробуйте ещё раз.');
                        info($e);
                    }

                } catch (WeatherException $e){

                    $ctx->replyHTML('Такого места не найдено, либо где-то ошибка в написании. '.Emoji::thinkingFace());
                }
            }




        });
        return $scene;
    }

    public static function getAllScenes(): array
    {
        return array(self::findToSubscribe(), self::subscribeScene(),self::getCityScene(), self::getHomeScene(),self::chooseVariantScene(),self::locationScene());
    }
}
