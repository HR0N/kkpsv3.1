<?php
include('vendor/autoload.php');
use Telegram\Bot\Api;
include_once('env.php');
include_once('db.php');
include_once('tg-bot.php');
//include_once('parsing.php');  // must by disable cycle function before include
use env\Env as env;
use mydb\myDB;


//$dbase3 = new myDB(env::class);
//$tgBot3 = new TGBot(env::class);





$reply = 'test';
//$telegram->sendMessage(['chat_id' => '-718032249', 'text' => $reply]);
