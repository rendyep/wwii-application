<?php

namespace WWII\Application\Erp\QualityControl;

class AutocompleteProdukAction
{
    protected $serviceManager;

    protected $entityManager;

    protected $productHelper;

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->entityManager = $entityManager;
        $this->productHelper = new \WWII\Common\Helper\Collection\MsSQL\Product(
            $this->serviceManager,
            $this->entityManager
        );
    }

    public function dispatch($params)
    {
        $productList = $this->productHelper->find($params['key']);

        echo json_encode($productList);
    }
}
