<?php

namespace WWII\Application\Erp\Finding;

class DeleteFindingAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $flashMessenger;

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
        switch (strtoupper($params['btx'])) {
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_finding'));
            case 'HAPUS':
                $this->dispatchDelete($params);
                break;
            default:
                break;
        }

        $finding = $this->getRequestedModel();
        $params = $this->populateData($finding, $params);

        $this->render($params);
    }

    protected function dispatchDelete($params)
    {
        $finding = $this->getRequestedModel();

        if (!$this->isRemovable($finding)) {
            $this->flashMessenger->addMessage('Data pelamar tidak bisa dihapus.');
            $this->routeManager->redirect(array('report_finding'));
        }
        $fileNames = array();
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
                . '/images'
                . '/' . $this->routeManager->getModule()
                . '/' . $this->routeManager->getController();

        foreach ($finding->getFindingPhotos() as $photo) {
            $fileNames[] = $filePath . '/' . $photo->getNamaFile();
        }

        $this->entityManager->remove($finding);
        $this->entityManager->flush();

        foreach ($fileNames as $fileName) {
            unlink($fileName);
        }

        $this->flashMessenger->addMessage('Data berhasil dihapus.');
        $this->routeManager->redirect(array('action' => 'report_finding'));
    }

    protected function populateData(\WWII\Domain\Erp\Finding\Finding $finding, $params)
    {
        $params = array(
            'tanggalKejadian' => call_user_func(function($finding) {
                if ($finding->getTanggal() !== null) {
                    return $finding->getTanggal()->format('d/m/Y');
                }
                return null;
            }, $finding),
            'kejadian' => $finding->getKejadian(),
            'tindakan' => $finding->getTindakan(),
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

    protected function isRemovable(\WWII\Domain\Erp\Finding\Finding $finding)
    {
        return true;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/delete_finding.phtml');
        $this->templateManager->renderFooter();
    }
}
