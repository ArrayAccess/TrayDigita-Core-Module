<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Attachment;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\IdentityBasedEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\ParentIdEventStateTrait;
use ArrayAccess\TrayDigita\Database\TypeList;
use DateTimeImmutable;
use DateTimeInterface;
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

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read ?string $description
 * @property-read ?int $parent_id
 * @property-read ?int $author_id
 * @property-read ?int $publisher_id
 * @property-read ?int $category_id
 * @property-read ?int $attachment_id
 * @property-read int $revisions
 * @property-read string $status
 * @property-read ?int $user_id
 * @property-read ?DateTimeInterface $release_year
 * @property-read ?DateTimeInterface $published_at
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?BookAuthor $author
 * @property-read ?BookCategory $category
 * @property-read ?BookPublisher $publisher
 * @property-read ?Admin $user
 * @property-read ?Attachment $attachment
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Book lists',
    ]
)]
#[Index(
    columns: ['author_id'],
    name: 'relation_books_author_id_book_authors_id'
)]
#[Index(
    columns: ['parent_id'],
    name: 'relation_books_parent_id_books_id'
)]
#[Index(
    columns: ['category_id'],
    name: 'relation_books_category_id_book_categories_id'
)]
#[Index(
    columns: ['attachment_id'],
    name: 'relation_books_attachment_id_attachments_id'
)]
#[Index(
    columns: ['publisher_id'],
    name: 'relation_books_publisher_id_book_publishers_id'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_books_user_id_admins_id'
)]
#[Index(
    columns: [
        'title',
        'status',
        'author_id',
        'publisher_id',
        'release_year',
        'revisions',
        'published_at',
    ],
    name: 'index_title_status_author_id_publisher_id_release_year_rev_p_at'
)]
#[HasLifecycleCallbacks]
class Book extends AbstractEntity implements IdentityBasedEntityInterface, AvailabilityStatusEntityInterface
{
    const TABLE_NAME = 'books';

    use AvailabilityStatusTrait,
        ParentIdEventStateTrait;

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Book id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'title',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Book title'
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
            'comment' => 'Book description / synopsis'
        ]
    )]
    protected ?string $content = null;

    #[Column(
        name: 'parent_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'comment' => 'Book parent id'
        ]
    )]
    protected ?int $parent_id = null;

    #[Column(
        name: 'author_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Author id'
        ]
    )]
    protected ?int $author_id = null;

    #[Column(
        name: 'publisher_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Publisher id'
        ]
    )]
    protected ?int $publisher_id = null;

    #[Column(
        name: 'category_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Category id'
        ]
    )]
    protected ?int $category_id = null;

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
        name: 'revisions',
        type: Types::BIGINT,
        length: 20,
        nullable: false,
        options:  [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'Revision number'
        ]
    )]
    protected int $revisions = 0;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Book status'
        ]
    )]
    protected string $status;

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
        name: 'release_year',
        type: TypeList::YEAR,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Book release year'
        ]
    )]
    protected ?DateTimeInterface $release_year = null;

    #[Column(
        name: 'published_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Date published'
        ]
    )]
    protected ?DateTimeInterface $published_at = null;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Book data created time'
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
            'comment' => 'Book data updated time'
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
            'comment' => 'Book data deleted time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'parent_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_parent_id_books_id',
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
    protected ?Book $parent = null;

    #[
        JoinColumn(
            name: 'author_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_author_id_book_authors_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: BookAuthor::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?BookAuthor $author = null;

    #[
        JoinColumn(
            name: 'category_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_category_id_book_categories_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: BookCategory::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?BookCategory $category = null;

    #[
        JoinColumn(
            name: 'attachment_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_attachment_id_attachments_id',
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
            name: 'publisher_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_publisher_id_book_publishers_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: BookPublisher::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?BookPublisher $publisher = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_books_user_id_admins_id',
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

    protected bool $entityAllowAssociations = true;
    public function __construct()
    {
        $this->revisions = 0;
        $this->user_id = null;
        $this->attachment_id = null;
        $this->author_id = null;
        $this->publisher_id = null;
        $this->release_year = null;
        $this->content = null;
        $this->published_at = null;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->deleted_at = null;

        $this->attachment = null;
        $this->author = null;
        $this->user = null;
        $this->publisher = null;
        $this->category = null;
        $this->parent = null;
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

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function setParentId(?int $parent_id): void
    {
        $this->parent_id = $parent_id;
    }

    public function getAuthorId(): ?int
    {
        return $this->author_id;
    }

    public function setAuthorId(?int $author_id): void
    {
        $this->author_id = $author_id;
    }

    public function getPublisherId(): ?int
    {
        return $this->publisher_id;
    }

    public function setPublisherId(?int $publisher_id): void
    {
        $this->publisher_id = $publisher_id;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function setCategoryId(?int $category_id): void
    {
        $this->category_id = $category_id;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachment_id;
    }

    public function setAttachmentId(?int $attachment_id): void
    {
        $this->attachment_id = $attachment_id;
    }

    public function getRevisions(): int
    {
        return $this->revisions;
    }

    public function setRevisions(int $revisions): void
    {
        $this->revisions = $revisions;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getReleaseYear(): ?DateTimeInterface
    {
        return $this->release_year;
    }

    public function setReleaseYear(DateTimeInterface|int|null $release_year): void
    {
        /** @noinspection DuplicatedCode */
        if (is_int($release_year)) {
            $release_year = (string) $release_year;
            if ($release_year < 1000) {
                do {
                    $release_year = "0$release_year";
                } while (strlen($release_year) < 4);
            }
            $release_year = substr($release_year, 0, 4);
            $release_year = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                "$release_year-01-01"
            )?:new DateTimeImmutable("$release_year-01-01 00:00:00");
        }

        $this->release_year = $release_year;
    }

    public function getPublishedAt(): ?DateTimeInterface
    {
        return $this->published_at;
    }

    public function setPublishedAt(?DateTimeInterface $published_at): void
    {
        $this->published_at = $published_at;
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

    public function getParent(): ?Book
    {
        return $this->parent;
    }

    public function setParent(?Book $parent): void
    {
        $this->parent = $parent;
        $this->setParentId($parent?->getId());
    }

    // relation

    public function setBookAuthor(?BookAuthor $author): void
    {
        $this->author = $author;
        $this->setAuthorId($author?->getId());
    }

    public function getAuthor(): ?BookAuthor
    {
        return $this->author;
    }

    public function setCategory(?BookCategory $category): void
    {
        $this->category = $category;
        $this->setCategoryId($category?->getId());
    }

    public function getCategory(): ?BookCategory
    {
        return $this->category;
    }

    public function setPublisher(?BookPublisher $publisher): void
    {
        $this->publisher = $publisher;
        $this->setPublisherId($publisher?->getId());
    }

    public function getPublisher(): ?BookPublisher
    {
        return $this->publisher;
    }

    public function setAttachment(?Attachment $attachment): void
    {
        $this->attachment = $attachment;
        $this->setAttachmentId($attachment?->getId());
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    public function setUser(Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user->getId());
    }

    public function getUser(): Admin
    {
        return $this->user;
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
