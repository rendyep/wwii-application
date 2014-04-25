<?php

namespace WWII\Application\Erp\ItInventory;

class ReportPeripheralAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $sessionContainer;

    protected $entityManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        if (!empty($params)) {
            $this->dispatchFilter($params);
        }

        $this->render($params);
    }

    public function dispatchFilter($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $query = $this->entityManager->createQueryBuilder()
                ->select('peripheral')
                ->from('WWII\Domain\Erp\ItInventory\Peripheral', 'peripheral')
                ->where('UPPER(peripheral.nama) LIKE :nama')
                ->andWhere('UPPER(peripheral.brand) LIKE :brand')
                ->andWhere('UPPER(peripheral.type) LIKE :type')
                ->andWHere('UPPER(peripheral.lokasi) LIKE :lokasi')
                ->setParameter('nama', '%' . strtoupper(trim($params['nama'])) . '%')
                ->setParameter('brand', '%' . strtoupper(trim($params['brand'])) . '%')
                ->setParameter('type', '%' . strtoupper(trim($params['type'])) . '%')
                ->setParameter('lokasi', '%' . strtoupper(trim($params['lokasi'])) . '%');

            $peripheral = $query->getQuery()->getResult();

            $this->data = $peripheral;
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        return $errorMessages;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/report_peripheral.phtml');
        $this->templateManager->renderFooter();
    }
}
