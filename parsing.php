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





/* description => start cycles parsing */
function start_cycles_parsing(){
    global $dbase, $tgBot;
    $watch_groups = $dbase->get_all("SELECT * FROM `categories_watch`");
    [,$dropped_errors] = $dbase->get_dropped_errors()[0];
    $hour_now = intval(date('H'));
    $iteration_count = 0;
    if($hour_now < 6){
        $delay = 30;
    }else{$delay = 9;}

    $tgBot->sendMessage('-718032249', " - - - - - - - - - - - - - - - - - - - - - - - - - - - - start");

    $while = 0;
    while (total_sec_in_each_five_min() < (295 - $delay)/*$while < 1*/){
        $while+=1;
        $iteration_count+=1;
        [,$last_order] = $dbase->get_last_order()[0];
        [,$errors_count] = $dbase->get_errors_count()[0];
        [,$backup_order] = $dbase->get_backup_order()[0];
        [,$deprecated] = $dbase->get_deprecated_order()[0];
        $current_order_was_create = '';
        if(check_total_dropped_errors($dropped_errors)){break;} // check if total dropped errors successively > 500

        unset($parse);
        $url = new_url($last_order);
        $doc = parse_order($url);
        $parse = fetch_order($doc);

//        echo '<pre>';
//        echo var_dump($parse);
//        echo '</pre>';

        if($parse){
            $objTgMessage = create_message_and_button($parse, $url);
            $current_order_was_create = $objTgMessage[2];
        }

        // send report to php console.log()
        send_report_message($iteration_count, $last_order, $backup_order, $errors_count, $current_order_was_create);

        if($parse && $errors_count < 1){
//            $tgBot->sendMessage('-718032249', "test stage parsed");
            $errors_count = 0;
            $dbase->set_errors_count($errors_count);
            $dbase->set_dropped_errors(0);
            $deprecated = false;
            $dbase->set_deprecated_order($deprecated);
            $id = $last_order;
            compare_groups_data_and_send_message($watch_groups, $parse, $objTgMessage, $id);
            $last_order+=1;
            $dbase->set_last_order($last_order);

            $backup_order = $last_order;
            $dbase->set_backup_order($backup_order);
        }


        else{
//            If Not Deprecated
            if($deprecated == false){
//                $tgBot->sendMessage('-718032249', '$deprecated == false');
                if(check_order_page($doc)){
                    $errors_count = 0;
                    $dbase->set_errors_count($errors_count);
                    $deprecated = true;
                    $dbase->set_deprecated_order($deprecated);
                    $last_order = $backup_order;
                    $dbase->set_last_order($last_order);
                }else{
                    $errors_count+=1;
                    $dbase->set_errors_count($errors_count);
                    $last_order+=1;
                    $dbase->set_last_order($last_order);
                }
                if($errors_count > 5){
                    $errors_count = 1;
                    $dbase->set_errors_count($errors_count);
                    $dropped_errors+=1;
                    $dbase->set_dropped_errors($dropped_errors);
                    $last_order = $backup_order;
                    $dbase->set_last_order($last_order);
                }
            }else if($deprecated == true){ //            If Deprecated
//                $tgBot->sendMessage('-718032249', '$deprecated == true');
                $errors_count = 1;
                $dbase->set_errors_count($errors_count);
                $last_order = $backup_order + 1;
                $dbase->set_last_order($last_order);
                $dbase->set_backup_order($last_order);
                $deprecated = false;
                $dbase->set_deprecated_order($deprecated);
                $last_order+=1;
                $dbase->set_last_order($last_order);
            }
            }
        $delay2 = $delay + rand(1, 4);


        $dbase->set_last_iteration_timestamp(date('d.m.y - H:i'));
        sleep($delay2);      // delay in seconds
    }
}

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


/* description => check is order page has correct data
   return      => bool */
function check_order_page($doc){
    global $tgBot;
    $chatId='-718032249';
    unset($array);
    $exist = false;
    try{
        $title = trim(explode('№', $doc->find('h1.kb-task-details__title')->text())[0]);
        if(isset($title) && strlen($title > 0)){$exist = true;}
    }catch (Exception $e) {$tgBot->sendMessage($chatId, 'Title исключение: '.$e->getMessage()."\n");}
    return $exist;
}


/* description => get new url use current order from db
   return      => new url */
