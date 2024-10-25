<?php

namespace App\KeyWords;
use LINE\Clients\MessagingApi\Model\TextMessage;

class WelCome implements Command
{
    public function __construct($user)
    {
        $this->nickName = $user['nickName'];
    }
    public function replyCommand($event): array
    {
        // TODO: Implement replyCommand() method.
        $text = [
            'text' => $this->nickName."你好！歡迎使用材料庫存小幫手，馬上輸入/新增庫存，開始管理吧！\n如不清楚指令可以輸入/help"
        ];
        return [(new TextMessage($text))->setType('text')];
    }
}
