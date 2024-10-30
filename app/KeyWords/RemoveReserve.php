<?php

namespace App\KeyWords;

use App\Models\StoreItem;
use App\Traits\UserStatus;
use Illuminate\Support\Facades\Log;
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
                $text = '請輸入要刪除的庫存物品名稱，或輸入全部查詢：';
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
                        $text .= "庫存庫存物品ID : ".$Item->id." , ".$Item->item_name." ".$Item->item_quantity."個\n";
                    }
                    $text .= "請輸入要刪除的庫存物品ID,若要刪除多筆記錄請用半形,隔開：";
                    $this->setUserStatus($userId, $this->method, 'FINISH');
                }
                else
                {
                    $text = "沒有類似名稱的庫存物品。";
                }
                break;
            case "FINISH":
                $arrDelId = [] ;
                if(strpos($input, ','))
                {
                    //多筆刪除
                    $arrInput = explode(',', $input);
                    $err = 0;
                    foreach( $arrInput as $item )
                    {
                        if(!preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $item))
                        {
                            $err++;
                        }
                        else
                        {
                            $arrDelId[] = $item;
                        }
                    }

                    if($err > 0 )
                    {
                        $text = '輸入內容有不是數字的ID';
                    }
                }
                else
                {
                    if( !preg_match('/^-?([1-9]\d*|0)(\.\d+)?$/', $input)) {
                        $text = "輸入錯誤，這不是數字";
                    }
                    else
                    {
                        $arrDelId[] = $input;
                    }
                }
                Log::channel('lineCommandLog')->info('[Remove][ItemID] => '. implode(',',$arrDelId).", [countDelId]=>". count($arrDelId));

                if( count($arrDelId) > 0 )
                {
                    $succDel = 0 ;
                    $returnID = [] ;
                    foreach($arrDelId as $delId)
                    {
                        if($objStoreItem->delStoreItem($delId))
                        {
                            $succDel++;
                        }
                        else
                        {
                            $returnID[] = $delId;
                        }
                    }

                    $text = "沒有要被刪除的資料";

                    if( $succDel > 0 )
                    {
                        $text = "已成功刪除指定ID的庫存。";
                    }
                    if(count($returnID))
                    {
                        $text .= "\n下列ID [".implode(",", $returnID)."]的物品之前已被刪除或輸入錯誤。";
                    }

                    $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                    $this->setUserStatus($userId, 'statusLock', 'none');
                    $this->clearUserInput($userId);
                }

                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
