<?php
    date_default_timezone_set('Asia/Tashkent');
    
    define('API_KEY', "5324228645:AAFzRpM3xcYV5fpcY1hfBRuWknaQ5-FD15E");
    function bot($method, $datas=[]){
        $url = "https://api.telegram.org/bot".API_KEY."/".$method;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);

        $res = curl_exec($ch);

        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        }else{
            return json_decode($res);
        }
    }
    function html($tx){
        return str_replace(['<','>'],['&#60;','&#62;'],$tx);
    }
    include 'db.php';
    $update = json_decode(file_get_contents('php://input'));
    $message = $update->message;
    $chat_id = $message->chat->id;
    $type = $message->chat->type;
    $miid =$message->message_id;
    $name = $message->from->first_name;
    $lname = $message->from->last_name;
    $full_name = $name . " " . $lname;
    $full_name = rStr(html($full_name));
    $user = $message->from->username;
    $fromid = $message->from->id;
    $text = rStr(html($message->text));
    $title = $message->chat->title;
    $chatuser = $message->chat->username;
    $chatuser = $chatuser ? $chatuser : "Shaxsiy Guruh!";
    $caption = rStr($message->caption);
    $entities = $message->entities;
    $entities = $entities[0];
    $text_link = $entities->type;
    $left_chat_member = $message->left_chat_member;
    $new_chat_member = $message->new_chat_member;
    $photo = $message->photo;
    $video = $message->video;
    $audio = $message->audio;
    $reply = $message->reply_markup;
    $fchat_id = $message->forward_from_chat->id;
    $fid = $message->forward_from_message_id;
    //editmessage
    $callback = $update->callback_query;
    $qid = $callback->id;
    $mes = $callback->message;
    $mid = $mes->message_id;
    $cmtx = $mes->text;
    $cid = $callback->message->chat->id;
    $ctype = $callback->message->chat->type;
    $cbid = $callback->from->id;
    $cbuser = $callback->from->username;
    $data = $callback->data;
    if (!file_exists("step")) mkdir("step");
    $check = file_get_contents("step/check.txt");
    if ($message) {
        if ($text == "/start") {
            bot('sendMessage',[
                'chat_id'=>$fromid,
                'text'=>"Salom 👋".$full_name.",\nBotimizga hush kelibsiz, siz bu bot orqali bir necha kitoblarni osongina topishingiz mumkin va bu imkoniyat siz uchun mutlaqo bepul!😊"
            ]);
        }
        if ($fromid == $admin) {
            if ($text == "/on") {
                file_put_contents("step/check.txt", "on");
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>"Barcha kitob joylay oladi"
                ]);
            }
            if ($text == "/off") {
                file_put_contents("step/check.txt", "on");
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>"faqat admin kitob joylay oladi"
                ]);
            }
        }

        if ($message->document) {
            if ($check == "on" || $fromid == $admin) {
                $file_id = $message->document->file_id;
                $file_name = rStr($message->document->file_name);
                $file_size = $message->document->file_size;
                $query = mysqli_query($conn,"INSERT INTO book_usersCW (fromid,name,file_id,size,caption) VALUES ('{$fromid}', '{$file_name}','{$file_id}','{$file_size}','{$caption}')") or die(mysqli_error($conn));
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>"Kitob muoffaqiyatli joylandi!\n\n"
                ]);
            }
        }

        $commands = ['/start','help'];
        if (!in_array($text, $commands) && $text != $message->document) {
            $query = mysqli_query($conn,"SELECT * FROM book_usersCW WHERE name LIKE '%{$text}%'");
            if (mysqli_num_rows($query)>0) {
                $matn = "Natijalar:\n\n";
                $i = 0;
                foreach ($query as $key => $value) {
                    $i++;
                    $size = ($value["size"] / (1024 * 1024));
                    $matn .= $i . ".  " . $value["name"] . " " . $size . " MB\n";
                    $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                    if ($i == 10) {
                        break;
                    }
                }
                $keys = array_chunk($keyy, 3);
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>$matn,
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$keys
                    ]),
                ]);
            }
        }
        if ($text == "/top") {
            $query = mysqli_query($conn,"SELECT * FROM book_usersCW WHERE id > '0' ORDER BY down DESC LIMIT 10");
            if (mysqli_num_rows($query) > 0) {
                $matn = "Eng kop yuklab olingan kitoblar:\n\n";
                $i = 0;
                foreach ($query as $key => $value) {
                    $i++;
                    $size = ($value["size"] / (1024 * 1024));
                    $matn .= $i . ".  " . $value["name"] . "\n" . $size  . "MB Yuklab olngnalar soni: " . $value["down"] . " ta\n";
                    $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                    if ($i == 10) {
                        break;
                    }
                }
                $keys = array_chunk($keyy, 3);
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>$matn,
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$keys
                    ]),
                ]);
            }
        }
        if ($text == "/random") {
        	$query = mysqli_query($conn, "SELECT * FROM book_usersCW WHERE id ORDER BY RAND() LIMIT 10");
        	if (mysqli_num_rows($query) > 0) {
                $matn = "Tasodiyif yuborilgan kitoblar:\n\n";
                $i = 0;
                foreach ($query as $key => $value) {
                    $i++;
                    $size = ($value["size"] / (1024 * 1024));
                    $matn .= $i . ".  " . $value["name"] . "\n" . $size  . " MB Yuklab olngnalar soni: " . $value["down"] . " ta\n";
                    $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                    if ($i == 10) {
                        break;
                    }
                }
                $keys = array_chunk($keyy, 3);
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>$matn,
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$keys
                    ]),
                ]);
            }
        }
        if ($text == "/last") {
        	$query = mysqli_query($conn, "SELECT * FROM book_usersCW WHERE id > '0' ORDER BY id DESC LIMIT 10");
        	if (mysqli_num_rows($query) > 0) {
                $matn = "Oxirgi yuklangan 10ta kitob:\n\n";
                $i = 0;
                foreach ($query as $key => $value) {
                    $i++;
                    $size = ($value["size"] / (1024 * 1024));
                    $matn .= $i . ".  " . $value["name"] . "\n" . $size  . "MB Yuklab olnganlar soni: " . $value["down"] . " ta\n";
                    $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                    if ($i == 10) {
                        break;
                    }
                }
                $keys = array_chunk($keyy, 3);
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>$matn,
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$keys
                    ]),
                ]);
            }
        }
    }
    if ($callback) {
        if (mb_stripos($data, 'down_')!==false) {
            $exp = explode("down_", $data);
            $id = $exp[1];
            $query = mysqli_query($conn,"SELECT * FROM book_usersCW WHERE id = '{$id}'");
            if (mysqli_num_rows($query)>0) {
                $row = mysqli_fetch_assoc($query);
                bot('sendDocument',[
                    'chat_id'=>$cbid,
                    'document'=>$row["file_id"],
                    'caption'=>$row["caption"]
                ]);
                mysqli_query($conn,"UPDATE book_usersCW SET down = down + '1' WHERE id = '{$id}'");
            }else{
                bot('answerCallbackQuery',[
                    'callback_query_id'=>$qid,
                    'text'=>"Avval kitob qidiring!",
                    'show_alert'=>true
                ]);
            }
        }
    }
?>
