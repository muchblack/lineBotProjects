<?php

namespace App\KeyWords;

use App\Traits\UserStatus;
use LINE\Clients\MessagingApi\Model\TextMessage;

class StopAndCancel implements Command
{
    use UserStatus;
    public function replyCommand($event)
    {
        // TODO: Implement replyCommand() method.
        $this->cleanUserALL($event->getSource()->getUserId());

        return [(new TextMessage(['text'=>'已中止輸入流程，並清除狀態和輸入內容']))->setType('text')];
    }
}
