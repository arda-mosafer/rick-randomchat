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

    // Select User by chat_id
    $query = "SELECT * FROM users WHERE chat_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    if(!$result){
        $query = "INSERT INTO users SET chat_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $chat_id);
        $stmt->execute();
    }
}

if($text == "/home") {
    // True MyCommands (bool)
    $myCommands = true;

    // Send Welcome mesasage & Create keyboard
    $option = array(
        array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
        array($telegram->buildInlineKeyBoardButton("تکمیل پروفایل🕵️‍♂️", '', '/set_profile'), $telegram->buildInlineKeyBoardButton("نمایش پروفایل👁", '', '/show_profile')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/set_profile") {
    // True MyCommands (bool)
    $myCommands = true;

    // Create Commands History
    $query = "INSERT INTO commands_history SET chat_id=?, command=?, time=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, "set_profile");
    $stmt->bindValue(3, time());
    $stmt->execute();

    $query = "UPDATE users SET name=?, age=?, gender=? WHERE chat_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, null);
    $stmt->bindValue(2, null);
    $stmt->bindValue(3, null);
    $stmt->bindValue(4, $chat_id);
    $stmt->execute();

    // Send Edit Profile Message
    $content = array('chat_id' => $chat_id, 'text' => "اسمت چیه؟🤔");
    $telegram->sendMessage($content);
}

if($text == "/show_profile"){
    // True MyCommands (bool)
    $myCommands = true;

    // Find User Profile
    $query = "SELECT * FROM users WHERE chat_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_OBJ);
    $gender = "پسر";
    if($profile->gender == "women") $gender = "دختر";
    $content = array('chat_id' => $chat_id, 'text' => "اسم شما: " . $profile->name . "\n" . "جنسیت: " . $gender . "\n" . "سن شما: " . $profile->age);
    $telegram->sendMessage($content);

    // Send Menu
    $option = array(
        array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
        array($telegram->buildInlineKeyBoardButton("تکمیل پروفایل🕵️‍♂️", '', '/set_profile'), $telegram->buildInlineKeyBoardButton("نمایش پروفایل👁", '', '/show_profile')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->sendMessage($content);
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

        $option = array( 
            //First row
            array($telegram->buildKeyboardButton("پایان چت 🧨"), $telegram->buildKeyboardButton("نمایش پروفایل 👁")),
        );
        $keyb = $telegram->buildKeyBoard($option, $onetime=false);

        // Start Chat Message
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "خب، یه کاربر تصادفی پیدا شد. راضی باش، چون احتمال وقوعش از برخورد دو سیاه‌چاله هم کمتر بود.");
        $telegram->sendMessage($content);

        $content = array('chat_id' => $target_request->chat_id, 'reply_markup' => $keyb, 'text' => "خب، یه کاربر تصادفی پیدا شد. راضی باش، چون احتمال وقوعش از برخورد دو سیاه‌چاله هم کمتر بود.");
        $telegram->sendMessage($content);
    }
}

if($text == "نمایش پروفایل 👁"){
    // True MyCommands (bool)
    $myCommands = true;

    // Find active chat
    $query = "SELECT * FROM chats WHERE status=? AND (user_1=? OR user_2=?) ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "doing");
    $stmt->bindValue(2, $chat_id);
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    if($result){
        $target_chatid;
        if($result->user_1 == $chat_id) $target_chatid = $result->user_2;
        elseif($result->user_2 == $chat_id) $target_chatid = $result->user_1;

        // Find Profile
        $query = "SELECT * FROM users WHERE chat_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $target_chatid);
        $stmt->execute();
        $profile = $stmt->fetch(PDO::FETCH_OBJ);

        $gender = "پسر";
        if($profile->gender == "women") $gender = "دختر";
        $content = array('chat_id' => $chat_id, 'text' => "اسم کاربر مقابل: " . $profile->name . "\n" . "جنسیت: " . $gender . "\n" . "سن کاربر مقابل: " . $profile->age);
        $telegram->sendMessage($content);
    }
}

if($text == "پایان چت 🧨"){
    // True MyCommands (bool)
    $myCommands = true;

    // Find active chat
    $query = "SELECT * FROM chats WHERE status=? AND (user_1=? OR user_2=?) ORDER By id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "doing");
    $stmt->bindValue(2, $chat_id);
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    if(!$result) {
        // Not found commands
        $content = array('chat_id' => $chat_id, 'text' => "خطای غیرممکن❌");
        $telegram->sendMessage($content);
    }
    else{
        // Update Chat to Finished
        $query = "UPDATE chats SET status=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, "finished");
        $stmt->bindValue(2, $result->id);
        $stmt->execute();

        // Delete Keyboard
        $reply_markup = array(
            'remove_keyboard' => true
        );

        $reply_markup = json_encode($reply_markup);


        // End Chat Message
        $content = array('chat_id' => $result->user_1, 'reply_markup' => ($reply_markup), 'text' => "چت شما منفجر شد.💥 کاربر تصادفی با موفقیت نابود شد🩸");
        $telegram->sendMessage($content);

        $content = array('chat_id' => $result->user_2, 'reply_markup' => ($reply_markup), 'text' => "چت شما منفجر شد.💥 کاربر تصادفی با موفقیت نابود شد🩸");
        $telegram->sendMessage($content);

        // Send Welcome mesasage & Create keyboard
        $option = array(
            array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $result->user_1, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
        $telegram->sendMessage($content);

        // Send Welcome mesasage & Create keyboard
        $option = array(
            array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $result->user_2, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!",  'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
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

    // Find Before Commands
    $query = "SELECT * FROM commands_history WHERE chat_id=? AND time>=? ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, time()-120);
    $stmt->execute();
    $commands = $stmt->fetch(PDO::FETCH_OBJ);

    if($result) {
        $target_chatid;
        if($chat_id == $result->user_1) $target_chatid = $result->user_2;
        elseif ($chat_id == $result->user_2) $target_chatid = $result->user_1;

        $content = array('chat_id' => $target_chatid, 'text' => $text);
        $telegram->sendMessage($content);
    }
    elseif($commands){
        if($commands->command == "set_profile"){
            // Find Profile
            $query = "SELECT * FROM users WHERE chat_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $chat_id);
            $stmt->execute();
            $profile = $stmt->fetch(PDO::FETCH_OBJ);

            if(!$profile->name){
                // Update Name in Profile (users)
                $query = "UPDATE users SET name=? WHERE chat_id=?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $text);
                $stmt->bindValue(2, $chat_id);
                $stmt->execute();

                $content = array('chat_id' => $chat_id, 'text' => "سن خود را وارد کنید:");
                $telegram->sendMessage($content);
            }
            elseif(!$profile->age){
                // Update Name in Profile (users)
                $query = "UPDATE users SET age=? WHERE chat_id=?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $text);
                $stmt->bindValue(2, $chat_id);
                $stmt->execute();

                $option = array(
                    // First row
                    array($telegram->buildInlineKeyBoardButton("پسر 👦", '', 'men'), $telegram->buildInlineKeyBoardButton("دختر 👧", '', 'women')),
                );
                $keyb = $telegram->buildInlineKeyBoard($option);

                $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "جنسیت خود را انتخاب کنید:");
                $telegram->sendMessage($content);
            }
            elseif(!$profile->gender){
                // Update Name in Profile (users)
                $query = "UPDATE users SET gender=? WHERE chat_id=?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $text);
                $stmt->bindValue(2, $chat_id);
                $stmt->execute();
                
                $content = array('chat_id' => $chat_id, 'text' => "پروفایل شما با موفقیت آپدیت شد. ✅");
                $telegram->sendMessage($content);

                // Send Welcome mesasage & Create keyboard
                $option = array(
                    array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
                    array($telegram->buildInlineKeyBoardButton("تکمیل پروفایل🕵️‍♂️", '', '/set_profile'), $telegram->buildInlineKeyBoardButton("نمایش پروفایل👁", '', '/show_profile')),
                );
                $keyb = $telegram->buildInlineKeyBoard($option);
                $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "دکمه زیر رو بزن و یه نفرو تصادفی پیدا کن!");
                $telegram->sendMessage($content);
            }
        }
    }
    else{
        // Not found commands
        $content = array('chat_id' => $chat_id, 'text' => "دستور وارد شده صحیح نیست.❌");
        $telegram->sendMessage($content);
    }
}