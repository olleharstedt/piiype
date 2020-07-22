<?php

namespace app\ioactions;

class IOPayload
{
    private $payload;
    private $ioactions;

    public function __construct($payload = null, $ioactions = [])
    {
        $this->payload = $payload;
        $this->ioactions = $ioactions;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getIOActions()
    {
        return $this->ioactions;
    }
}
