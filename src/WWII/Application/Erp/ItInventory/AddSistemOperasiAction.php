<?php

namespace WWII\Application\Erp\ItInventory;

class AddSistemOperasiAction
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
            $sistemOperasi = new \WWII\Domain\Erp\ItInventory\SistemOperasi();
            $sistemOperasi->setNama($params['nama']);
            $sistemOperasi->setVersi($params['versi']);
            $sistemOperasi->setSerial($params['serial']);
            $sistemOperasi->setLisensi($params['lisensi']);

            $this->entityManager->persist($sistemOperasi);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_sistem_operasi'));
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

        if (empty($params['serial'])) {
            $errorMessages[] = 'Serial harus diisi';
        }

        if (empty($params['versi'])) {
            $errorMessages[] = 'Versi harus diisi';
        }

        if (empty($params['lisensi'])) {
            $errorMessages[] = 'Lisensi harus diisi';
        }

        return $errorMessages;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/add_sistem_operasi.phtml');
        $this->templateManager->renderFooter();
    }
}
