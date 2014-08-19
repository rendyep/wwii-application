<?php

namespace WWII\Application\Hrd\Cuti;

class AutocompleteKaryawanAction
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
        $this->karyawanHelper = new \WWII\Common\Helper\Collection\MsSQL\Karyawan(
            $this->serviceManager,
            $this->entityManager
        );
    }

    public function dispatch($params)
    {
        $karyawanList = $this->karyawanHelper->find($params['key']);

        echo json_encode($karyawanList);
    }
}
