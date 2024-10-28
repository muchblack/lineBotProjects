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
    private string $method = "newStatus";

    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        //先撈使用者目前狀態
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
                if($objStoreItem->getStoreItemByName($userId, $input))
                {
                    $text = "已有相同名稱的庫存。，請重新輸入";
                }
                else
                {
                    $text = '好的，接下來請輸入材料單位：';
                    $this->setUserStatus($userId, $this->method, 'WAIT:UNIT');
                    $this->setUserInput($userId, 'item_name', $input);
                }
                break;
            case "WAIT:UNIT":
                $text = "收到，請輸入材料數量,只能輸入數字：";
                $this->setUserStatus($userId, $this->method, 'WAIT:PRICE');
                $this->setUserInput($userId, 'item_unit', $input);
                break;
            case "WAIT:PRICE":
                if( (!preg_match('/^-?[1-9][0-9]*$|^0$/', $input)) || $input <= 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    $text = "收到，最後請輸入材料單價(單位：台幣),只能輸入數字：";
                    $this->setUserStatus($userId, $this->method, 'FINISH');
                    $this->setUserInput($userId, 'item_quantity', $input);
                }
                break;
            case "FINISH":
                Log::info('[gettype]=>'.gettype($input).", [Value]=>".$input.", [preg_match]=>".preg_match('/^-?[1-9][0-9]*$|^0$/', $input));
                if( (!preg_match('/^-?[1-9][0-9]*$|^0$/', $input)) || $input <= 0) {
                    $text = "輸入錯誤，這不是數字";
                }
                else
                {
                    try {
                        $inputData = $this->getUserInput($userId);
                        $arrStoreItemData = [
                            'user_id' => $userId,
                            'item_name' => $inputData['item_name'],
                            'item_unit' => $inputData['item_unit'],
                            'item_quantity' => $inputData['item_quantity'],
                            'dollarPerSet' => $input,
                        ] ;

                        $objStoreItem->setStoreItem($userId, $arrStoreItemData);

                        $text = "已爲你儲存資料。";
                        $this->setUserStatus($userId, $this->method, 'WAIT:STANDBY');
                        $this->setUserStatus($userId, 'statusLock', 'none');
                        $this->clearUserInput($userId); //清理輸入內容
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
