<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\IdentityBasedEntityInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read ?string $description
 * @property-read string $status
 * @property-read ?int $attachment_id
 * @property-read ?int $user_id
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Attachment $attachment
 * @property-read ?Admin $user
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Book publisher lists',
    ]
)]
#[Index(
    columns: [
        'name',
        'status',
    ],
    name: 'index_name_status'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_book_publishers_user_id_admins_id'
)]
#[Index(
    columns: ['attachment_id'],
    name: 'relation_book_publishers_attachment_id_attachments_id'
)]
#[HasLifecycleCallbacks]
class BookPublisher extends AbstractEntity implements IdentityBasedEntityInterface
{
    const TABLE_NAME = 'book_publishers';

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Publisher id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Publisher name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'description',
        type: Types::TEXT,
        length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT,
        nullable: true,
        options:  [
            'default' => null,
            'comment' => 'Publisher description'
        ]
    )]
    protected ?string $description = null;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Publisher status'
        ]
    )]
    protected string $status;

    #[Column(
        name: 'attachment_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Attachment id'
        ]
    )]
    protected ?int $attachment_id = null;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $user_id = null;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Publisher created time'
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
            'comment' => 'Publisher updated time'
        ],
    )]
    protected DateTimeInterface $updated_at;

    #[Column(
        name: 'deleted_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Publisher deleted time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'attachment_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_book_publishers_attachment_id_attachments_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Attachment::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Attachment $attachment = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_book_publishers_user_id_admins_id',
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

    #[OneToMany(
        mappedBy: 'publisher',
        targetEntity: Book::class,
        cascade: [
            'detach',
            'merge',
            'persist',
            'remove',
        ],
        fetch: 'LAZY',
        indexBy: 'id'
    )]
    protected ?Collection $books = null;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    protected array $entityBlackListedFields = [
        'books',
    ];

    public function __construct()
    {
        $this->user_id = null;
        $this->attachment_id = null;
        $this->description = null;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->deleted_at = null;
        $this->attachment = null;
        $this->user = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachment_id;
    }

    public function setAttachmentId(?int $attachment_id): void
    {
        $this->attachment_id = $attachment_id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
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

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    public function setAttachment(?Attachment $attachment): void
    {
        $this->attachment = $attachment;
        $this->setAttachmentId($attachment?->getId());
    }

    public function getUser(): ?Admin
    {
        return $this->user;
    }

    public function setUser(?Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user?->getId());
    }

    public function getBooks(): ?Collection
    {
        return $this->books;
    }
}
