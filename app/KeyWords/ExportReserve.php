<?php

namespace App\KeyWords;

use App\Exports\StoreItemsExport;
use Illuminate\Support\Facades\Storage;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Maatwebsite\Excel\Facades\Excel;
/**
 * 庫存品項匯出
 */
class ExportReserve implements Command
{
    public function replyCommand($event, $userId, $input, $objStoreItem): array
    {
        // TODO: Implement replyCommand() method.
        $fileName = $userId.'/export.xlsx';
        Excel::store(new StoreItemsExport($userId), $fileName, 'export');
        $filePath = Storage::disk('export')->url($fileName);

        return [(new TextMessage(['text'=>'匯出連結爲：'.$filePath]))->setType('text')];
    }
}
