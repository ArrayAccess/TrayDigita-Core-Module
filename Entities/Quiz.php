<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\PasswordTrait;
use ArrayAccess\TrayDigita\Event\Interfaces\ManagerAllocatorInterface;
use ArrayAccess\TrayDigita\Traits\Manager\ManagerAllocatorTrait;
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
use function is_string;
use function strtolower;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read ?string $description
 * @property-read string $status
 * @property-read ?string $password
 * @property-read bool $password_protected
 * @property-read int $time_open
 * @property-read int $time_close
 * @property-read int $time_limit
 * @property-read int $max_attempts
 * @property-read bool $attempt_previous
 * @property-read bool $attempt_unanswered
 * @property-read bool $allow_skip
 * @property-read int $redo_questions
 * @property-read bool $shuffle_answers
 * @property-read bool $shuffle_questions
 * @property-read int $decimal_points
 * @property-read int $question_decimal_points
 * @property-read int $question_per_page
 * @property-read int $decimal_grade
 * @property-read float $maximum_grade
 * @property-read float $sum_grades
 * @property-read string $overdue_handling
 * @property-read int $grace_period
 * @property-read int $review_attempts
 * @property-read int $review_correctness
 * @property-read int $review_marks
 * @property-read bool $preview_right_answers
 * @property-read bool $reveal_right_answers
 * @property-read ?int $user_id
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Admin $user
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Quiz settings'
    ]
)]
#[Index(
    columns: ['name', 'status'],
    name: 'index_name_status'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_quiz_user_id_admins_id'
)]
#[HasLifecycleCallbacks]
class Quiz extends AbstractEntity implements AvailabilityStatusEntityInterface, ManagerAllocatorInterface
{
    use AvailabilityStatusTrait,
        ManagerAllocatorTrait,
        PasswordTrait;

