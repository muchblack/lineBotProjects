<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;
use LINE\Webhook\Model\JoinEvent;
use LINE\Webhook\Model\FollowEvent;

use App\Models\User;
use App\Traits\UserStatus;

use App\KeyWords\ProcessError;

class LineService
{
    use UserStatus;
    private MessagingApiApi $_bot;

    public function __construct()
    {
        $channelToken = config('line.channel_access_token');
        $config = new Configuration();
        $config->setAccessToken($channelToken);
        $this->_bot = new MessagingApiApi(new \GuzzleHttp\Client(), $config);
    }

    public function webhook($request)
    {
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        $parsedEvents = EventRequestParser::parseEventRequest($request->getContent(), config('line.channel_secret'), $signature);

        $commandList = [
            '新增庫存' => 'App\\KeyWords\\AddNewReserve',
            '刪除庫存' => 'App\\KeyWords\\RemoveReserve',
            '庫存金額修改' => 'App\\KeyWords\\ModifyReservePrice',
            '庫存數量增加' => 'App\\KeyWords\\ReserveIncrease',
            '庫存數量減少' => 'App\\KeyWords\\ReserveDecrease',
            '庫存確認' => 'App\\KeyWords\\CheckReserve',
            '庫存匯出' => 'App\\KeyWords\\ExportReserve',
            '中止' => 'App\\KeyWords\\StopAndCancel',
            'help' => 'App\\KeyWords\\HelperCommand',
        ];
        $statusList = [
            'newStatus' => 'App\\KeyWords\\AddNewReserve',
            'delStatus' => 'App\\KeyWords\\RemoveReserve',
            'priceStates' => 'App\\KeyWords\\ModifyReservePrice',
            'insStatus' => 'App\\KeyWords\\ReserveIncrease',
            'desStatus' => 'App\\KeyWords\\ReserveDecrease',
            'chkStatus' => 'App\\KeyWords\\CheckReserve',
        ];

        foreach($parsedEvents->getEvents() as $event)
        {

            if ($event instanceof JoinEvent || $event instanceof FollowEvent) {
                $userProfile = $this->handleUserJoin($event, $this->_bot);
                $command = new CommandService($event, $this->_bot, new WelCome($userProfile));
                $command->reply();
            }

            if($event instanceof MessageEvent)
            {
                $message = $event->getMessage();
                if(!($message instanceof TextMessageContent))
                {
                    continue;
                }

                //檢查是否在交互輸入中
                $checkCommand = $this->getUserStatus($event->getSource()->getUserId());
                Log::channel('lineCommandLog')->info('command => '. json_encode($checkCommand));
                Log::channel('lineCommandLog')->info('[LockStatus] => '. $checkCommand['statusLock']);

                $isCommand = false;
                $className = "App\\KeyWords\\CommandError";
                if(str_starts_with($message->getText(),'/')) {
                    $inputText = substr($message->getText(), 1);
                    $className = $commandList[$inputText] ?? "App\\KeyWords\\CommandError";
                    $isCommand = true;
                }

                //流程開始, 先檢查是否有被鎖上的流程
                if($checkCommand['statusLock'] === 'none')
                {
                    //初始狀態，沒有任何鎖
                    $command = new CommandService($event, $this->_bot, new $className());
                }
                else
                {
                    //在流程中但是需要中止&幫助
                    if ( ($message->getText() === '/中止') || ($message->getText() === '/help') ){
                        $command = new CommandService($event, $this->_bot, new $className());
                    }
                    else
                    {
                        if($isCommand) {
                            //流程中輸入出了中止&幫助的其他指令
                            $command = new CommandService($event, $this->_bot, new ProcessError());
                        }
                        else
                        {
                            //繼續流程
                            $className = $statusList[$checkCommand['statusLock']] ?? "App\\KeyWords\\CommandError";
                            $command = new CommandService($event, $this->_bot, new $className());
                        }
                    }
                }

                $command->reply();
            }
        }

        return response('ok');
    }

    private function handleUserJoin($event, $bot): array
    {
        $userId = $event->getSource()->getUserId();

        $userResponse = json_decode($bot->getProfile($userId), true);

        $objUser = new User();
        $user  = $objUser->where('lineUserID', $userId)->first();
        if(!$user)
        {
            $objUser->lineUserID = $userId;
            $objUser->nickname = $userResponse['displayName'];
            $objUser->save();
        }


        return [
            'nickName' => $userResponse['displayName'],
        ];
    }
}
