<?php

namespace WWII\Application\Erp\Finding;

class ViewFindingAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'report_finding'));
                break;
        }

        $finding = $this->getRequestedModel();
        $params = $this->populateData($finding, $params);

        $this->render($params);
    }

    protected function dispatchView($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $finding = $this->entityManager
                ->getRepository('WWII\Domain\Erp\Finding\Finding', 'finding')
                ->findOneById($this->routeManager->getKey());

            if (empty($finding)) {
                $this->flashMessenger->addMessage('Data yang anda cari tidak ditemukan.');
                $this->routeManager->redirect(array('action' => 'report_finding'));
            } else {
                $this->data = $finding;
            }
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function populateData(\WWII\Domain\Erp\Finding\Finding $finding, $params)
    {
        $params = array(
            'tanggalKejadian' => $finding->getTanggal()->format('d-M-Y'),
            'kejadian' => nl2br($finding->getKejadian()),
            'tindakan' => nl2br($finding->getTindakan()),
            'photos' => count($finding->getFindingPhotos()) == 0 ? array() : call_user_func(function($finding) {
                    $data = array();
                    foreach ($finding->getFindingPhotos() as $photo) {
                        $data[]['namaFile'] = $photo->getNamaFile();
                    }
                    return $data;
                }, $finding),
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $finding = $this->entityManager
            ->getRepository('WWII\Domain\Erp\Finding\Finding')
            ->findOneById($this->routeManager->getKey());

        if ($finding == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(null, null, 'report_finding');
        }

        return $finding;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/view_finding.phtml');
        $this->templateManager->renderFooter();
    }
}
