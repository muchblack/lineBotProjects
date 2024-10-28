<?php

namespace App\KeyWords;

use App\Models\StoreItem;
use App\Traits\UserStatus;
use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\TextMessage;

/**
 * 確認庫存品項
 */
class CheckReserve implements Command
{
    use UserStatus;
    private string $method = "chkStatus";

    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        Log::info('[CHK][UserId] => '. $userId);
        $userStatus = $this->getUserStatus($userId);
        Log::info('[CHK]=>'.json_encode($userStatus));
        $this->setUserStatus($userId, 'statusLock', $this->method);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要查詢的庫存物品名稱，可輸入全部查看：';
                $this->setUserStatus($userId, $this->method, 'FINISH'); //更改狀態
                break;
            case "FINISH":
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
                        $text .= "庫存材料ID : ".$Item->id.", ".$Item->item_name." ".$Item->item_quantity."個, 單價：".$Item->dollarPerSet.", 總成本：".($Item->item_quantity * $Item->dollarPerSet)."元\n";
                    }
                    $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                    $this->setUserStatus($userId, 'statusLock', 'none');
                    $this->clearUserInput($userId); //清理輸入內容
                }
                else
                {
                    $text = "沒有類似名稱的庫存物品。";
                }
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
