<?php

namespace app\ioactions;

use yii\db\Connection;

class QueryIOAction implements IOActionInterface
{
    private $query;
    private $connection;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function __invoke()
    {
        $result = $this->connection->createCommand($this->query)->execute();
        return $result;
    }

    public function set(Connection $connection)
    {
        $this->connection = $connection;
    }
}
