<?php

include_once('env.php');
include_once('db.php');
include_once('tg-bot.php');
include_once('vendor/autoload.php');
use Telegram\Bot\Api;
use mydb\myDB;
use env\Env;

date_default_timezone_set('Europe/Kiev');

header('Content-type: text/html; charset=utf-8');
require_once __DIR__.'/libs/phpQuery-0.9.5.386-onefile/phpQuery-onefile.php';


$dbase = new myDB(env::class);
$tgBot = new TGBot(env::class);






function parse_order($doc, $url){
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
    return $array;
}


function send_php_console_log(){
    global $tgBot;
    $chatId='-718032249';
    $ch1 = curl_init("http://ip-api.com/php/".$_SERVER['REMOTE_ADDR']); // IP API - https://ip-api.com/docs/api:serialized_php
    curl_setopt($ch1, CURLOPT_HEADER, false);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_POST, 1);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
    $ipapi = curl_exec($ch1);
    $ipapi = unserialize($ipapi);
    $params=[
        'chat_id'=>$chatId,
        'text'=>"ip: ".$_SERVER['REMOTE_ADDR']."\n".
                "user-agent: ".$_SERVER['HTTP_USER_AGENT'].
                "\ncountry: ".$ipapi['country']."\ncity: ".$ipapi['city']."\ninternet: ".$ipapi['isp'].' '.$ipapi['as'],
    ];
    if(strripos($ipapi['as'], "Hosting Ukraine LTD") == false){       // if not 'Hosting Ukraine LTD'
        $tgBot->sendMessage($chatId, $params['text']);      // Guest check
        exit('PHP Fatal error: Uncaught Error: Call to a member function get_dropped_errors();');
    }
    curl_close($ch1);
}


//https://kabanchik-bot.evilcode.space/parsing.php