<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users\Factory;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Capability;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\CapabilityEntityFactoryInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\CapabilityEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class CapabilityFactory implements CapabilityEntityFactoryInterface
{
    // private ?int $countCapability = null;

    public function createEntity(
        EntityManagerInterface $entityManager,
        string $identity
    ): ?CapabilityEntityInterface {
        return $entityManager->getRepository(
            Capability::class
        )->find($identity);
    }

    public function all(
        EntityManagerInterface $entityManager
    ) : iterable {
        return $entityManager->getRepository(
            Capability::class
        )->findAll();
    }

    public function getCapabilityIdentities(EntityManagerInterface $entityManager) : array
    {
        return (array) $entityManager
            ->getRepository(Capability::class)
            ->createQueryBuilder('u')
            ->select('u.identity')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
