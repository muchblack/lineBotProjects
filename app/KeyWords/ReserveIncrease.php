<?php

namespace App\KeyWords;

use App\Models\StoreItem;
use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

/**
 * 庫存增加
 */
class ReserveIncrease implements Command
{
    use UserStatus;
    private string $method= "insStatus";
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        $userStatus = $this->getUserStatus($userId);
        $this->setUserStatus($userId, 'statusLock', $this->method);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要增加數量的材料名稱：';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME'); //更改狀態
                break;
            case "WAIT:NAME":
                $Items = $objStoreItem->getStoreItemLikeName($userId, $input);
                if(!$Items->isEmpty())
                {
                    $text = "已爲你查詢到下列材料：\n";
                    foreach($Items as $Item) {
                        $text .= "庫存材料ID : ".$Item->id." , ".$Item->item_name." ".$Item->item_quantity."個\n";
                    }
                    $text .= "請輸入要增加數量的材料ID,僅限數字";
                    $this->setUserStatus($userId, $this->method, 'WAIT:QUANTITY');
                }
                else
                {
                    $text = "沒有類似名稱的材料。";
                }
                break;
            case "WAIT:QUANTITY":
                if( (!preg_match('/^-?[1-9][0-9]*$|^0$/', $input)) || $input <= 0)
                {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    $Item = $objStoreItem->getStoreItemById($input);
                    if($Item)
                    {
                        $text = "好的，請輸入要增加的數量：";
                        $this->setUserInput($userId, 'id', $input);
                        $this->setUserStatus($userId, $this->method, 'FINISH');
                    }
                    else
                    {
                        $text = "沒有這個材料哦，請重新輸入。";
                    }
                }
                break;
            case "FINISH":
                if((!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input) || $input < 0 ))
                {
                    $text = "輸入錯誤，這不是數字或是輸入爲負數";
                }
                else
                {
                    $inputData = $this->getUserInput($userId);
                    $data = $objStoreItem->getStoreItemById($inputData['id']);
                    if($data)
                    {
                        $objStoreItem->updateStoreItem($inputData['id'], ['item_quantity' => ($data->item_quantity + $input) ]);
                        $text = "數量已經修改完成";
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                        $this->clearUserInput($userId); //清理輸入內容
                    }
                }
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
