<?php

namespace App\KeyWords;

use LINE\Clients\MessagingApi\Model\TextMessage;

class CommandError implements Command
{
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        return [(new TextMessage(['text'=>'我不清楚你的問題，可使用/help確認可執行的動作。']))->setType('text')];
    }
}
