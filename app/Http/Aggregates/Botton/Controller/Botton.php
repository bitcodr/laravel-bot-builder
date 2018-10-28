<?php   namespace App\Http\Aggregates\Botton\Controller;

use Telegram;
use Telegram\Bot\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Aggregates\User\Controller\UserController;
use App\Http\Aggregates\User\Contract\UserContract as User;
use App\Http\Aggregates\AdminBot\Controller\AdminBotController;
use App\Http\Aggregates\Botton\Contract\BottonContract as Botton;

class BottonController extends Controller
{

    private $user;
    private $botton;

    public function __construct(Botton $botton, User $user)
    {
        $this->botton = $botton;
        $this->user = $user;
    }


    

    public function buttons($bot,$message, $parent_id = null)
    {
        $cacheGet = (isset($parent_id) && !empty($parent_id)) ? json_decode($parent_id) : null;
        $parentId = (isset($cacheGet) && !empty($cacheGet)) ? $cacheGet[0] : null;
        $bottons = $this->botton->bottonList($bot,$parentId);
        $groupBottons = $bottons->groupBy('position');

        $encodeBtn = json_encode($groupBottons);
        $decodeBtn = json_decode($encodeBtn,true);
        $keyboards = [];
        foreach($decodeBtn as $key => $gb)
        {
            $btn = array_column($gb,'name');
            array_push($btn,trans('start.newBouttonKey')." ".$key);
            $keyboards[] = $btn;
        }
        $countOfBottons = count($groupBottons)+1;
        array_push($keyboards,[trans('start.newBouttonKey')." ".$countOfBottons]);
        array_push($keyboards,[trans('start.PreviusBtn')]);

        $keyboard = $keyboards;

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
            <i>بخش مدیریت دکمه ها</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }





    public function newBottonName($bot,$message)
    {
        $cacheKey = $message['chat']['id'].'_bottonName';    
        if(Cache::has($cacheKey))
        {   
            Cache::forget($cacheKey);
        }
        Cache::put($cacheKey, $message['text'], 30);

        $reply_markup = Telegram::replyKeyboardMarkup([
            'remove_keyboard' => true, 
        ]);
        
        $html = "
            <i>نام دکمه مورد نظر را ارسال کنید</i>,
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }




    public function insertNewParrentbotton($bot,$message)
    {
        $cacheKey = $message['chat']['id'].'_bottonName';

        $btnActionCacheKey = $message['chat']['id'].'_bottonAction';    
        if(Cache::has($btnActionCacheKey))
        {   
            $cacheGet = Cache::get($btnActionCacheKey);
            $parent_id = json_decode($cacheGet);
        }
        $parentId = (isset($parent_id) && !empty($parent_id)) ? $parent_id[0] : null;

        $botton = $this->botton->existParentBtn($message['text'],$bot->id,$message['chat']['id'],$parentId);
        if(!is_null($botton))
        {
            if(Cache::has($cacheKey))
            {   
                Cache::forget($cacheKey);
            }
            if(Cache::has($btnActionCacheKey))
            {   
                Cache::forget($btnActionCacheKey);
            }
            $keyboard = [  
                [trans('start.PreviusBtn')]
            ];
    
            $reply_markup = Telegram::replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true, 
                'one_time_keyboard' => false
            ]);
            
            $html = "
                <b>خطا</b>
                <i>این دکمه تکراری است و دکمه ای با همین عنوان در منو وجود دارد</i>,
            ";
            
