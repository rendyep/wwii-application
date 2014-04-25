<?php

namespace WWII\Application\Erp\ItInventory;

class DeleteSistemOperasiAction
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
        if (!empty($params['key'])) {
            $this->dispatchDelete($params);
        } else {
            $this->routeManager->redirect(array('action' => 'report_sistem_operasi'));
        }

        $this->render($params);
    }

    protected function dispatchDelete($params)
    {
        $errorMessages = $this->validateData($params);

        if ($_POST['btx'] == 'Cancel') {
            $this->routeManager->redirect(array('action' => 'report_sistem_operasi'));
        }

        if (!empty($errorMessages)) {
            $this->flashMessenger->addMessage('Data yang anda cari tidak ditemukan.');
            $this->routeManager->redirect(array('action' => 'report_sistem_operasi'));
        }

        $sistemOperasi = $this->entityManager
            ->getRepository('WWII\Domain\Erp\ItInventory\SistemOperasi')
            ->findOneById($params['key']);

        if ($_POST['btx'] == 'Delete') {
            $this->entityManager->remove($sistemOperasi);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil dihapus.');
            $this->routeManager->redirect(array('action' => 'report_sistem_operasi'));
        } else {
            $this->data = $sistemOperasi;
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        return $errorMessages;
    }

    protected function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/delete_sistem_operasi.phtml');
        $this->templateManager->renderFooter();
    }
}
