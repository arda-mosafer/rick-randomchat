<?php
require_once 'config/db.php';
require __DIR__ . '/vendor/autoload.php';
include 'Telegram.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$botToken = $_ENV['BOT_TOKEN'];

$telegram = new Telegram($botToken);

// result request body{}
$resultTelegram = $telegram->getData();

$chat_id = $telegram->ChatID();
$text    = $telegram->Text();

$myCommands = false;

if($text == "/start") {
    // true myCommands (bool)
    $myCommands = true;

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("بزن بریم😎", '', '/home')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "خب، تو وارد شدی. تبریک، یا شایدم تسلیت. این یه ربات چت تصادفیه. دکمه رو بزن، و صبر کن تا یه غریبه پیداش شه. هویتت محفوظ می‌مونه، البته اگه خودت لو ندی.🪐");
    $telegram->sendMessage($content);
}

if($text == "/home") {
    // true myCommands (bool)
    $myCommands = true;

    // send welcome mesasage& create keyboard
    $option = array(
        array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/random") {
    // Loading
    $content = array('chat_id' => $chat_id, 'text' => "چند لحظه منتظر بمونید ⌛");
    $telegram->editMessageText($content);
}