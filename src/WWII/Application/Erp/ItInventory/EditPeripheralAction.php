<?php

namespace WWII\Application\Erp\ItInventory;

class EditPeripheralAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $sessionContainer;

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
        if (!isset($_GET['key'])) {
            $this->routeManager->redirect(array('action' => 'report_peripheral'));
        }

        $peripheral = $this->entityManager
            ->getRepository('WWII\Domain\Erp\ItInventory\Peripheral')
            ->findOneById($_GET['key']);

        if ($peripheral == null) {
            $this->flashMessenger->addMessage('Data yang anda cari tidak ditemukan.');
            $this->routeManager->redirect(array('action' => 'report_peripheral'));
        }

        if (!empty($params)) {
            $this->dispatchSimpan($params);
        }

        $params = $this->populateData();

        $this->render($params);
    }

    public function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $peripheral = $this->entityManager
                ->getRepository('WWII\Domain\Erp\ItInventory\Peripheral')
                ->findOneById($_GET['key']);

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

    protected function populateData()
    {
        $peripheral = $this->entityManager
            ->getRepository('WWII\Domain\Erp\ItInventory\Peripheral')
            ->findOneById($_GET['key']);

        $params = array(
            'nama' => $peripheral->getNama(),
            'nomorSeri' => $peripheral->getNomorSeri(),
            'brand' => $peripheral->getBrand(),
            'type' => $peripheral->getType(),
            'port' => $peripheral->getPort(),
            'userAccount' => $peripheral->getUserAccount(),
            'lokasi' => $peripheral->getLokasi()
        );

        return $params;
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

        if (empty($params['lokasi'])) {
            $errorMessages[] = 'Lokasi harus diisi';
        }

        return $errorMessages;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/edit_peripheral.phtml');
        $this->templateManager->renderFooter();
    }
}
