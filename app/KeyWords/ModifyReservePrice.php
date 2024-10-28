<?php

namespace App\KeyWords;

use App\Models\StoreItem;
use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

class ModifyReservePrice implements Command
{
    use UserStatus;
    private string $method = "priceStates";
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        $userStatus = $this->getUserStatus($userId);
        $this->setUserStatus($userId, 'statusLock', 'priceStates');
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要修改金額的庫存物品名稱：';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME'); //更改狀態
                break;
            case "WAIT:NAME":
                $Items =$objStoreItem->getStoreItemLikeName($userId, $input);
                if(!$Items->isEmpty())
                {
                    $text = "已爲你查詢到下列庫存物品：\n";
                    foreach($Items as $Item) {
                        $text .= "庫存材料ID : ".$Item->id." , ".$Item->item_name." ".$Item->item_quantity."個\n";
                    }
                    $text .= "請輸入要修改金額的庫存物品ID,僅限數字";
                    $this->setUserStatus($userId, $this->method, 'WAIT:PRICE');
                }
                else
                {
                    $text = "沒有類似名稱的庫存物品。";
                }
                break;
            case "WAIT:PRICE":
                if( (!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) || $input < 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    $text = "好的，希望金額修改爲？（單位爲台幣）";
                    $this->setUserInput($userId, 'id', $input);
                    $this->setUserStatus($userId, $this->method, 'FINISH');
                }
                break;
            case "FINISH":
                $inputData = $this->getUserInput($userId);
                if( (!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) || $input < 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    if($objStoreItem->getStoreItemById($inputData['id']))
                    {
                        $objStoreItem->updateStoreItem($inputData['id'], ['dollarPerSet' => $input]);
                        $text = "金額修改完成";
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                        $this->clearUserInput($userId); //清理輸入內容
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
