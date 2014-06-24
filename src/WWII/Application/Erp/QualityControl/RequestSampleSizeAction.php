<?php

namespace WWII\Application\Erp\QualityControl;

class RequestSampleSizeAction
{
    protected $serviceManager;

    protected $entityManager;

    public function __construct(
    \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        $lotSize         = $params['jumlahLot'];
        $inspectionLevel = $params['inspectionLevel'];
        $acceptanceIndex = $params['acceptanceIndex'];

        $lotRange = $this->entityManager->createQueryBuilder()
            ->select('lotRange')
            ->from('WWII\Domain\Erp\QualityControl\GeneralInspection\LotRange', 'lotRange')
            ->leftJoin('lotRange.level', 'level')
            ->where('level.code = :level')
                ->setParameter('level', $inspectionLevel)
            ->andWhere('lotRange.minLot <= :minLot')
                ->setParameter('minLot', $lotSize)
            ->andWhere('lotRange.maxLot >= :maxLot')
                ->setParameter('maxLot', $lotSize)
            ->orderBy('lotRange.minLot', 'asc')
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $category = $lotRange->getCategory();

        $acceptanceLimit = $this->entityManager->createQueryBuilder()
            ->select('acceptanceLimit')
            ->from('WWII\Domain\Erp\QualityControl\GeneralInspection\AcceptanceLimit', 'acceptanceLimit')
            ->leftJoin('acceptanceLimit.acceptanceIndex', 'acceptanceIndex')
            ->where('acceptanceLimit.category = :category')
                ->setParameter('category', $category)
            ->andWhere('acceptanceIndex.code = :acceptanceIndex')
                ->setParameter('acceptanceIndex', $acceptanceIndex)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        echo $acceptanceLimit->getSampleSize();
    }
}
