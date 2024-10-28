<?php

namespace App\Services;


use App\Models\StoreItem;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

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
        $input = $this->event->getMessage()->getText();

        $replyText = $this->service->replyCommand($this->event, $userId, $input, $this->storeItem);

        $this->bot->replyMessage( new ReplyMessageRequest([
            'replyToken' => $this->event->getReplyToken(),
            'messages' => $replyText
        ]));
    }
}
