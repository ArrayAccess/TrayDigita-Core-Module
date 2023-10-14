<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\TypeList;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @property-read string $name
 * @property-read mixed $value
 * @property-read bool $autoload
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Site settings',
    ]
)]
#[HasLifecycleCallbacks]
class Option extends AbstractEntity
{
    const TABLE_NAME = 'options';

    #[Id]
    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        updatable: true,
        options: [
            'comment' => 'Option name primary key'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'value',
        type: TypeList::DATA,
        length: 4294967295,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Option value data'
        ]
    )]
    protected mixed $value = null;

    #[Column(
        name: 'autoload',
        type: Types::BOOLEAN,
        nullable: false,
        options: [
            'default' => false,
            'comment' => 'Autoload reference'
        ]
    )]
    protected bool $autoload = false;

    public function __construct()
    {
        $this->autoload = false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isAutoload(): bool
    {
        return $this->autoload;
    }

    public function setAutoload(bool $autoload): void
    {
        $this->autoload = $autoload;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
