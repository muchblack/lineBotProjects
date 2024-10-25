<?php

namespace App\Traits;

use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait UserStatus
{
    public function getUserStatus($userID)
    {
            $userStatus = Redis::get($userID);
            Log::info('handUserStatus: ' . $userStatus);
            if(!$userStatus)
            {
                $userStatus = [
                    'statusLock' => 'none',
                    'newStatus' => 'WAIT:STANDBY',
                    'delStatus' => 'WAIT:STANDBY',
                    'insStatus' => 'WAIT:STANDBY',
                    'desStatus' => 'WAIT:STANDBY',
                    'chkStatus' => 'WAIT:STANDBY',
                ];

                Redis::set($userID, json_encode($userStatus, JSON_UNESCAPED_UNICODE));
            }
            else
            {
                $userStatus = json_decode($userStatus, true);
            }

            return $userStatus;
    }

    public function setUserStatus($userID, $method, $action)
    {
        $userStatus = $this->getUserStatus($userID);
        $userStatus[$method] = $action;

        Redis::set($userID, json_encode($userStatus, JSON_UNESCAPED_UNICODE));
    }

    public function getUserInput($userID)
    {
        return json_decode(Redis::get('input_'.$userID), true);
    }

    public function setUserInput($userId, $input_column, $input_value)
    {
        $userInput = json_decode(Redis::get('input_'.$userId), true) ?? [] ;
        Log::info('[setUserInput][inputColumn] '. $input_column);
        Log::info('[setUserInput][inputValue] '. $input_value);
        $userInput[$input_column] = $input_value;
        Redis::set('input_'.$userId, json_encode($userInput, JSON_UNESCAPED_UNICODE));
    }

    public function clearUserInput($userId)
    {
        Redis::del('input_'.$userId);
    }

    public function cleanUserALL($userID)
    {
        Redis::del($userID);
        Redis::del('input_'.$userID);
    }
}
