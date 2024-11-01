<?php

namespace App\Services;


use App\Models\StoreItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Webhook\Model\ImageMessageContent;

class CommandService
{
    private mixed $service;
    private mixed $bot;
    private mixed $event;
    private StoreItemService $storeItem;

    public function __construct($event, $bot, $service)
    {
        $this->service = $service;
        $this->bot = $bot;
        $this->event = $event;
        $this->storeItem = new StoreItemService(new StoreItem());
    }

    public function reply(): void
    {
        $userId = $this->event->getSource()->getUserId();
        if($this->event->getMessage() instanceof ImageMessageContent) {
            //傳入圖片設定
            $msgId = $this->event->getId();
            $response = $this->getImage($msgId);
            //存入資料夾
            $fileName = $this->event->getSource()->getUserId().'/'.uniqid().'.jpg';
            Storage::disk('itemImage')->put($fileName,$response);
            //取回url準備輸入
            $input = Storage::disk()->url($fileName);
            Log::channel('lineCommandLog')->info('[imgUrl] => '. $input);
        }
        else
        {
            $input = $this->event->getMessage()->getText();
        }

        $replyText = $this->service->replyCommand($this->event, $userId, $input, $this->storeItem);

        $this->bot->replyMessage( new ReplyMessageRequest([
            'replyToken' => $this->event->getReplyToken(),
            'messages' => $replyText
        ]));
    }

    private function getImage($msgId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-data.line.me/v2/bot/message/'.$msgId.'/content',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.env('LINE_BOT_CHANNEL_ACCESS_TOKEN')
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
