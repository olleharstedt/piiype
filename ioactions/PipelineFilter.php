<?php

namespace app\ioactions;

class PipelineFilter implements PipelineFilterInterface
{
    private $filter;
    private $error;
    public function __construct(callable $filter, IOActionInterface $error = null)
    {
        $this->filter = $filter;
        $this->error = $error;
    }

    public function __invoke($payload)
    {
        return (bool) ($this->filter)($payload);
    }

    public function getError()
    {
        return $this->error;
    }
}
