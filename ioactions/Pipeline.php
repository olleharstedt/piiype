<?php

namespace app\ioactions;

// TODO: PipelineRunner
// TODO: Pipeline = collection of IOAction implementing ArrayAccess
// TODO: IOAction with cache/memo?
// TODO: Pipeline controller with IOAction factory
class Pipeline
{
    public function run($payload, $actions)
    {
        foreach ($actions as $action) {
            // Can also be callable.
            if ($action instanceof IOActionInterface) {
                $this->resolveDependencies($action);
            }
            if ($payload instanceof \SplFixedArray) {
                $result = $action(...$payload);
            } else {
                $result = $action($payload);
            }
            // Filters are special
            if ($action instanceof PipelineFilterInterface) {
                if ($result === false) {
                    if ($action->getError()) {
                        return $this->run(null, [$action->getError()]);
                    } else {
                        return;
                    }
                }
            } elseif ($result instanceof \Generator) {
                // Catch all yielded actions.
                foreach ($result as $yieldedAction) {
                    $payload = $this->run($payload, [$yieldedAction]);
                }
                $return = $result->getReturn();
                if (!is_null($return)) {
                    $oldPayload = $payload;
                    $payload = new \SplFixedArray(2);
                    $payload[0] = $oldPayload;
                    $payload[1] = $return;
                }
            } else {
                $payload = $result;
            }
        }
        return $payload;
    }

    public function resolveDependencies(IOActionInterface $ioaction)
    {
        $reflectionClass = new \ReflectionClass($ioaction);
        if (!$reflectionClass->hasMethod('set')) {
            return;
        }
        $set = $reflectionClass->getMethod('set');
        $params = $set->getParameters();
        if (count($params) === 0) {
            return;
        }
        $arg = null;
        foreach($params as $param) {
            $class = $param->getClass();
            if ($class->name == 'yii\db\Connection') {
                $arg = \Yii::$app->db;
            }
        }
        if ($arg) {
            $ioaction->set($arg);
        }
    }
}
