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


/* description => fetch parsed document to array of strings
   return      => array of strings (order) || false */
function fetch_order($doc){
    global $tgBot;
    $chatId='-718032249';
    unset($array);
    $array = [];
    try{
        $title = trim(explode('№', $doc->find('h1.kb-task-details__title')->text())[0]);
        $title = trim($title);
        $array['title'] = $title;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Title исключение: '.$e->getMessage()."\n");}
    try{
        $price = trim($doc->find('span.js-task-cost')->text());
        $price = trim($price);
        $array['price'] = $price;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Price исключение: '.$e->getMessage()."\n");}
    try{
        $was_created = trim(explode('о:', $doc->find('div.kb-sidebar-grid__content:eq(1)')->text())[1]);
        $was_created = trim($was_created);
        $array['was_created'] = $was_created;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Was created исключение: '.$e->getMessage()."\n");}
    try{
        $deadline = trim($doc->find('span.js-datetime_due')->text());
        $deadline = trim($deadline);
        $array['deadline'] = $deadline;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Deadline исключение: '.$e->getMessage()."\n");}
    try{
        $tasks[0] = trim($doc->find('div.kb-task-details__non-numeric-attribute:eq(0)')->text());
        $tasks[1] = trim($doc->find('div.kb-task-details__non-numeric-attribute:eq(1)')->text());
        array_map(function ($val){return trim($val);}, $tasks);
        $array['tasks'] = $tasks;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Tasks исключение: '.$e->getMessage()."\n");}
    try{
        $comment = trim($doc->find('div.kb-task-details__content:eq(3)')->text());
        $comment = trim($comment);
        $array['comment'] = $comment;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Comment исключение: '.$e->getMessage()."\n");}
    try{
        $city = trim($doc->find('span.kb-execution-place__text')->text());
        $city = trim($city);
        $array['city'] = $city;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'City исключение: '.$e->getMessage()."\n");}
    try{
        $client = trim($doc->find('a.kb-sidebar-profile__name:eq(0)')->text());
        $client = trim($client);
        $array['client'] = $client;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Client исключение: '.$e->getMessage()."\n");}
    try{
        $review = trim($doc->find('span.kb-sidebar-profile__reviews-count:eq(0)')->text());
        $review = trim($review);
        $array['review'] = $review;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Review исключение: '.$e->getMessage()."\n");}
    try{
        $positive = trim($doc->find('div.kb-sidebar-profile__rating:eq(0)')->text());
        $positive = trim($positive);
        $array['positive'] = $positive;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Positive исключение: '.$e->getMessage()."\n");}
    try{
        $categories[1] = trim($doc->find('a.kb-breadcrumb__link:eq(1)')->text());
        $categories[2] = trim($doc->find('a.kb-breadcrumb__link:eq(2)')->text());
        $categories[3] = trim($doc->find('a.kb-breadcrumb__link:eq(3)')->text());
        array_map(function ($val){return trim($val);}, $categories);
        $array['categories'] = $categories;
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Categories исключение: '.$e->getMessage()."\n");}
    if(isset($title) && strlen($title > 0)){return $array;}
    else{return false;}
}


/* description => compares the data of groups and the current order. send messages to groups where it match */
function compare_groups_data_and_send_message($parse){
    $string = "emptyСтворення сайтівДоробка сайтуСтворення Landing pageВерстка сайтуСкрипти і ботиАдміністрування 
    серверівІнші послуги в сфері ITІнші роботи з розробки сайтівПарсинг";
    global $tgBot;
    $categories = $parse['categories'];
    $cat1 = $categories[2];
    $cat2 = $categories[3];
    if(strlen($cat2) >= 5){$match = $cat2;}else{$match = $cat1;}
}


$link1 = 'https://kabanchik.ua/ua/task/3117325-dopisati-funktsiiu-saiti';
$link2 = 'https://kabanchik.ua/ua/task/3117365-obnovit-sait';





//$telegram->sendMessage(['chat_id' => '-718032249', 'text' => $reply]);
