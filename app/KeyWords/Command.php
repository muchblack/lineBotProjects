<?php

namespace App\KeyWords;

interface Command
{
    public function replyCommand($event, $userId, $input, $objStoreItem);
}
