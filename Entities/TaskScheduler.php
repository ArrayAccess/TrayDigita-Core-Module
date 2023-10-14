<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\TypeList;
use ArrayAccess\TrayDigita\Scheduler\Runner;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * @property-read string $identity
 * @property-read string $name
 * @property-read ?string $executed_object_class
 * @property-read int $status_code
 * @property-read int $execution_time
 * @property-read ?int $finish_time
 * @property-read mixed $message
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Scheduler record list',
        'primaryKey' => [
            'identity'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['executed_object_class'],
    name: 'index_executed_object_class'
)]
#[Index(
    columns: ['status_code', 'execution_time', 'finish_time'],
    name: 'index_status_code_execution_time_finish_time'
)]
#[HasLifecycleCallbacks]
class TaskScheduler extends AbstractEntity
{
    const TABLE_NAME = 'task_schedulers';
    
    #[Id]
    #[Column(
        name: 'identity',
        type: Types::STRING,
        length: 255,
        options: [
            'primaryKey' => true,
            'comment' => 'Task identity'
        ]
    )]
    protected string $identity;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        options: [
            'comment' => 'Task name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'executed_object_class',
        type: Types::STRING,
        length: 512,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Executed class name'
        ]
    )]
    protected ?string $executed_object_class = null;

    #[Column(
        name: 'status_code',
        type: Types::SMALLINT,
        length: 3,
        options: [
            'unsigned' => true,
            'default' => Runner::STATUS_QUEUE,
            'comment' => 'Task status code'
        ]
    )]
    protected int $status_code = Runner::STATUS_QUEUE;

    #[Column(
        name: 'execution_time',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'Task start executed'
        ]
    )]
    protected int $execution_time = 0;

    #[Column(
        name: 'finish_time',
        type: Types::INTEGER,
        length: 10,
        nullable: true,
        options: [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Cron interval'
        ]
    )]
    protected ?int $finish_time = null;

    #[Column(
        name: 'message',
        type: TypeList::DATA_BLOB,
        length: 4294967295,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Option value data'
        ]
    )]
    protected mixed $message = null;

    public function __construct()
    {
        $this->execution_time = 0;
        $this->finish_time = null;
        $this->message = null;
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function setIdentity(string $identity): void
    {
        $this->identity = $identity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExecutedObjectClass(): ?string
    {
        return $this->executed_object_class;
    }

    public function setExecutedObjectClass(?string $executed_object_class): void
    {
        $this->executed_object_class = $executed_object_class;
    }

    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    public function setStatusCode(int $status_code): void
    {
        $this->status_code = $status_code;
    }

    public function getExecutionTime(): int
    {
        return $this->execution_time;
    }

    public function setExecutionTime(int $execution_time): void
    {
        $this->execution_time = $execution_time;
    }

    public function getFinishTime(): ?int
    {
        return $this->finish_time;
    }

    public function setFinishTime(?int $finish_time): void
    {
        $this->finish_time = $finish_time;
    }

    public function getMessage(): mixed
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }
}
