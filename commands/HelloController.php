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
}

class Mock implements IOFactoryInterface
{
    private $results;
    private $i = 0;
    private $args = [];

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function __call($name, $args)
    {
        if (!array_key_exists($this->i, $this->results)) {
            $result = null;
        } else {
            $result = $this->results[$this->i];
        }
        $this->i++;

        // Spy
        $this->args[] = $args;

        return function () use ($result, $args) {
            echo $args[0] . PHP_EOL;
            return $result;
        };
    }
}

class FilterEmpty extends PipelineFilter
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
     * @param IO $io
     * @return array<int, IOActionInterface|callable|Generator>
     */
    public function actionIndex($userId = 1, $io)
    {
        /*
        $io = new Mock(
            [
                0 => ['id' => 1, 'is_admin' => 0],
            ]
        );
         */

        // Array of promises, filters or callables.
        return [
            // TODO: Multiple queries before business logic
            $io->db->queryOne('SELECT * FROM users WHERE id = ' . $userId),
            new FilterEmpty($io->stdout->printline('Found no such user')),

            /**
             * @param array $user TODO: Filter to convert array -> object
             * @return int
             */
            function (array $user) use ($io) {
                yield $io->stdout->printline('Yay, found user!');
                $becomeAdmin = $user['is_admin'] ? 0 : 1;
                yield [
                    $io->db->query(
                        sprintf(
                            'UPDATE users SET is_admin = %d WHERE id = %d',
                            $becomeAdmin,
                            $user['id']
                        )
                    ),
                    $io->exec('rm adminfile')
                ];
                return $becomeAdmin;
            },

            /**
             * NB: If previous closure has both yields and return, two arguments will be sent to next closure.
             * @param int $becomeAdmin Return from previous closure's return
             * @return int
             */
            function ($becomeAdmin) use ($io) {
                if ($becomeAdmin === 1) {
                    yield $io->stdout->printline('User is now admin');
                } else {
                    yield $io->stdout->printline('User is no longer admin');
                }
                return ExitCode::OK;
            }
        ];
    }

    /**
     * @return
     */
    public function actionIndex2($userId, $appId, $io)
    {
        return [
            $io->queryOne('SELECT * FROM users WHERE id = ' . $userId),
            new FilterEmpty($io->printline('Found no such user')),
            function ($user) use ($io) {
            }
        ];
    }
}
