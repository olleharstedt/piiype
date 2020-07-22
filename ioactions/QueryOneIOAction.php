<?php

namespace app\ioactions;

use yii\db\Connection;

class QueryOneIOAction implements IOActionInterface
{
    private $query;
    private $connection;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function __invoke()
    {
        $result = $this->connection->createCommand($this->query)->queryOne();
        return $result;
    }

    public function set(Connection $connection)
    {
        $this->connection = $connection;
    }
}
