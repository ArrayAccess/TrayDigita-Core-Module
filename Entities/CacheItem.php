<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Cache items'
    ]
)]
#[HasLifecycleCallbacks]
class CacheItem extends AbstractEntity
{
    const TABLE_NAME = 'cache_items';

    #[Id]
    #[Column(
        name: 'item_id',
        type: Types::BINARY,
        length: 255,
        options: [
            'comment' => 'Item id'
        ]
    )]
    protected int $item_id;

    #[Column(
        name: 'item_data',
        type: Types::BLOB,
        length: AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB,
        options: [
            'comment' => 'Cache item data'
        ]
    )]
    protected int $item_data;

    #[Column(
        name: 'item_lifetime',
        type: Types::INTEGER,
        length: 10,
        nullable: true,
        options: [
            'unsigned' => true,
            'comment' => 'Cache item lifetime'
        ]
    )]
    protected int $item_lifetime;

    #[Column(
        name: 'item_time',
        type: Types::INTEGER,
        length: 10,
        options: [
            'unsigned' => true,
            'comment' => 'Cache item timestamp'
        ]
    )]
    protected int $item_time;

    public function getItemId(): int
    {
        return $this->item_id;
    }

    public function setItemId(int $item_id): void
    {
        $this->item_id = $item_id;
    }

    public function getItemData(): int
    {
        return $this->item_data;
    }

    public function setItemData(int $item_data): void
    {
        $this->item_data = $item_data;
    }

    public function getItemLifetime(): int
    {
        return $this->item_lifetime;
    }

    public function setItemLifetime(int $item_lifetime): void
    {
        $this->item_lifetime = $item_lifetime;
    }

    public function getItemTime(): int
    {
        return $this->item_time;
    }

    public function setItemTime(int $item_time): void
    {
        $this->item_time = $item_time;
    }
}
