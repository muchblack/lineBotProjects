<?php

namespace App\KeyWords;

use LINE\Clients\MessagingApi\Model\TextMessage;

class CommandError implements Command
{
    public function replyCommand($event): array
    {
        // TODO: Implement replyCommand() method.
        return [(new TextMessage(['text'=>'我不清楚你的問題，可用指令有： 起卦，抽神社籤和大樂透號碼。']))->setType('text')];
    }
}
