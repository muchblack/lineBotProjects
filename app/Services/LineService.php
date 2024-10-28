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

use App\KeyWords\ModifyReservePrice;
use App\KeyWords\HelperCommand;
use App\KeyWords\CommandError;
use App\KeyWords\WelCome;
use App\KeyWords\AddNewReserve;
use App\KeyWords\CheckReserve;
use App\KeyWords\ExportReserve;
use App\KeyWords\RemoveReserve;
use App\KeyWords\ReserveDecrease;
use App\KeyWords\ReserveIncrease;
use App\KeyWords\StopAndCancel;
use App\KeyWords\ProcessError;

use App\Traits\UserStatus;

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
            'newStatus' => '/新增庫存',
            'delStatus' => '/刪除庫存',
            'priceStates' => '/庫存金額修改',
            'insStatus' => '/庫存數量增加',
            'desStatus' => '/庫存數量減少',
            'chkStatus' => '/庫存確認',
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
                Log::info('command => '. json_encode($checkCommand));
                Log::info('[LockStatus] => '. $checkCommand['statusLock']);
                //流程開始
                if($checkCommand['statusLock'] === 'none')
                {
                    $command = match ($message->getText()) {
                        '/新增庫存' => new CommandService($event, $this->_bot, new AddNewReserve()),
                        '/刪除庫存' => new CommandService($event, $this->_bot, new RemoveReserve()),
                        '/庫存金額修改' => new CommandService($event, $this->_bot, new ModifyReservePrice()),
                        '/庫存數量增加' => new CommandService($event, $this->_bot, new ReserveIncrease()),
                        '/庫存數量減少' => new CommandService($event, $this->_bot, new ReserveDecrease()),
                        '/庫存確認' => new CommandService($event, $this->_bot, new CheckReserve()),
                        '/庫存匯出' => new CommandService($event, $this->_bot, new ExportReserve()),
                        '/中止' => new CommandService($event, $this->_bot, new StopAndCancel()),
                        '/help' => new CommandService($event, $this->_bot, new HelperCommand()),
                        default => new CommandService($event, $this->_bot, new CommandError()),
                    };

                }
                else
                {
                    if ($message->getText() === '/中止'){
                        $command = new CommandService($event, $this->_bot, new StopAndCancel());
                    }
                    elseif($message->getText() === '/help')
                    {
                        $command = new CommandService($event, $this->_bot, new HelperCommand());
                    }
                    else
                    {
                        if( (str_starts_with($message->getText(), '/')) && ($message->getText() !== $commandList[$checkCommand['statusLock']])) {
                            $command = new CommandService($event, $this->_bot, new ProcessError());
                        }
                        else
                        {
                            $command = match ($checkCommand['statusLock']) {
                                'newStatus' => new CommandService($event, $this->_bot, new AddNewReserve()),
                                'delStatus' => new CommandService($event, $this->_bot, new RemoveReserve()),
                                'priceStates' => new CommandService($event, $this->_bot, new ModifyReservePrice()),
                                'insStatus' => new CommandService($event, $this->_bot, new ReserveIncrease()),
                                'desStatus' => new CommandService($event, $this->_bot, new ReserveDecrease()),
                                'chkStatus' => new CommandService($event, $this->_bot, new CheckReserve()),
                                default => new CommandService($event, $this->_bot, new CommandError()),
                            };
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
