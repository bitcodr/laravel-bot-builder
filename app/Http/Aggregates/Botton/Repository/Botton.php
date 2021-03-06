<?php namespace App\Http\Aggregate\Botton\Repository;

use App\Http\Aggregates\Botton\Model\{BotFAQ, BotUser, Botton, BotChannel, BottonData, FAQAnswer};
use App\Http\Aggregates\Botton\Contract\BottonContract;


class BottonRepository implements BottonContract
{


    public function createBottonData($data)
    {
        return BottonData::create($data);
    }

    public function updateBottonData($bot_id,$botton_id,$sort)
    {
        return BottonData::where('bot_id',$bot_id)->where('botton_id',$botton_id)->update(['sort'=>$sort]);
    }

    public function bottonData($bot_id,$botton_id)
    {
        $BottonData = BottonData::where('bot_id',$bot_id)->where('botton_id',$botton_id)->first();
        return BottonData::where('bot_id',$bot_id)->where('botton_id',$botton_id)->orderBy('created_at',$BottonData->sort)->get();
    }

    public function updateFileCaption($fileID,$text)
    {
        return BottonData::where("fileID",'=',$fileID)->update(["caption"=>$text]);
    }
  
    public function createBotton($data)
    {
        return Botton::create($data);
    }

    public function updateBottonType($bottonID,$type)
    {
        return Botton::where('id',$bottonID)->update(['type'=>$type]);
    }

    public function bottonList($bot,$parent_id)
    {
        if(is_null($parent_id))
        {
            return Botton::where('bot_id',$bot->id)->whereNull('parent_id')->with('bot')->get();
        }
        return Botton::where('bot_id',$bot->id)->where('parent_id',$parent_id)->with('bot')->get();
    }


    public function existParentBtn($text,$bot_id,$user_id,$parent_id=null)
    {
        if(is_null($parent_id))
        {
            return Botton::where('bot_id',$bot_id)->whereNull('parent_id')->where('name','=',$text)->with('bot')->first();
        }
        return Botton::where('bot_id',$bot_id)->where('parent_id',$parent_id)->where('name','=',$text)->with('bot')->first();
    }



    public function updateBtn($botton_id,$name)
    {
        return Botton::where('id',$botton_id)->update(['name'=>$name]);
    }


    public function deleteBtn($botton_id)
    {
        return Botton::where('id',$botton_id)->delete();
    }


    public function updatePosition($botton_id,$position)
    {
        return Botton::where('id',$botton_id)->update(['position'=>$position]);
    }


    public function createBotChannel($botId,$username)
    {
        return BotChannel::updateOrCreate(['bot_id'=> $botId],[
            'bot_id'=> $botId,
            'username' => $username
        ]);
    }

    public function getChannelBot($botID)
    {
        return BotChannel::where('bot_id',$botID)->first();
    }

    public function deleteChannelBot($botID)
    {
        return BotChannel::where('bot_id',$botID)->delete();
    }

    public  function updateBotChannelText($botId,$text)
    {
        return BotChannel::where('bot_id',$botId)->update(['message'=>$text]);
    }

    public function createBotUser($data)
    {
        return BotUser::create($data);
    }

    public function getBotUser($bot_id,$user_id)
    {
        return BotUser::where('bot_id',$bot_id)->where('telegram_user_id',$user_id)->first();
    }

    public function createFaq($data)
    {
        return BotFAQ::create($data);
    }

    public function updateQuestion($botID,$question,$type,$bottonID)
    {
        return BotFAQ::where('bot_id',$botID)->where('botton_id',$bottonID)->where('id',$question)->update(['answer_type'=>$type]);
    }

    public function updateQuestionName($botID,$text,$bottonID)
    {
        return BotFAQ::where('bot_id',$botID)->where('botton_id',$bottonID)->update(['name'=>$text]);
    }

    public function listOfFAQ($botID,$bottonID)
    {
        return BotFAQ::where('bot_id',$botID)->where('botton_id',$bottonID)->get();
    }

    public function deleteAllFAQ($botID,$bottonID)
    {
        return BotFAQ::where('bot_id',$botID)->where('botton_id',$bottonID)->forcedelete();
    }

    public function getQuestion($questionID)
    {
        return BotFAQ::where('id',$questionID)->first();
    }


    public function createAnswer($data)
    {
        FAQAnswer::create($data);
    }


    public function get_user($user_id)
    {
        return BotUser::where('telegram_user_id',$user_id)->first();
    }


    public function updateAnswerGroup($count, $user_id, $bottonId)
    {
        $answers = FAQAnswer::where('user_id',$user_id)->whereHas("faq",function($query) use($bottonId) {
            $query->where("botton_id",$bottonId);
        })->orderBy("created_at","desc")->take($count)->get();

        $max = FAQAnswer::where('user_id',$user_id)->whereHas("faq",function($query) use($bottonId) {
            $query->where("botton_id",$bottonId);
        })->max("group");

        if(!empty($answers))
        {
            $group = $max+1;
            foreach ($answers as $answer)
            {
                FAQAnswer::where("id",$answer["id"])->update(["group"=>$group]);
            }
        }
        return true;
    }

    public function userAnswerList($botID, $userId)
    {
        $group =  FAQAnswer::where("user_id",$userId)->whereHas("faq",function($query) use($botID) {
            $query->where("bot_id",$botID);
        })->max("group");

        return FAQAnswer::where("user_id",$userId)->where("group",$group)->whereHas("faq",function($query) use($botID) {
            $query->where("bot_id",$botID);
        })->with("faq")->get();
    }

}
