<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Announcement\Helper\AnnouncementTarget;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\IdentityBasedEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read ?string $content
 * @property-read ?int $user_id
 * @property-read string $status
 * @property-read string $target
 * @property-read ?int $target_id
 * @property-read ?DateTimeInterface $expired_at
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Admin $user
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Announcements'
    ]
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_announcements_user_id_admins_id'
)]
#[Index(
    columns: ['title', 'status', 'target', 'target_id'],
    name: 'index_title_status_target_target_id'
)]
#[HasLifecycleCallbacks]
class Announcement extends AbstractEntity implements IdentityBasedEntityInterface, AvailabilityStatusEntityInterface
{
    const TABLE_NAME = 'announcements';

    const TARGET_ALL = 'all';

    const TARGET_CLASS = 'class';

    const TARGET_FACULTY = 'faculty';

    const TARGET_DEPARTMENT = 'department';

    const TARGET_USER = 'user';

    const TARGET_ADMIN = 'admin';

    use AvailabilityStatusTrait;

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Attachment Id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'title',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Announcement title'
        ]
    )]
    protected string $title;

    #[Column(
        name: 'content',
        type: Types::TEXT,
        length: 4294967295,
        nullable: true,
        options:  [
            'default' => null,
            'comment' => 'Announcement content'
        ]
    )]
    protected ?string $content = null;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $user_id = null;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Announcement status'
        ]
    )]
    protected string $status;

    #[Column(
        name: 'target',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: [
            'comment' => 'Announcement target'
        ]
    )]
    protected ?string $target;

    #[Column(
        name: 'target_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Target identity'
        ]
    )]
    protected ?int $target_id = null;

    #[Column(
        name: 'expired_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        updatable: true,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Announcement created time'
        ]
    )]
    protected ?DateTimeInterface $expired_at;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Announcement created time'
        ]
    )]
    protected DateTimeInterface $created_at;

    #[Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        unique: false,
        updatable: false,
        options: [
            'attribute' => 'ON UPDATE CURRENT_TIMESTAMP',
            'default' => '0000-00-00 00:00:00',
            'comment' => 'Announcement update time'
        ],
        // columnDefinition: "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP"
    )]
    protected DateTimeInterface $updated_at;

    #[Column(
        name: 'deleted_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Announcement delete time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_announcements_user_id_admins_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Admin::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Admin $user = null;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function __construct()
    {
        $this->target = null;
        $this->target_id = null;
        $this->expired_at = null;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->deleted_at = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): void
    {
        $this->target = $target;
    }

    public function getTargetId(): ?int
    {
        return $this->target_id;
    }

    public function setTargetId(?int $target_id): void
    {
        $this->target_id = $target_id;
    }

    public function getExpiredAt(): ?DateTimeInterface
    {
        return $this->expired_at;
    }

    public function setExpiredAt(?DateTimeInterface $expired_at): void
    {
        $this->expired_at = $expired_at;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTimeInterface $deleted_at): void
    {
        $this->deleted_at = $deleted_at;
    }

    public function setUser(?Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user?->getId());
    }

    public function getUser(): ?Admin
    {
        return $this->user;
    }

    protected ?AnnouncementTarget $targetObject = null;

    public function getTargetObject() : AnnouncementTarget
    {
        return $this->targetObject ??= new AnnouncementTarget($this);
    }
}
