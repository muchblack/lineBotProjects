<?php

namespace App\KeyWords;

use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

class ModifyReserve implements Command
{
    use UserStatus;
    private string $method = "modifyStates";
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        $userStatus = $this->getUserStatus($userId);
        $this->setUserStatus($userId, 'statusLock', $this->method);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要修改的庫存物品名稱,可輸入全部查詢：';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME'); //更改狀態
                break;
            case "WAIT:NAME":
                if($input === '全部')
                {
                    $Items = $objStoreItem->getStoreItems($userId);
                }
                else
                {
                    $Items = $objStoreItem->getStoreItemLikeName($userId, $input);
                }
                if(!$Items->isEmpty())
                {
                    $text = "已爲你查詢到下列庫存物品：\n";
                    foreach($Items as $Item) {
                        $text .= "庫存材料ID : ".$Item->id." , ".$Item->item_name." ".$Item->item_quantity."個\n";
                    }
                    $text .= "請輸入要修改的庫存物品ID,僅限數字";
                    $this->setUserStatus($userId, $this->method, 'WAIT:MTYPE');
                }
                else
                {
                    $text = "沒有類似名稱的庫存物品。";
                }
                break;
            case "WAIT:MTYPE":
                if( (!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) || $input < 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    if($objStoreItem->getStoreItemById($input))
                    {
                        $text = "好的，要修改哪個項目呢(1-5)？\n";
                        $text .= "1. 金額(僅限阿拉伯數字)\n";
                        $text .= "2. 名稱\n";
                        $text .= "3. 單位\n";
                        $text .= "4. 位置\n";
                        $text .= "5. 圖片\n";
                        $text .= "請輸入數字：";
                        $this->setUserInput($userId, 'id', $input);
                        $this->setUserStatus($userId, $this->method, 'WAIT:SELECT');
                    }
                    else
                    {
                        $text = "沒有你輸入Id的物品。";
                    }
                }
                break;
            case "WAIT:SELECT":
                if( (!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) || $input < 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    $text="請輸入要替換的內容，若是圖片請直接傳送圖片";
                    $this->setUserInput($userId, 'selected', $input);
                    $this->setUserStatus($userId, $this->method, 'FINISH');
                }
                break;
            case "FINISH":
                $inputData = $this->getUserInput($userId);
                $itemId = $inputData['id'];
                $modifyData = [];
                    switch($inputData['selected'])
                    {
                        case 1: //修改金額
                            if( (!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) || $input < 0) {
                                $text = "輸入錯誤，這不是數字";
                            }
                            else {
                                $modifyData = ['dollarPerSet' => $input];
                                $text = "金額修改完成";
                            }
                            break;
                        case 2: //修改名稱
                            $modifyData = ['item_name' => $input];
                            $text = "名稱修改完成";
                            break;
                        case 3: //修改單位
                            $modifyData = ['item_unit' => $input];
                            $text = "單位修改完成";
                            break;
                        case 4: //修改位置
                            $modifyData = ['item_place' => $input];
                            $text = "位置修改完成";
                            break;
                        case 5:
                            break;
                    }
                    if(!empty($modifyData)) {
                        $objStoreItem->updateStoreItem($itemId, $modifyData);
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                    }
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
