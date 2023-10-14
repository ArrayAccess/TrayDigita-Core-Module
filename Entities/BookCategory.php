<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\IdentityBasedEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\ParentIdEventStateTrait;
use ArrayAccess\TrayDigita\Util\Generator\UUID;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @property-read int $id
 * @property-read ?int $parent_id
 * @property-read string $name
 * @property-read ?string $description
 * @property-read string $slug
 * @property-read ?int $user_id
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?BookCategory $parent
 * @property-read ?Admin $user
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Book category metadata',
    ]
)]
#[UniqueConstraint(
    name: 'unique_slug',
    columns: ['slug']
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['parent_id'],
    name: 'relation_book_categories_parent_id_book_categories_id'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_book_categories_user_id_admins_id'
)]
#[HasLifecycleCallbacks]
class BookCategory extends AbstractEntity implements IdentityBasedEntityInterface
{
    const TABLE_NAME = 'book_categories';

    use ParentIdEventStateTrait;

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key category id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'parent_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Parent category id'
        ]
    )]
    protected ?int $parent_id = null;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Category name'
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
            'comment' => 'Category description'
        ]
    )]
    protected ?string $description = null;

    #[Column(
        name: 'slug',
        type: Types::STRING,
        length: 255,
        unique: true,
        nullable: false,
        options: [
            'comment' => 'Unique slug for category'
        ]
    )]
    protected string $slug;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
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
            'comment' => 'Category created time'
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
            'comment' => 'Category updated time'
        ],
        // columnDefinition: "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP"
    )]
    protected DateTimeInterface $updated_at;

    #[
        JoinColumn(
            name: 'parent_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_book_categories_parent_id_book_categories_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: self::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?BookCategory $parent = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_book_categories_user_id_admins_id',
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

    #[
        PrePersist,
        PreUpdate
    ]
    public function preCheckSlug(PrePersistEventArgs|PreUpdateEventArgs $event): void
    {
        $oldSlug = null;

        $slug = $this->getSlug();
        $isUpdate = $event instanceof PreUpdateEventArgs;
        if ($isUpdate) {
            if (!$event->hasChangedField('slug')) {
                return;
            }
            $oldSlug = $event->getOldValue('slug');
            $slug = $event->getNewValue('slug')?:$slug;
        }

        if ($oldSlug === $slug) {
            return;
        }

        if (trim($slug) === '') {
            $slug = UUID::v4();
        }
        do {
            $this->slug = $slug;
            $query = $event
                ->getObjectManager()
                ->getRepository($this::class)
                ->createQueryBuilder('category')
                ->where('category.slug = :slug')
                ->setParameter('slug', $slug)
                ->setMaxResults(1)
                ->getQuery()
                ->execute();
        } while (!empty($query) && ($slug = UUID::v4()));
        if ($isUpdate) {
            $event->setNewValue('slug', $slug);
        }
    }

    public function __construct()
    {
        $this->parent_id = null;
        $this->description = null;
        $this->user_id = null;
        $this->slug = UUID::v4();
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->parent = null;
        $this->user = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function setParentId(?int $parent_id): void
    {
        $this->parent_id = $parent_id;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
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

    public function getParent(): ?BookCategory
    {
        return $this->parent;
    }

    public function setParent(?BookCategory $parent): void
    {
        $this->parent = $parent;
        $this->setParentId($parent?->getId());
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

    #[
        PreUpdate,
        PostLoad,
        PrePersist
    ]
    public function checkDataEvent(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        $this->parentIdCheck($event);
    }
}
