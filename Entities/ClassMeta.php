<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractBasedMeta;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Classes metadata',
        'primaryKey' => [
            'class_id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['class_id'],
    name: 'relation_class_meta_class_id_classes_id'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $class_id
 * @property-read Classes $class
 */
class ClassMeta extends AbstractBasedMeta
{
    const TABLE_NAME = 'class_meta';

    #[Id]
    #[Column(
        name: 'class_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $class_id;

    #[
        JoinColumn(
            name: 'class_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_class_meta_class_id_classes_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Classes::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected Classes $class;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getClassId(): int
    {
        return $this->class_id;
    }

    public function setClassId(int $class_id): void
    {
        $this->class_id = $class_id;
    }

    public function setClass(Classes $class): void
    {
        $this->class = $class;
        $this->setClassId($class->getId());
    }

    public function getClass(): ?Classes
    {
        return $this->class;
    }
}
