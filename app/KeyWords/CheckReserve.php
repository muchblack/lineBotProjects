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
    private $method = "chkStatus";

    public function replyCommand($event)
    {
        // TODO: Implement replyCommand() method.
        $userId = $event->getSource()->getUserId();
        Log::info('[CHK][UserId] => '. $userId);
        $userStatus = $this->getUserStatus($userId);
        Log::info('[CHK]=>'.json_encode($userStatus));
        $this->setUserStatus($userId, 'statusLock', $this->method);
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入要查詢的材料名稱：';
                $this->setUserStatus($userId, $this->method, 'FINISH'); //更改狀態
                break;
            case "FINISH":
                $input = $event->getMessage()->getText();
                $Items = StoreItem::where('user_id', $userId)->where('item_name', 'like', "%$input%")->get();
                $text = "已爲你查詢到下列材料：\n";
                foreach($Items as $Item) {
                    $text .="有 ".$Item->item_name." ".$Item->item_quantity."個\n";
                }
                $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                $this->setUserStatus($userId, 'statusLock', 'none');
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
