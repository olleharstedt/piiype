<?php

namespace app\ioactions;

class StdoutIOAction implements IOActionInterface
{
    public function __invoke(): IOPayload
    {
        return new IOPayload();
    }
}
