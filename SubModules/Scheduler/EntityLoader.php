<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Scheduler;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\TaskScheduler;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Scheduler\Abstracts\Task;
use ArrayAccess\TrayDigita\Scheduler\Interfaces\MessageInterface;
use ArrayAccess\TrayDigita\Scheduler\LastRecord;
use ArrayAccess\TrayDigita\Scheduler\LocalRecordLoader;
use ArrayAccess\TrayDigita\Scheduler\Messages\Exited;
use ArrayAccess\TrayDigita\Scheduler\Messages\Failure;
use ArrayAccess\TrayDigita\Scheduler\Messages\Progress;
use ArrayAccess\TrayDigita\Scheduler\Messages\Skipped;
use ArrayAccess\TrayDigita\Scheduler\Messages\Stopped;
use ArrayAccess\TrayDigita\Scheduler\Messages\Success;
use ArrayAccess\TrayDigita\Scheduler\Messages\Unknown;
use ArrayAccess\TrayDigita\Scheduler\Runner;
use ArrayAccess\TrayDigita\Scheduler\Scheduler;
use Stringable;
use function is_string;

class EntityLoader extends LocalRecordLoader
{
    const FINISH = 'finish';
    const PROGRESS = 'progress';
    const SKIPPED = 'skipped';
    const EXITED = 'exited';

    public function __construct(protected Connection $connection)
    {
        parent::__construct($this->connection->getContainer());
    }

    private function getRecordEntity(Task $task) : ?TaskScheduler
    {
        $id = $this->taskNameHash($task);
        return $this
            ->connection
            ->find(
                TaskScheduler::class,
                $id
            );
    }

    public function getRecord(Task $task): ?LastRecord
    {
        $id = $this->taskNameHash($task);
        if (isset($this->lastRecords[$id])) {
            return $this->lastRecords[$id];
        }
        $entity = $this->getRecordEntity($task);
        if (!$entity) {
            return null;
        }

        $lastExecutionTime = $entity->getExecutionTime();
        $statusCode = $entity->getStatusCode();
        $message = $entity->getMessage();
        if ($message instanceof LastRecord) {
            $messageLastExecutionTime = $message->getLastExecutionTime();
            $messageStatusCode = $message->getStatusCode();
            $message = $message->getMessage();
            if (($statusCode === Runner::STATUS_UNKNOWN
                || $statusCode === Runner::STATUS_QUEUE)
                && (
                    $messageStatusCode !== Runner::STATUS_UNKNOWN
                    && $messageStatusCode !== Runner::STATUS_PROGRESS
                )
            ) {
                $statusCode = $message->getStatusCode();
            }
            if ($messageLastExecutionTime > Runner::PREVIOUS_MIN_TIME
                && ($lastExecutionTime === 0 || $lastExecutionTime)) {
                $lastExecutionTime = $messageLastExecutionTime;
            }
        } elseif (!$message instanceof MessageInterface) {
            if ($message !== null
                && !is_string($message)
                && !$message instanceof Stringable
            ) {
                $message = null;
            }
            $message = match ($entity->getStatusCode()) {
                Runner::STATUS_SKIPPED => new Skipped($message),
                Runner::STATUS_FAILURE => new Failure($message),
                Runner::STATUS_EXITED => new Exited($message),
                Runner::STATUS_PROGRESS => new Progress($message),
                Runner::STATUS_STOPPED => new Stopped($message),
                Runner::STATUS_SUCCESS => new Success($message),
                default => new Unknown($message),
            };
        }

        return $this->executionRecords[$id] = (new LastRecord(
            $task,
            $lastExecutionTime,
            $message
        ))->withStatusCode($statusCode);
    }

    private function saveRecord(LastRecord $record, ?string $status = null): void
    {
        $isFinish = $status === self::FINISH;
        switch ($status) {
            case self::FINISH:
            case self::PROGRESS:
            case self::SKIPPED:
            case self::EXITED:
                $status = match ($status) {
                    self::PROGRESS => Runner::STATUS_PROGRESS,
                    self::SKIPPED => Runner::STATUS_SKIPPED,
                    self::EXITED => Runner::STATUS_EXITED,
                    default => $record->getStatusCode()
                };
                $task = $record->getTask();
                $entity = $this->getRecordEntity($task);
                if (!$entity) {
                    $entity = new TaskScheduler();
                    $entity->setName($task->getName());
                    $entity->setIdentity($this->taskNameHash($task));
                }
                $entity->setFinishTime(
                    $isFinish ? time() : $entity->getFinishTime()
                );
                $entity->setExecutedObjectClass($task::class);
                $entity->setExecutionTime($record->getLastExecutionTime());
                $entity->setMessage($record->getMessage());
                $entity->setStatusCode($status);
                $em = $this->connection->getEntityManager();
                $em->persist($entity);
                $em->flush();
                return;
        }
    }

    public function storeExitRunner(Runner $runner, Scheduler $scheduler): ?LastRecord
    {
        $record = parent::storeExitRunner($runner, $scheduler);
        if ($record) {
            $this->saveRecord($record, self::EXITED);
        }
        return $record;
    }

    public function doSkipProgress(Runner $runner, Scheduler $scheduler): ?LastRecord
    {
        $record = parent::doSkipProgress($runner, $scheduler);
        if ($record) {
            $this->saveRecord($record, self::SKIPPED);
        }
        return $record;
    }

    public function doStartProgress(Runner $runner, Scheduler $scheduler): ?LastRecord
    {
        $record = parent::doStartProgress($runner, $scheduler);
        if ($record) {
            $this->saveRecord($record, self::PROGRESS);
        }
        return $record;
    }

    public function finish(int $executionTime, Runner $runner, Scheduler $scheduler): LastRecord
    {
        $record = parent::finish($executionTime, $runner, $scheduler);
        if ($record) {
            $this->saveRecord($record, self::FINISH);
        }
        return $record;
    }

    protected function doSaveRecords(bool $isFinish = false): void
    {
    }
}
