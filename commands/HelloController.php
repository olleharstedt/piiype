<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

use app\ioactions\EchoIOAction;
use app\ioactions\StdoutIOAction;
use app\ioactions\IOPayload;
use app\ioactions\QueryOneIOAction;
use app\ioactions\QueryIOAction;
use app\ioactions\PipelineFilter;
use app\ioactions\PipelineFilterInterface;
use app\ioactions\IOActionInterface;

interface IOFactoryInterface
{
}

class IOFactory implements IOFactoryInterface
{
    public function queryOne($sql)
    {
        return new QueryOneIOAction($sql);
    }

    public function query($sql)
    {
        return new QueryIOAction($sql);
    }

    public function printline($message)
    {
        return new EchoIOAction($message . PHP_EOL);
    }

    public function filterNotEmpty(IOActionInterface $error)
    {
        return new PipelineFilter(
            function($result) { return !empty($result); },
            $error
        );
    }
}

class Mock implements IOFactoryInterface
{
    private $results;
    private $i = 0;
    public $args = [];
    public function __construct($results)
    {
        $this->results = $results;
    }
    public function __call($name, $args)
    {
        if (!array_key_exists($this->i, $this->results)) {
            throw new \Exception('No result at i = ' . $this->i);
        }
        $this->args[] = $args;
        echo $args[0] . PHP_EOL;
        $result = $this->results[$this->i++];
        return function () use ($result) { return $result; };
    }
}

class FilterNotEmpty extends PipelineFilter
{
    public function __construct($error = null)
    {
        $this->filter = function ($r) { return !empty($r); };
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

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return array<int, IOActionInterface|callable|Generator>
     */
    public function actionIndex($userId = 1)
    {
        /*
        $io = new IOFactory();
         */
        $io = new Mock(
            [
                false, //['id' => 1, 'is_admin' => 1],
                null,
                null,
                null,
                null,
            ]
        );
        return [
            $io->queryOne('SELECT * FROM users WHERE id = ' . $userId),
            new FilterNotEmpty($io->printline('Found no such user')),
            function ($user) use ($io, $userId) {
                yield $io->printline('Yay, found user!');
                $reverted = $user['is_admin'] ? 0 : 1;
                yield $io->query(
                    sprintf(
                        'UPDATE users SET is_admin = %d WHERE id = %d',
                        $reverted,
                        $user['id']
                    )
                );
                if ($reverted === 1) {
                    yield $io->printline('User is now admin');
                } else {
                    yield $io->printline('User is no longer admin');
                }
                return ExitCode::OK;
            }
        ];
    }
}
