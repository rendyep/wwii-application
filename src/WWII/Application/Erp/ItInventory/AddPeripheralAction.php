<?php

namespace WWII\Application\Erp\ItInventory;

class AddPeripheralAction
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
            $this->dispatchSimpan($params);
        }

        $this->render($params);
    }

    public function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $peripheral = new \WWII\Domain\Erp\ItInventory\Peripheral();
            $peripheral->setNama($params['nama']);
            $peripheral->setNomorSeri($params['nomorSeri']);
            $peripheral->setBrand($params['brand']);
            $peripheral->setType($params['type']);
            $peripheral->setPort($params['port']);
            $peripheral->setUserAccount($params['userAccount']);
            $peripheral->setLokasi($params['lokasi']);

            $this->entityManager->persist($peripheral);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_peripheral'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['nama'])) {
            $errorMessages[] = 'Nama harus diisi';
        }

        if (empty($params['nomorSeri'])) {
            $errorMessages[] = 'Nomor Seri harus diisi';
        }

        if (empty($params['brand'])) {
            $errorMessages[] = 'Brand harus diisi';
        }

        if (empty($params['type'])) {
            $errorMessages[] = 'Type harus diisi';
        }

        if (empty($params['port'])) {
            $errorMessages[] = 'Port harus diisi';
        }

        if (empty($params['lokasi'])) {
            $errorMessages[] = 'Lokasi harus diisi';
        }

        return $errorMessages;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/add_peripheral.phtml');
        $this->templateManager->renderFooter();
    }
}
