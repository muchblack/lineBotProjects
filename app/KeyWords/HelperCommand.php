<?php

namespace App\KeyWords;

use App\KeyWords\Command;
use LINE\Clients\MessagingApi\Model\TextMessage;

class HelperCommand implements Command
{
    /**
     * 'newStatus' => '/新增庫存',
     * 'delStatus' => '/刪除庫存',
     * 'insStatus' => '/庫存數量增加',
     * 'desStatus' => '/庫存數量減少',
     * 'chkStatus' => '/庫存確認',
     * @param $event
     * @param $userId
     * @param $input
     * @param $objStoreItem
     */
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        $text = "目前的指令有： \n";
        $text .= "/新增庫存 : 用來新增庫存物品項目 \n";
        $text .= "/刪除庫存 : 用來刪除指定的庫存物品項目 \n";
        $text .= "/庫存數量增加 : 用來增加指定的庫存物品項目 \n";
        $text .= "/庫存數量減少 : 用來減少指定的庫存物品項目 \n";
        $text .= "/庫存修改 : 用來修改指定的庫存物品 \n";
        $text .= "/庫存確認 : 確認庫存物品細節 \n";
        $text .= "/庫存匯出 : 將目前的庫存物品資料匯出成Excel \n";
        $text .= "/中止 : 中止目前正在進行的動作 \n";
        $text .= "/help : 顯示目前所有指令 \n";

        // TODO: Implement replyCommand() method.
        return [(new TextMessage(['text'=>$text]))->setType('text')];
    }
}
