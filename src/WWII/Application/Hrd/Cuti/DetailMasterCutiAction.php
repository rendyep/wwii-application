<?php

namespace WWII\Application\Hrd\Cuti;

class DetailMasterCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'master_cuti'));
                break;
        }

        $masterCuti = $this->getRequestedModel();
        $params = $this->populateData($masterCuti, $params);

        $this->render($params);
    }

    protected function populateData(\WWII\Domain\Hrd\Cuti\MasterCuti $masterCuti, $params)
    {
        $params = array(
            'nik' => $masterCuti->getNik(),
            'namaKaryawan' => $masterCuti->getNamaKaryawan(),
            'departemen' => $masterCuti->getDepartemen(),
            'tanggalKadaluarsaAktif' => call_user_func(function($masterCuti) {
                if (!$masterCuti->isExpired()) {
                    return $masterCuti->getTanggalKadaluarsa()->format('d/m/Y');
                } elseif($masterCuti->getPerpanjanganCuti() !== null && !$masterCuti->getPerpanjanganCuti()->isExpired()) {
                    return $masterCuti->getPerpanjanganCuti()->getTanggalKadaluarsa()->format('d/m/Y');
                } else {
                    return '-';
                }
            }, $masterCuti),
            'sisaCutiAktif' => $masterCuti->getSisaLimit(),
            'tanggalKadaluarsaPeriodeSebelumnya' => call_user_func(function($masterCuti) {
                if ($masterCuti->getParent() !== null
                    && $masterCuti->getParent()->getPerpanjanganCuti() !== null
                    && !$masterCuti->getParent()->getPerpanjanganCuti()->isExpired()) {
                    return $masterCuti->getParent()->getPerpanjanganCuti()->getTanggalKadaluarsa()->format('d/m/Y');
                } else {
                    return '-';
                }
            }, $masterCuti),
            'sisaCutiPeriodeSebelumnya' => call_user_func(function($masterCuti) {
                if ($masterCuti->getParent() !== null
                    && $masterCuti->getParent()->getPerpanjanganCuti() !== null
                    && !$masterCuti->getParent()->getPerpanjanganCuti()->isExpired()) {
                    return $masterCuti->getParent()->getSisaLimit();
                } else {
                    return '-';
                }
            }, $masterCuti),
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Cuti\MasterCuti')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'master_cuti'));
        }

        return $model;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/detail_master_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
