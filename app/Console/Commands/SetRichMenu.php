<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\ApiException;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

class SetRichMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setRichMenu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws ApiException
     */
    public function handle()
    {
        $channelToken = config('line.channel_access_token');
        $config = new Configuration();
        $config->setAccessToken($channelToken);
        $bot = new MessagingApiApi(new \GuzzleHttp\Client(), $config);
        $richMenuSetting = [
            "size" => [
                'width' => 2500,
                'height' => 1686,
            ],
            "selected" => false,
            "name" => "小幫手指令頁面",
            "chatBarText" => "點擊開啓",
            "areas" => [
                [
                    "bounds" => [
                        "x" => 0,
                        "y" => 0,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/新增庫存"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 500,
                        "y" => 0,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/刪除庫存"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 1000,
                        "y" => 0,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/庫存數量增加"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 1500,
                        "y" => 0,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/庫存數量減少"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 2000,
                        "y" => 0,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/庫存金額修改"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 0,
                        "y" => 843,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/庫存確認"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 500,
                        "y" => 843,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/庫存匯出"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 1000,
                        "y" => 843,
                        "width" => 500,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/中止"
                    ]
                ],
                [
                    "bounds" => [
                        "x" => 1500,
                        "y" => 843,
                        "width" => 1000,
                        "height" => 843,
                    ],
                    "action"=>[
                        'type' => "message",
                        'text' => "/help"
                    ]
                ]
            ]

        ];

        $richMenuResponse = $bot->createRichMenu(new RichMenuRequest($richMenuSetting));
        $richMenuId = $richMenuResponse->getRichMenuId();
        dump($richMenuId);

        $curl = curl_init();
        curl_setopt_array($curl,
            [
            CURLOPT_URL => "https://api-data.line.me/v2/bot/richmenu/{$richMenuId}/content",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => file_get_contents(app_path('richMenu').'/richMenu.png'),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $channelToken,
                "Content-Type: image/jpeg"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        dump($response);
        dump($err);
        $bot->setDefaultRichMenu($richMenuId);

    }
}