    const TABLE_NAME = 'quiz';
    
    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key quiz id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Quiz name'
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
            'comment' => 'Quiz information'
        ]
    )]
    protected ?string $description = null;


    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Quiz status'
        ]
    )]
    protected string $status;


    #[Column(
        name: 'password',
        type: Types::STRING,
        length: 255,
        nullable: true,
        updatable: true,
        options: [
            'comment' => 'Quiz password'
        ]
    )]
    protected ?string $password = null;

    #[Column(
        name: 'password_protected',
        type: Types::BOOLEAN,
        options: [
            'comment' => 'Protect quiz with password'
        ]
    )]
    protected bool $password_protected = false;

    #[Column(
        name: 'time_open',
        type: Types::BIGINT,
        length: 20,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'The time when this quiz opens. (0 = no restriction.)'
        ]
    )]
    protected int $time_open = 0;

    #[Column(
        name: 'time_close',
        type: Types::BIGINT,
        length: 20,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'The time when this quiz close. (0 = no restriction.)'
        ]
    )]
    protected int $time_close = 0;

    #[Column(
        name: 'time_limit',
        type: Types::BIGINT,
        length: 20,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'The time limit for quiz attempts, in seconds.'
        ]
    )]
    protected int $time_limit = 0;

    #[Column(
        name: 'max_attempts',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'The maximum number of attempts a student is allowed.'
        ]
    )]
    protected int $max_attempts = 0;

    #[Column(
        name: 'attempt_previous',
        type: Types::BOOLEAN,
        options: [
            'default' => true,
            'comment' => 'Whether subsequent attempts start from the answer'
                .' to the previous attempt (true) or start blank (false).'
        ]
    )]
    protected bool $attempt_previous = true;

    #[Column(
        name: 'attempt_unanswered',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => 'Whether subsequent attempts start from the not answered question or use the normal stage.'
        ]
    )]
    protected bool $attempt_unanswered = false;

    #[Column(
        name: 'allow_skip',
        type: Types::BOOLEAN,
        options: [
            'default' => true,
            'comment' => 'Allow skip / jump to next question'
        ]
    )]
    protected bool $allow_skip = true;

    #[Column(
        name: 'redo_questions',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'Allows students to redo any completed question within a quiz attempt.'
        ]
    )]
    protected int $redo_questions = 0;

    #[Column(
        name: 'shuffle_answers',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => 'Shuffle the quiz answers.'
        ]
    )]
    protected bool $shuffle_answers = false;

    #[Column(
        name: 'shuffle_questions',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => 'Shuffle the quiz questions.'
        ]
    )]
    protected bool $shuffle_questions = false;

    #[Column(
        name: 'decimal_points',
        type: Types::SMALLINT,
        length: 5,
        options: [
            'unsigned' => true,
            'default' => 2,
            'comment' => 'Number of decimal points to use when displaying grades.'
        ]
    )]
    protected int $decimal_points = 2;

    #[Column(
        name: 'question_decimal_points',
        type: Types::SMALLINT,
        length: 5,
        options: [
            'unsigned' => false,
            'default' => -1,
            'comment' => 'Number of decimal points to use when displaying question grades.'
                . ' (-1 means use decimal_points.)'
        ]
    )]
    protected int $question_decimal_points = -1;

    #[Column(
        name: 'question_per_page',
        type: Types::BIGINT,
        length: 20,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => 'Question counts per page.'
        ]
    )]
    protected int $question_per_page = 0;

    #[Column(
        name: 'decimal_grade',
        type: Types::SMALLINT,
        length: 5,
        options: [
            'unsigned' => true,
            'default' => 2,
            'comment' => 'The total that the quiz overall grade is scaled to be out of.'
        ]
    )]
    protected int $decimal_grade = 2;

    #[Column(
        name: 'grade',
        type: Types::DECIMAL,
        length: 5,
        precision: 10,
        scale: 5,
        options: [
            'unsigned' => true,
            'default' => '0.00000',
            'comment' => 'The total that the quiz overall grade / difficulty is scaled to be out of.'
        ]
    )]
    protected float $maximum_grade = 0.00000;

    #[Column(
        name: 'sum_grades',
        type: Types::DECIMAL,
        length: 5,
        precision: 10,
        scale: 5,
        options: [
            'unsigned' => true,
            'default' => '0.00000', // use string 0.00000 / 5 decimal points as scale
            'comment' => 'Maximum grade for assessment weight.'
        ]
    )]
    protected float $sum_grades = 0.00000;

    const AUTOBANDON = 'autobandon';
    const AUTOSUBMIT = 'autosubmit';
    const GRACEPERIOD = 'graceperiod';

    #[Column(
        name: 'overdue_handling',
        type: Types::STRING,
        length: 64,
        options: [
            'default' => self::AUTOBANDON,
            'comment' => "The method used to handle overdue attempts. 'autosubmit', 'graceperiod' or 'autoabandon'."
        ]
    )]
    protected string $overdue_handling = self::AUTOBANDON;

    #[Column(
        name: 'grace_period',
        type: Types::BIGINT,
        length: 20,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => "Grace period time when overdue handling use graceperiod method"
        ]
    )]
    protected int $grace_period = 0;

    #[Column(
        name: 'review_attempts',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => "Whether users are allowed to review their quiz attempts at various times. A bit field"
        ]
    )]
    protected int $review_attempts = 0;

    #[Column(
        name: 'review_correctness',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => "Whether users are allowed to review their quiz correctness at various times.'
            .' A bit field, like review_attempts."
        ]
    )]
    protected int $review_correctness = 0;

    #[Column(
        name: 'review_marks',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'default' => 0,
            'comment' => "Whether users are allowed to review their quiz marks at various times.'
            .' A bit field, like review_attempts."
        ]
    )]
    protected int $review_marks = 0;

    #[Column(
        name: 'preview_right_answers',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => "Whether users are allowed to preview the quiz answers when it's done."
        ]
    )]
    protected bool $preview_right_answers = false;

    #[Column(
        name: 'reveal_right_answers',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => "Whether users are allowed to preview the quiz answers on each question after answer."
        ]
    )]
    protected bool $reveal_right_answers = false;

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
        type: Types::DATETIME_MUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Quiz created time'
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
            'comment' => 'Quiz update time'
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
            'comment' => 'Quiz delete time'
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
                'relation_name' => 'relation_quiz_user_id_admins_id',
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

    public function getId() : int
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function isPasswordProtected(): bool
    {
        return $this->password_protected;
    }

    public function setPasswordProtected(bool $password_protected): void
    {
        $this->password_protected = $password_protected;
    }

    public function getTimeOpen(): int
    {
        return $this->time_open;
    }

    public function setTimeOpen(int $time_open): void
    {
        $this->time_open = $time_open;
    }

    public function getTimeClose(): int
    {
        return $this->time_close;
    }

    public function setTimeClose(int $time_close): void
    {
        $this->time_close = $time_close;
    }

    public function getTimeLimit(): int
    {
        return $this->time_limit;
    }

    public function setTimeLimit(int $time_limit): void
    {
        $this->time_limit = $time_limit;
    }

    public function getMaxAttempts(): int
    {
        return $this->max_attempts;
    }

    public function setMaxAttempts(int $max_attempts): void
    {
        $this->max_attempts = $max_attempts;
    }

    public function isAttemptPrevious(): bool
    {
        return $this->attempt_previous;
    }

    public function setAttemptPrevious(bool $attempt_previous): void
    {
        $this->attempt_previous = $attempt_previous;
    }

    public function isAllowSkip(): bool
    {
        return $this->allow_skip;
    }

    public function setAllowSkip(bool $allow_skip): void
    {
        $this->allow_skip = $allow_skip;
    }

    public function isAttemptUnanswered(): bool
    {
        return $this->attempt_unanswered;
    }

    public function setAttemptUnanswered(bool $attempt_unanswered): void
    {
        $this->attempt_unanswered = $attempt_unanswered;
    }

    public function getRedoQuestions(): int
    {
        return $this->redo_questions;
    }

    public function setRedoQuestions(int $redo_questions): void
    {
        $this->redo_questions = $redo_questions;
    }

    public function isShuffleAnswers(): bool
    {
        return $this->shuffle_answers;
    }

    public function setShuffleAnswers(bool $shuffle_answers): void
    {
        $this->shuffle_answers = $shuffle_answers;
    }

    public function isShuffleQuestions(): bool
    {
        return $this->shuffle_questions;
    }

    public function setShuffleQuestions(bool $shuffle_questions): void
    {
        $this->shuffle_questions = $shuffle_questions;
    }

    public function getDecimalPoints(): int
    {
        return $this->decimal_points;
    }

    public function setDecimalPoints(int $decimal_points): void
    {
        $this->decimal_points = $decimal_points;
    }

    public function getQuestionDecimalPoints(): int
    {
        return $this->question_decimal_points;
    }

    public function setQuestionDecimalPoints(int $question_decimal_points): void
    {
        $this->question_decimal_points = $question_decimal_points;
    }

    public function getQuestionPerPage(): int
    {
        return $this->question_per_page;
    }

    public function setQuestionPerPage(int $question_per_page): void
    {
        $this->question_per_page = $question_per_page;
    }

    public function getDecimalGrade(): int
    {
        return $this->decimal_grade;
    }

    public function setDecimalGrade(int $decimal_grade): void
    {
        $this->decimal_grade = $decimal_grade;
    }

    public function getMaximumGrade(): float
    {
        return $this->maximum_grade;
    }

    public function setMaximumGrade(float $maximum_grade): void
    {
        $this->maximum_grade = $maximum_grade;
    }

    public function getSumGrades(): float
    {
        return $this->sum_grades;
    }

    public function setSumGrades(float $sum_grades): void
    {
        $this->sum_grades = $sum_grades;
    }

    public function getOverdueHandling(): string
    {
        return $this->overdue_handling;
    }

    public function setOverdueHandling(string $overdue_handling): void
    {
        $this->overdue_handling = $overdue_handling;
    }

    /*! OVERDUE */
    public function getFilteredOverdueHandling() : string
    {
        $overdue_handling = strtolower(trim($this->getOverdueHandling()));
        switch ($overdue_handling) {
            case self::AUTOSUBMIT:
            case self::GRACEPERIOD:
            case self::AUTOBANDON:
                return $overdue_handling;
            default:
                $handling = $this->getManager()?->dispatch(
                    'entity.quizOverdueHandling',
                    self::AUTOBANDON,
                    $overdue_handling,
                    $this
                );
                return is_string($handling)
                    && trim($handling)
                    ? trim($handling)
                    :self::AUTOBANDON;
        }
    }

    public function getGracePeriod(): int
    {
        return $this->grace_period;
    }

    public function setGracePeriod(int $grace_period): void
    {
        $this->grace_period = $grace_period;
    }

    public function getReviewAttempts(): int
    {
        return $this->review_attempts;
    }

    public function setReviewAttempts(int $review_attempts): void
    {
        $this->review_attempts = $review_attempts;
    }

    public function getReviewCorrectness(): int
    {
        return $this->review_correctness;
    }

    public function setReviewCorrectness(int $review_correctness): void
    {
        $this->review_correctness = $review_correctness;
    }

    public function getReviewMarks(): int
    {
        return $this->review_marks;
    }

    public function setReviewMarks(int $review_marks): void
    {
        $this->review_marks = $review_marks;
    }

    public function getPreviewRightAnswers(): bool
    {
        return $this->preview_right_answers;
    }

    public function setPreviewRightAnswers(bool $preview_right_answers): void
    {
        $this->preview_right_answers = $preview_right_answers;
    }

    public function isRevealRightAnswers(): bool
    {
        return $this->reveal_right_answers;
    }

    public function setRevealRightAnswers(bool $reveal_right_answers): void
    {
        $this->reveal_right_answers = $reveal_right_answers;
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

    public function setDeletedAt(?DateTimeInterface $deletedAt) : void
    {
        $this->deleted_at = $deletedAt;
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
    public function passwordCheck(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        $this->passwordBasedIdUpdatedAt($event);
    }
}
