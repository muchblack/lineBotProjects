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

            return json_decode($userStatus,true);
    }

    public function setUserStatus($userID, $method, $action)
    {
        $userStatus = $this->getUserStatus($userID);
        $userStatus[$method] = $action;

        Redis::set($userID, json_encode($userStatus, JSON_UNESCAPED_UNICODE));
    }
}
