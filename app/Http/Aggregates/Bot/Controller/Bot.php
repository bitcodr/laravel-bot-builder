<?php   namespace App\Http\Aggregates\Bot\Controller;

use App\Http\Controllers\Controller;
use App\Http\Aggregates\User\Controller\UserController;
use App\Http\Aggregates\Bot\Contract\BotContract as Bot;
use App\Http\Aggregates\User\Contract\UserContract as User;
use Telegram\Bot\Api;

class BotController extends Controller
{

    private $user;
    private $bot;

    public function __construct(Bot $bot, User $user)
    {
        $this->bot = $bot;
        $this->user = $user;
    }

    public function newBot($telegram,$message)
    {
        $user = $this->user->get_user($message['chat']['id']);
        if($user !== null && $user->status == 'DEACTIVATE')
        {
            app(UserController::class)->repeatSms($telegram,$message);
            return true;
        }
        if($user !== null && $user->status == 'ACTIVATE')
        {
            $this->createBot($telegram,$message);
            return true;
        }
        $keyboard = [
            [[
                'text' => trans('start.ConfirmID'),
                'request_contact' => true
            ]]
        ];
        
        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);

        $html = "
        <i>مدیر ربات (شخص محترم شما) موظف به تایید هویت خود با استفاده از SMS قبل از ساخت ربات است.</i>
        <i>برای ادامه کار روی دکمه زیر کلیک کنید.</i>
        ";

        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
        
    }





    public function createBot($telegram,$message)
    {
        $user = $this->user->get_user($message['chat']['id']);

        $keyboard = [
            [trans('start.CreateBotVideo')]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        $html = "
        <i>توکن دریافتی ربات خود را از </i><a href='@BotFather'>@BotFather</a><i> ارسال کنید.</i>

        <i>اگر نمیدانید چگونه از بات فادر توکن بگیرید:</i>
        <i>1-</i><code> ربات @BotFather را استارت کنید.</code>
        <i>2-</i><code> دستور /newbot را به بات فادر ارسال کنید.</code>
        <i>3-</i><code> یک نام برای ربات خودتان به بات فادر ارسال کنید.</code>
        <i>4-</i><code>  یک یوزرنیم برای ربات خودتان به بات فادر ارسال کنید. توجه کنید که آخر یوزرنیم باید عبارت bot وجود داشته باشد و حتما از نوع لاتین/انگلیسی باشد.</code>
        <i>5-</i><code> اگر تمام مراحل را درست انجام داده باشید، بات فادر متن طولانی ای به عنوان توکن برای شما ارسال میکند.</code>
        <i>6-</i><code> آن متن طولانی که توکن نامیده میشود را به پی وی رسان (همین ربات) فروارد کنید تا ربات شما ساخته شود.</code>
        
        ";
        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);

    }



    public function checkAndCreateBot($botToken,$botInfo,$telegram,$message)
    {

        $user = $this->user->get_user($message['chat']['id']);
        if($user == null)
        {   
            app(UserController::class)->userNotFound($telegram,$message);
            config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
            return true;
        }

        $bot = $this->bot->getBot($botInfo->getId());
        if($bot !== null)
        {   
            $this->botExist($telegram,$message);
            config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
            return true;
        }

        $data = [
            'token' => $botToken,
            'bot_id' => $botInfo->getId(),
            'name' => $botInfo->getFirstName(),
            'username' => $botInfo->getUsername(),
            'user_id' => $user->id
        ];
        $this->bot->createBot($data);

        $keyboard = [
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        $html = "
        <i>ربات شما با موفقیت ایجاد شد</i>
        ";
        $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);

        $inline_keyboard = json_encode([
            'inline_keyboard'=>[
                [
                    ['text'=>$botInfo->getFirstName(), 'url'=>'https://t.me/'.$botInfo->getUsername()]
                ],
            ]
        ]);
 
        $html1 = "
        <i>برای ورود به ربات روی دکمه زیر کلیک کنید. 😃👇</i>
        ";

        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html1, 
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard
        ]);

    }






    public function botNotFound($telegram,$message)
    {
        $keyboard = [
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        $html = "
        <i>توکن بات ارسال شده اشتباه است یا در بات فادر ثبت نشده است</i>
        ";
        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }




    public function botExist($telegram,$message)
    {
        $keyboard = [
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        $html = "
        <i>ربات با توکن ارسالی قبلا ثبت شده است</i>
        ";
        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }



    public function validateBotWithToken($value,$telegram)
    {
        config(['telegram.bot_token' => $value['message']['text']]);
        $telegramBot = new Api(config('telegram.bot_token'));
        $createdBot = $telegramBot->getMe();
        if($createdBot->toArray() == [])
        {
            $this->botNotFound($telegram,$value['message']);
            config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
            return true;
        }
        $this->checkAndCreateBot($value['message']['text'],$createdBot,$telegram,$value['message']);
        config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
        return true;
    }


    public function validateBotWithTokenText($value,$telegram)
    {
        $text =  explode(' ',substr($value['message']['text'], strpos($value['message']['text'], 'API:')));
        $cleanText = str_replace(["API:","\n","For",'"'],"",$text[0]);
        config(['telegram.bot_token' => $cleanText]);
        $telegramBot = new Api(config('telegram.bot_token'));
        $createdBot = $telegramBot->getMe();
        if($createdBot->toArray() == [])
        {
            $this->botNotFound($telegram,$value['message']);
            config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
            return true;
        }
        $this->checkAndCreateBot($cleanText,$createdBot,$telegram,$value['message']);
        config(['telegram.bot_token' => env('TELEGRAM_BOT_TOKEN')]);
        return true;
    }



  
    public function myBots($telegram,$message)
    {
        $bots = $this->bot->
        $keyboard = [
            [trans('start.PreviusBtn')]
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        $html = "
        <i>برای مشاهده تنظیمات بیشتر </i>
        <i>ربات خود را از طریق دکمه های زیر انتخاب کنید</i>
        ";
        return $telegram->sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }


}