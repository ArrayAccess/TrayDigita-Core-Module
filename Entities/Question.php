<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\ParentIdEventStateTrait;
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
 * @property-read string $name
 * @property-read string $content
 * @property-read string $type
 * @property-read ?int $category_id
 * @property-read string $status
 * @property-read ?int $parent_id
 * @property-read ?int $user_id
 * @property-read ?DateTimeInterface $published_at
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Admin $user
 * @property-read ?Question $parent
 * @property-read ?QuestionCategory $category
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Question bank'
    ]
)]
#[Index(
    columns: ['name', 'status', 'hidden'],
    name: 'index_name_status_hidden'
)]
#[Index(
    columns: ['quiz_id'],
    name: 'relation_questions_quiz_id_quiz_id'
)]
#[Index(
    columns: ['parent_id'],
    name: 'relation_questions_parent_id_questions_id'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_questions_user_id_admins_id'
)]
#[Index(
    columns: ['category_id'],
    name: 'relation_questions_category_id_questions_categories_id'
)]
#[HasLifecycleCallbacks]
class Question extends AbstractEntity implements AvailabilityStatusEntityInterface
{
    use AvailabilityStatusTrait,
        ParentIdEventStateTrait;

    const TYPE_TRUE_FALSE = 1;

    const TYPE_MULTIPLE_CHOICE = 2;

    const TYPE_MULTIPLE_CHOICE_MULTIPLE_ANSWER = 3;

    const TYPE_ESSAY = 4;

    const TYPE_NUMERIC = 5;

    const TYPE_SHORT_ANSWER = 6;

    const TYPE_FILE_UPLOAD = 7;

    const TYPE_TEXT_UPLOAD = 7;

    const TYPE_DOCUMENT_UPLOAD = 8;

    const TYPE_IMAGE_UPLOAD = 9;

    const TYPE_DESCRIPTION = 10;


    const TABLE_NAME = 'questions';
    
    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key question id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'parent_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $parent_id = null;

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
        name: 'quiz_id',
        type: Types::BIGINT,
        length: 20,
        nullable: false,
        options:  [
            'unsigned' => true,
            'comment' => 'Quiz id'
        ]
    )]
    protected int $quiz_id;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Question name'
        ]
    )]
    protected string $name;

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
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'default' => self::DRAFT,
            'comment' => 'Question status'
        ]
    )]
    protected string $status = self::DRAFT;

    #[Column(
        name: 'question_content',
        type: Types::TEXT,
        length: 4294967295,
        nullable: false,
        options: [
            'comment' => 'Question content'
        ]
    )]
    protected ?string $content = null;

    #[Column(
        name: 'question_feedback',
        type: Types::TEXT,
        length: 4294967295,
        nullable: false,
        options: [
            'comment' => 'Question feedback about the answers'
        ]
    )]
    protected ?string $feedback = null;

    #[Column(
        name: 'hidden',
        type: Types::BOOLEAN,
        nullable: false,
        options: [
            'default' => false,
            'comment' => 'Set question hidden or not'
        ]
    )]
    protected bool $hidden = false;

    #[Column(
        name: 'type',
        type: Types::SMALLINT,
        length: 3,
        nullable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Question type'
        ]
    )]
    protected int $type;

    #[Column(
        name: 'minimum_answer_count',
        type: Types::SMALLINT,
        length: 3,
        nullable: false,
        options: [
            'unsigned' => true,
            'default' => 1,
            'comment' => 'Question minimum required to answer'
        ]
    )]
    protected int $minimum_answer_count = 1;

    #[Column(
        name: 'maximum_answer_count',
        type: Types::INTEGER,
        length: 5,
        nullable: false,
        options: [
            'unsigned' => false,
            'default' => 1,
            'comment' => 'Maximum answer to question. -1 means no limit'
        ]
    )]
    protected int $maximum_answer_count = -1;

    #[Column(
        name: 'default_mark',
        type: Types::DECIMAL,
        length: 5,
        precision: 10,
        scale: 5,
        nullable: false,
        options: [
            'default' => '1.00000',
            'unsigned' => true,
            'comment' => 'Question default mark per each question'
        ]
    )]
    protected float $default_mark = 1.00000;

    #[Column(
        name: 'penalty',
        type: Types::DECIMAL,
        length: 5,
        precision: 10,
        scale: 5,
        nullable: false,
        options: [
            'default' => '0.33333',
            'unsigned' => true,
            'comment' => 'Question penalty'
        ]
    )]
    protected float $penalty = 0.33333;

    #[Column(
        name: 'version',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Question version'
        ]
    )]
    protected ?string $version = null;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_MUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Question created time'
        ]
    )]
    protected DateTimeInterface $created_at;

    #[Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        unique: false,
        updatable: false,
        options: [
            'attribute' => 'ON UPDATE CURRENT_TIMESTAMP', // this column attribute
            'default' => '0000-00-00 00:00:00',
            'comment' => 'Question update time'
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
            'comment' => 'Question delete time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'quiz_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_questions_quiz_id_quiz_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
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
    protected Quiz $quiz;
    #[
        JoinColumn(
            name: 'category_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_questions_category_id_questions_categories_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: QuestionCategory::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?QuestionCategory $category = null;

    #[
        JoinColumn(
            name: 'parent_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_questions_parent_id_questions_id',
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
    protected ?Question $parent = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_questions_user_id_admins_id',
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

    public function __construct()
    {
        $this->user = null;
        $this->parent = null;
        $this->parent_id = null;
        $this->status = self::DRAFT;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->deleted_at = null;
    }

    public function getId() : int
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

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getQuizId(): int
    {
        return $this->quiz_id;
    }

    public function setQuizId(int $quiz_id): void
    {
        $this->quiz_id = $quiz_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getMinimumAnswerCount(): int
    {
        return $this->minimum_answer_count;
    }

    public function setMinimumAnswerCount(int $minimum_answer_count): void
    {
        $this->minimum_answer_count = $minimum_answer_count;
    }

    public function getMaximumAnswerCount(): int
    {
        return $this->maximum_answer_count;
    }

    public function setMaximumAnswerCount(int $maximum_answer_count): void
    {
        $this->maximum_answer_count = $maximum_answer_count;
    }

    public function getDefaultMark(): float
    {
        return $this->default_mark;
    }

    public function setDefaultMark(float $default_mark): void
    {
        $this->default_mark = $default_mark;
    }

    public function getPenalty(): float
    {
        return $this->penalty;
    }

    public function setPenalty(float $penalty): void
    {
        $this->penalty = $penalty;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function setCategoryId(?int $category_id): void
    {
        $this->category_id = $category_id;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): void
    {
        $this->feedback = $feedback;
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

    public function setDeletedAt(?DateTimeInterface $deletedAt) : void
    {
        $this->deleted_at = $deletedAt;
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(Quiz $quiz): void
    {
        $this->quiz = $quiz;
        $this->setQuizId($quiz->getId());
    }

    public function getParent(): ?Question
    {
        return $this->parent;
    }

    public function setParent(?Question $parent): void
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

    public function getCategory(): ?QuestionCategory
    {
        return $this->category;
    }

    public function setCategory(?QuestionCategory $category): void
    {
        $this->category = $category;
        $this->setCategoryId($category?->getId());
    }

    #[
        PreUpdate,
        PostLoad,
        PrePersist
    ]
    public function eventDataCheck(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        $this->parentIdCheck($event);
    }
}