            return Telegram::sendMessage([
                'chat_id' => $message['chat']['id'],
                'reply_to_message_id' => $message['message_id'], 
                'text' => $html, 
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup
            ]);
        }

        if(Cache::has($cacheKey))
        {
            $value = Cache::get($cacheKey);
            $position = preg_replace("/[^0-9]/", '', $value);
            $data = [
                'parent_id' =>  $parentId,
                'bot_id' => $bot->id,
                'name' =>  $message['text'],
                'position' => $position
            ];
            $this->botton->createBotton($data);
            Cache::forget($cacheKey);
            if(Cache::has($btnActionCacheKey))
            {   
                Cache::forget($btnActionCacheKey);
            }
            $keyboard = [  
                [trans('start.buttons')]
            ];
    
            $reply_markup = Telegram::replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true, 
                'one_time_keyboard' => false
            ]);
            
            $html = "
                <i>با موفقیت اضافه شد</i>,
            ";
            
            return Telegram::sendMessage([
                'chat_id' => $message['chat']['id'],
                'reply_to_message_id' => $message['message_id'], 
                'text' => $html, 
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup
            ]);
        }

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
            <i>اشکالی پیش آمده مجددا تلاش کنید برگشت را بزنید</i>,
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }
   





    public function bottonActions($bot,$message,$botton)
    {
        if(!is_null($botton))
        {
            $cacheKey = $message['chat']['id'].'_bottonAction';    
            if(Cache::has($cacheKey))
            {   
                Cache::forget($cacheKey);
            }
            Cache::put($cacheKey, json_encode([$botton->id,$botton->parent_id]), 30);
        }

        $keyboard = [  
            [trans('start.editBottonName'),trans('start.bottonAnswer')],
            [trans('start.bottonChangePosition'),trans('start.bottonLink'),trans('start.deleteBotton')],
            [trans('start.bottonSubMenu')],
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>بخش مدیریت دکمه '".$message['text']."'</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }



    public function getEditBotton($bot,$message,$botton)
    {
        $cacheKey = $message['chat']['id'].'_botAlert';    
        if(Cache::has($cacheKey))
        {   
            Cache::forget($cacheKey);
        }
        Cache::put($cacheKey, 'editted', 30);

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>نام جدید را وارد نمایید</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }


    public function editBotton($bot,$message,$botton)
    {
        $btnActionCacheKey = $message['chat']['id'].'_bottonAction';    
        if(Cache::has($btnActionCacheKey))
        {   
            $cacheGet = Cache::get($btnActionCacheKey);
            $parent_id = json_decode($cacheGet);
        }
        $bottonId = (isset($parent_id) && !empty($parent_id)) ? $parent_id[0] : null;

        $this->botton->updateBtn($bottonId,$message['text']);

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>نام دکمه با موفقیت تغییر کرد</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }




    public function deleteBotton($bot,$message,$botton)
    {
        $btnActionCacheKey = $message['chat']['id'].'_bottonAction';    
        if(Cache::has($btnActionCacheKey))
        {   
            $cacheGet = Cache::get($btnActionCacheKey);
            $parent_id = json_decode($cacheGet);
        }
        $bottonId = (isset($parent_id) && !empty($parent_id)) ? $parent_id[0] : null;

        $this->botton->deleteBtn($bottonId);

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>دکمه مورد نظر با موفقیت حذف شد</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }




    public function getChangePosition($bot,$message,$botton)
    {
        $cacheKey = $message['chat']['id'].'_botAlert';    
        if(Cache::has($cacheKey))
        {   
            Cache::forget($cacheKey);
        }
        Cache::put($cacheKey, 'poistionChanged', 30);

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>موقعیت جدید دکمه را به صورت عدد انگلیسی وارد نمایید</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }

    

    public function changePosition($bot,$message,$botton)
    {
        $btnActionCacheKey = $message['chat']['id'].'_bottonAction';    
        if(Cache::has($btnActionCacheKey))
        {   
            $cacheGet = Cache::get($btnActionCacheKey);
            $parent_id = json_decode($cacheGet);
        }
        $bottonId = (isset($parent_id) && !empty($parent_id)) ? $parent_id[0] : null;

        $this->botton->updatePosition($bottonId,$message['text']);

        $keyboard = [  
            [trans('start.PreviusBtn')]
        ];

        $reply_markup = Telegram::replyKeyboardMarkup([
            'keyboard' => $keyboard, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
        ]);
        
        $html = "
        <i>موقعیت دکمه آپدیت شد</i>
        ";
        
        return Telegram::sendMessage([
            'chat_id' => $message['chat']['id'],
            'reply_to_message_id' => $message['message_id'], 
            'text' => $html, 
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }


}