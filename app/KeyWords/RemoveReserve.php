<?php

namespace App\KeyWords;

use App\Models\StoreItem;
use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

/**
 * 移除庫存品項
 */
class RemoveReserve implements Command
{
    use UserStatus;

    private $method = "delStatus";

    public function replyCommand($event, $userId, $input, $objStoreItem)
    {
        // TODO: Implement replyCommand() method.
        $userStatus = $this->getUserStatus($userId);
        $this->setUserStatus($userId, 'statusLock', $this->method);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要刪除的材料名稱：';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME'); //更改狀態
                break;
            case "WAIT:NAME":
                $Items =$objStoreItem->getStoreItemLikeName($userId, $input);
                if(!$Items->isEmpty())
                {
                    $text = "已爲你查詢到下列材料：\n";
                    foreach($Items as $Item) {
                        $text .= "庫存材料ID : ".$Item->id." , ".$Item->item_name." ".$Item->item_quantity."個\n";
                    }
                    $text .= "請輸入要刪除的材料ID,僅限數字";
                    $this->setUserStatus($userId, $this->method, 'FINISH');
                }
                else
                {
                    $text = "沒有類似名稱的材料。";
                }
                break;
            case "FINISH":
                if( (!preg_match('/^-?[1-9][0-9]*$|^0$/', $input)) || $input <= 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else{
                    if($objStoreItem->delStoreItem($input))
                    {
                        $text = "該材料已被刪除";
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                        $this->clearUserInput($userId);
                    }
                    else
                    {
                        $text = "沒有這個ID哦，請重新輸入。";
                    }
                }
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
