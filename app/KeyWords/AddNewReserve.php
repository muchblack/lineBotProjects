<?php

namespace App\KeyWords;

use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

/**
 * 新增庫存品項
 */
class AddNewReserve implements Command
{
    use UserStatus;
    private $method = "newStatus";

    public function replyCommand($event)
    {
        //先撈使用者目前狀態
        $userId = $event->getSource()->getUserId();
        $userStatus = $this->getUserStatus($userId);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入材料名稱';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME');
                break;
            case "WAIT:NAME":
                $text = '請輸入材料單位';
                $this->setUserStatus($userId, $this->method, 'WAIT:QUANTITY');
                break;
            case "WAIT:UNIT":
                $text = "請輸入材料數量,只能輸入數字";
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
