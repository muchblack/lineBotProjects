<?php

namespace App\KeyWords;

use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Illuminate\Support\Facades\Log;
use App\Models\StoreItem;

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
        Log::info('[UserId] => '. $userId);
        $userStatus = $this->getUserStatus($userId);
        Log::info('[addNew]=>'.json_encode($userStatus));
        $this->setUserStatus($userId, 'statusLock', 'newStatus');
        switch($userStatus[$this->method]) {
            case "WAIT:STANDBY":
                $text = '請輸入材料名稱：';
                $this->setUserStatus($userId, $this->method, 'WAIT:NAME'); //更改狀態
                break;
            case "WAIT:NAME":
                $text = '好的，接下來請輸入材料單位：';
                $this->setUserStatus($userId, $this->method, 'WAIT:UNIT');
                $this->setUserInput($userId, 'item_name', $event->getMessage()->getText());
                break;
            case "WAIT:UNIT":
                $text = "收到，最後請輸入材料數量,只能輸入數字：";
                $this->setUserStatus($userId, $this->method, 'FINISH');
                $this->setUserInput($userId, 'item_unit', $event->getMessage()->getText());
                break;
            case "FINISH":
                $input = $event->getMessage()->getText();
                Log::info('[gettype]=>'.gettype($input).", [Value]=>".$input.", [preg_match]=>".preg_match('/^-?[1-9][0-9]*$|^0$/', $input));
                if(!preg_match('/^-?[1-9][0-9]*$|^0$/', $input)) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    try {
                        $text = "已爲你儲存資料。";
                        $inputData = $this->getUserInput($userId);
                        $objItem = new StoreItem();
                        $objItem->user_id = $userId;
                        $objItem->item_name = $inputData['item_name'];
                        $objItem->item_unit = $inputData['item_unit'];
                        $objItem->item_quantity = $event->getMessage()->getText();

                        $objItem->save();
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                    }catch (\Exception $e){
                        Log::error($e->getMessage());
                    }
                }
                break;
        }

        $messages[] = (new TextMessage(['text' => $text]))->setType('text');
        return $messages;
    }
}
