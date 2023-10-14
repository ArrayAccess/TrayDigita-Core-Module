<?php
/** @noinspection PhpUnused */
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
 * @property-read int faculty_id
 * @property-read Department $faculty
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Faculty metadata',
        'primaryKey' => [
            'faculty_id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['faculty_id'],
    name: 'relation_faculty_meta_faculty_id_faculties_id'
)]
#[HasLifecycleCallbacks]
class FacultyMeta extends AbstractBasedMeta
{
    const TABLE_NAME = 'faculty_meta';

    #[Id]
    #[Column(
        name: 'faculty_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $faculty_id;

    #[
        JoinColumn(
            name: 'faculty_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_faculty_meta_faculty_id_faculties_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Faculty::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected Faculty $faculty;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getDepartmentId(): int
    {
        return $this->faculty_id;
    }

    public function setDepartmentId(int $faculty_id): void
    {
        $this->faculty_id = $faculty_id;
    }

    public function setFaculty(Faculty $faculty): void
    {
        $this->faculty = $faculty;
        $this->setDepartmentId($faculty->getId());
    }

    public function getFaculty(): ?Faculty
    {
        return $this->faculty;
    }
}
