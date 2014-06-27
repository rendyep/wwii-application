<?php

namespace WWII\Application\Erp\ItInventory;

class ReportKomputerAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

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
                ->select('komputer')
                ->from('WWII\Domain\Erp\ItInventory\Komputer', 'komputer')
                ->where('UPPER(komputer.mac) LIKE :mac')
                ->andWhere('UPPER(komputer.nomorSeri) LIKE :nomorSeri')
                ->andWhere('UPPER(komputer.os) LIKE :os')
                ->andWhere('UPPER(komputer.lokasi) LIKE :lokasi')
                ->setParameter('mac', '%' . strtoupper(trim($params['mac'])) . '%')
                ->setParameter('nomorSeri', '%' . strtoupper(trim($params['nomorSeri'])) . '%')
                ->setParameter('os', '%' . strtoupper(trim($params['sistemOperasi'])) . '%')
                ->setParameter('lokasi', '%' . strtoupper(trim($params['lokasi'])) . '%');

            if (!empty($params['tahun'])) {
                $query = $query->andWhere('komputer.tahun = :tahun')
                    ->setParameter('tahun', $tahun);
            }

            $komputer = $query->getQuery()->getResult();

            $this->data = $komputer;
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
        include('view/report_komputer.phtml');
        $this->templateManager->renderFooter();
    }
}
