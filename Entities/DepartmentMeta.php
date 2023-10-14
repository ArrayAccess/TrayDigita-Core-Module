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

/**
 * @property-read int $department_id
 * @property-read Department $department
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Department metadata',
        'primaryKey' => [
            'department_id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['department_id'],
    name: 'relation_department_meta_department_id_departments_id'
)]
#[HasLifecycleCallbacks]
class DepartmentMeta extends AbstractBasedMeta
{
    const TABLE_NAME = 'department_meta';

    #[Id]
    #[Column(
        name: 'department_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $department_id;

    #[
        JoinColumn(
            name: 'department_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_department_meta_department_id_departments_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Department::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected Department $department;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getDepartmentId(): int
    {
        return $this->department_id;
    }

    public function setDepartmentId(int $department_id): void
    {
        $this->department_id = $department_id;
    }

    public function setClass(Department $department): void
    {
        $this->department = $department;
        $this->setDepartmentId($department->getId());
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }
}
