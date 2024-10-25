<?php

namespace App\KeyWords;

use LINE\Clients\MessagingApi\Model\TextMessage;

use App\Traits\UserStatus;

class ProcessError implements Command
{
    use UserStatus;
    public function replyCommand($event): array
    {
        $userStatus = $this->getUserStatus($event->getSource()->getUserId());
        // TODO: Implement replyCommand() method.
        $commandList = [
            'newStatus' => '新增庫存',
            'delStatus' => '刪除庫存',
            'insStatus' => '庫存數量增加',
            'desStatus' => '庫存數量減少',
            'chkStatus' => '庫存確認',
        ];
        $text = "目前還在處理 [".$commandList[$userStatus['statusLock']]."] 中，請全部輸入完成後再進行其他動作,或是輸入[中止]來結束流程。";

        return [(new TextMessage(['text'=>$text]))->setType('text')];
    }
}
