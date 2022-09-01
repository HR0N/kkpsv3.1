<?php

include('vendor/autoload.php');
use Telegram\Bot\Api;
include_once('env.php');
include_once('db.php');
use env\Env as env;
use mydb\myDB;


$iteration_count = 0;

$telegram = new Api(env::$TELEGRAM_BOT_TOKEN);
$tgDbase = new myDB(env::class);


class TGBot{
    public $telegram;
    public function __construct($env)
    {
        $this->telegram = new Api($env::$TELEGRAM_BOT_TOKEN);
    }
    function sendMessage($chat_id, $message){
        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML']);
    }
    function sendMessage_mark($chat_id, $message, $keyboard){
        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $message, 'reply_markup' => $keyboard,
            'parse_mode' => 'HTML']);
    }
}


//if($text == 'start'){
//    $reply = "Hello world!";
//    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply]);
//}

// composer require irazasyed/telegram-bot-sdk ^2.0
//$ composer require vlucas/phpdotenv
//https://api.telegram.org/bot5591524736:AAGXk3kxgnGrjpIeMvhMM_toBda5NQVTLnQ/setWebHook?url=
//https://kkpsv3.evilcode.space/tg-bot.php