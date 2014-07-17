<?php

namespace WWII\Application\Erp\ItInventory;

class ScanBarcodeKomputerAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $data;

    protected $errorMessages = array();

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        if (empty($params['key'])) {
            $this->dispatchView($params);
        } else {
            $this->dispatchData($params);
        }
    }

    protected function dispatchData($params)
    {
        $komputer = $this->entityManager->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
            ->findOneByNomorSeri($this->routeManager->getKey());

        if ($komputer == null) {
            return;
        }

        $data = array(
            'nomorSeri'     => $komputer->getNomorSeri(),
            'ipAddress'     => $komputer->getIp(),
            'macAddress'    => $komputer->getMac(),
            'cpu'           => $komputer->getCpu(),
            'hardDisk'      => $komputer->getHardDisk(),
            'ram'           => $komputer->getRam(),
            'lcd'           => $komputer->getLcd(),
            'opticalDrive'  => $komputer->getOpticalDrive(),
            'sistemOperasi' => $komputer->getOs(),
            'email'         => $komputer->getEmail(),
            'tahun'         => $komputer->getTahun(),
            'user'          => $komputer->getUser(),
            'userAccount'   => $komputer->getUserAccount(),
            'lokasi'        => $komputer->getLokasi()
        );

        echo json_encode($data);
    }

    protected function dispatchView($params)
    {
        $this->templateManager->renderHeader();
        include('view/scan_barcode_komputer.phtml');
        $this->templateManager->renderFooter();
    }
}
