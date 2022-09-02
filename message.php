<?php
include('vendor/autoload.php');
use Telegram\Bot\Api;
include_once('env.php');
include_once('db.php');
include_once('tg-bot.php');
use env\Env as env;
use mydb\myDB;


$dbase3 = new myDB(env::class);
$tgBot3 = new TGBot(env::class);

$watched_groups = $dbase3->get_all("SELECT * FROM `categories_watch`");

foreach ($watched_groups as $group){
    $tgBot3->sendMessage($group[2], "Здравствуйте!\nСегодня мне необходимо улучшить кое какие "
        ."механизмы бота. Может наблюдаться не корректная его работа в какие то моменты. Пропуск новых заказов, "
        ."дублированние. Надеюсь до завтра я все закончу. В крайнем случае сделаю за выходные. Извините.");
}




$reply = 'test';
//$telegram->sendMessage(['chat_id' => '-718032249', 'text' => $reply]);
