<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Option as OptionEntity;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use function array_values;

final class Option extends CoreSubmoduleAbstract
{
    // HIGH PRIORITY
    protected int $priority = -9999999999;

    //private ?Collection $collection = null;
    protected string $name = 'Site Option';

    public function getName(): string
    {
        return $this->translateContext(
            'Site Option',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application support option setting',
            'module',
            'core-module'
        );
    }

    /**
     * @return ObjectRepository<OptionEntity>&Selectable<OptionEntity>
     */
    public function getRepository() : ObjectRepository&Selectable
    {
        return ContainerHelper::service(
            Connection::class,
            $this->getContainer()
        )->getRepository(OptionEntity::class);
    }

    public function createNewOptionEntityObject(
        ?string $name = null,
        mixed $value = null
    ): OptionEntity {
        $option = new OptionEntity();
        $option->setEntityManager(
            ContainerHelper::service(
                Connection::class,
                $this->getContainer()
            )->getEntityManager()
        );
        if ($name !== null) {
            $option->setName($name);
        }
        $option->setValue($value);
        return $option;
    }

    public function getBatch(string ...$names): array
    {
        return $this
            ->getRepository()
            ->findBy(
                [
                    'name' => Expression::in('name', array_values($names))
                ]
            );
    }

    public function saveBatch(OptionEntity ...$option): void
    {
        $em = null;
        foreach ($option as $opt) {
            $em ??= $opt->getEntityManager()??ContainerHelper::service(
                Connection::class,
                $this->getContainer()
            )->getEntityManager();
            $em->persist($opt);
        }
        $em->flush();
    }

    public function get(string $name) : ?OptionEntity
    {
        return $this
            ->getRepository()
            ->find($name);
    }

    public function set(string $name, mixed $value, bool $autoload = false): OptionEntity
    {
        $entity = $this->get($name)??$this->createNewOptionEntityObject($name);
        $entity->setName($name);
        $entity->setValue($value);
        $entity->setAutoload($autoload);
        $em = $entity->getEntityManager()??ContainerHelper::service(
            Connection::class,
            $this->getContainer()
        )->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }
}
