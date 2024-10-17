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

use App\KeyWords\Error;
use App\KeyWords\WelCome;

class LineService
{
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

                $command = match ($message->getText()) {
                    default => new CommandService($event, $this->_bot, new Error()),
                };

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
