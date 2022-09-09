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

header('Content-type: text/html; charset=utf-8');
require_once __DIR__.'/libs/phpQuery-0.9.5.386-onefile/phpQuery-onefile.php';



/* description => parsing page
   return      => phpQuery document "$doc" */
function parse_order($url){
    $file = file_get_contents($url);
    return phpQuery::newDocument($file);
}






//$telegram->sendMessage(['chat_id' => '-718032249', 'text' => $reply]);