function new_url($last_order){
    return 'https://kabanchik.ua/task/'.$last_order;
}


/* description => create message for group sending
   return      => array(message string, button object) */
function create_message_and_button($parse, $url){
    // variable $tasks
        $tasks = "Деталі: \n";
        if(strlen(trim(implode($parse['tasks']))) >= 15){
            foreach($parse['tasks'] as $task){
                if(strlen(trim($task)) > 1){
                    $tasks =  $tasks."  - ".$task."\n";
                }
            }
        }else if(strlen(trim(implode($parse['tasks'])) <= 15)){$tasks = "Без деталей\n";}
    // variable $positive
        $positive = '';
        if(intval(explode( ' ',$parse['review'])[1]) > 0){$positive = ', '.strtolower($parse['positive']);}
    // variable $price
        if(strlen($parse['price']) <= 0){$price = 'Без ціни';}else{$price = $parse['price'];}
    // variable $current_order_was_create
        if(isset($parse['was_created']) && strlen($parse['was_created']) > 2){$current_order_was_create = "\nprev.ord.time: "
            .explode(' ', $parse['was_created'])[1];}
        else{$current_order_was_create = '';}
    // variable $message
        $message = $parse['title']."\n".$price."\n"."Було створено: ".$parse['was_created']."\n".
            "Закінчити до: ".$parse['deadline']."\n\nКоментар: ".$parse['comment']."\n".$tasks.
            "\nМісто: ".$parse['city']."\nКлієнт: ".$parse['client']."\n".$parse['review'].$positive;
        $inline[] = ['text'=>'Відкрити у браузері', 'url'=>$url];
        $inline = array_chunk($inline, 2);
        $reply_markup = ['inline_keyboard'=>$inline];
        $inline_keyboard = json_encode($reply_markup);
        unset($inline);
        return [$message, $inline_keyboard, $current_order_was_create];
}


/* description => send report about each iteration to php console.log() */
function send_report_message($iteration_count, $last_order, $backup_order, $errors_count, $current_order_was_create){
    global $tgBot;
    $tgBot->sendMessage('-718032249', "iteration count: ".$iteration_count.
        "\ncur order:               ".$last_order."\nbackup order:       ".$backup_order."\nerrors count: "
        .$errors_count."\ntotal sec: ".total_sec_in_each_five_min().$current_order_was_create);
}

/* description => send message to Telegram group "php console.log()" about guest if it not crud of hosting*/
function send_php_console_log_about_guest(){
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


/* description => compares the data of groups and the current order. send messages to groups where it match */
function compare_groups_data_and_send_message($watch_groups, $parse, $objTgMessage, $id){
    global $tgBot;
//    $tgBot->sendMessage('-718032249', "compare. id now: ".$id);
    $php_console_log = '-718032249';
    $categories = $parse['categories'];
    $city       = $parse['city'];
    $message    = $objTgMessage[0];
    $button     = $objTgMessage[1];
    foreach ($watch_groups as $group){
        $cat1 = $categories[2];
        $cat2 = $categories[3];
        if(strlen($cat2) >= 5){$match = $cat2;}else{$match = $cat1;}
        if(strripos($group[3], trim($match))){ // check compare
            if($group[4] == 'all'){ // if use all we don't sort, send message right away
                $tgBot->sendMessage_mark($group[2], $message, $button);
                $tgBot->sendMessage_mark($php_console_log, $message, $button);
            }else if(isset($city) && strlen($city) > 1 && strripos($group[4], $city)){   // sort by cities
                $tgBot->sendMessage_mark($group[2], $message, $button);
                $tgBot->sendMessage_mark($php_console_log, $message, $button);
            }
        }
    }
}


/* description => check total mount seconds of each 5 minutes
   return      => total seconds */
function total_sec_in_each_five_min(){
    $min = intval(mb_substr(date('i'), 1));
    if($min >= 5){$min-=5;}
    $sec = intval(date('s'));
    return $min * 60 + $sec;
}


/* description => check if total dropped errors successively > 500 => break */
function check_total_dropped_errors($dropped_errors){
    global $tgBot;
    if($dropped_errors > 100){
        $tgBot->sendMessage('-718032249', 'Errors successively > 500. Program was break!');
        return true;}
}



start_cycles_parsing();
send_php_console_log_about_guest();

//https://kabanchik-bot.evilcode.space/parsing.php