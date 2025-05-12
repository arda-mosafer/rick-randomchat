<?php
require_once 'config/db.php';
require __DIR__ . '/vendor/autoload.php';
include 'Telegram.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$botToken = $_ENV['BOT_TOKEN'];

$telegram = new Telegram($botToken);

// Result request body{}
$resultTelegram = $telegram->getData();

$chat_id = $telegram->ChatID();
$text    = $telegram->Text();

$myCommands = false;

if($text == "/start") {
    // True MyCommands (bool)
    $myCommands = true;

    $option = array(
        //First Row
        array($telegram->buildInlineKeyBoardButton("بزن بریم😎", '', '/home')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "خب، تو وارد شدی. تبریک، یا شایدم تسلیت. این یه ربات چت تصادفیه. دکمه رو بزن، و صبر کن تا یه غریبه پیداش شه. هویتت محفوظ می‌مونه، البته اگه خودت لو ندی.🪐");
    $telegram->sendMessage($content);
}

if($text == "/home") {
    // True MyCommands (bool)
    $myCommands = true;

    // Send Welcome mesasage & Create keyboard
    $option = array(
        array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/random") {
    // True MyCommands (bool)
    $myCommands = true;

    $query = "UPDATE requests SET status=? WHERE time<=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "failed");
    $stmt->bindValue(2, (time() - 300));
    $stmt->execute();

    // Loading
    $content = array('chat_id' => $chat_id, 'text' => "چند لحظه منتظر بمونید ⌛");
    $telegram->editMessageText($content);

    // Find the active request
    $query = "SELECT * FROM requests WHERE status=? AND time>=? AND chat_id!=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "pending");
    $stmt->bindValue(2, (time() - 300));
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    if(count($result) == 0){
        $query = "INSERT INTO requests SET chat_id=?, status=?, time=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $chat_id);
        $stmt->bindValue(2, "pending");
        $stmt->bindValue(3, time());
        $stmt->execute();
    } else {
        $target_request = $result[0];

        $query = "INSERT INTO chats SET user_1=?, user_2=?, status=?, time=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $chat_id);
        $stmt->bindValue(2, $target_request->chat_id);
        $stmt->bindValue(3, "doing");
        $stmt->bindValue(4, time());
        $stmt->execute();

        $query = "UPDATE requests SET status=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, "done");
        $stmt->bindValue(2, $target_request->id);
        $stmt->execute();

        $content = array('chat_id' => $chat_id, 'text' => "خب، یه کاربر تصادفی پیدا شد. راضی باش، چون احتمال وقوعش از برخورد دو سیاه‌چاله هم کمتر بود.");
        $telegram->sendMessage($content);

        $content = array('chat_id' => $target_request->chat_id, 'text' => "خب، یه کاربر تصادفی پیدا شد. راضی باش، چون احتمال وقوعش از برخورد دو سیاه‌چاله هم کمتر بود.");
        $telegram->sendMessage($content);
    }
}

if(!$myCommands) {
    // Find active chat
    $query = "SELECT * FROM chats WHERE status=? AND (user_1=? OR user_2=?)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "doing");
    $stmt->bindValue(2, $chat_id);
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    if(!$result) {
        // Not found commands
        $content = array('chat_id' => $chat_id, 'text' => "دستور وارد شده صحیح نیست.❌");
        $telegram->sendMessage($content);
    }
    else{
        $target_chatid;
        if($chat_id == $result->user_1) $target_chatid = $result->user_2;
        else if ($chat_id == $result->user_2) $target_chatid = $result->user_1;

        $content = array('chat_id' => $target_chatid, 'text' => $text);
        $telegram->sendMessage($content);
    }
}