<?php

namespace WWII\Application\Erp\ItInventory;

class ReportSistemOperasiAction
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
            $result = $this->entityManager->createQueryBuilder()
                ->select("sistemOperasi")
                ->from('WWII\Domain\Erp\ItInventory\SistemOperasi', 'sistemOperasi')
                ->where('UPPER(sistemOperasi.nama) LIKE :nama')
                ->andWhere('UPPER(sistemOperasi.versi) LIKE :versi')
                ->andWhere('UPPER(sistemOperasi.serial) LIKE :serial')
                ->andWhere('UPPER(sistemOperasi.lisensi) LIKE :lisensi')
                ->setParameter('nama', '%' . strtoupper(trim($params['nama'])) . '%')
                ->setParameter('versi', '%' . strtoupper(trim($params['versi'])) . '%')
                ->setParameter('serial', '%' . strtoupper(trim($params['serial'])) . '%')
                ->setParameter('lisensi', '%' . strtoupper(trim($params['lisensi'])) . '%')
                ->getQuery()->getResult();

            $this->data = $result;
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
        include('view/report_sistem_operasi.phtml');
        $this->templateManager->renderFooter();
    }
}
