<?php

namespace app\ioactions;

class EchoIOAction implements IOActionInterface
{
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function __invoke()
    {
        echo $this->message;
        return 0;
    }
}
