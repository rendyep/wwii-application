<?php

namespace WWII\Application\Hrd\Karyawan;

class DeleteKaryawanAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

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
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        if (!$this->isRemovable()) {
            $this->flashMessenger->addMessage('Data karyawan tidak bisa dihapus.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        switch (strtoupper($params['btx'])) {
            case 'BATAL' :
                $this->routeManager->redirect(array('action' => 'report_karyawan'));
            case 'HAPUS' :
                $this->dispatchDelete($params);
                break;
        }

        $this->data = $this->getRequestedModel();

        $this->render($params);
    }

    protected function dispatchDelete($params)
    {
        $karyawan = $this->getRequestedModel();

        if (!$this->isRemovable($karyawan)) {
            $this->flashMessenger->addMessage('Data karyawan tidak bisa dihapus.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        $this->deletePhotoKaryawan($karyawan);

        $this->entityManager->remove($karyawan);
        $this->entityManager->flush();

        $this->flashMessenger->addMessage('Data berhasil dihapus.');
        $this->routeManager->redirect(array('action' => 'report_karyawan'));
    }

    protected function deletePhotoKaryawan(\WWII\Domain\Hrd\Karyawan\Karyawan $karyawan)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/images/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        if (file_exists($filePath . $karyawan->getPhoto())) {
            unlink($filePath . $karyawan->getPhoto());
        }
    }

    protected function getRequestedModel()
    {
        $key = $this->routeManager->getKey();
        if (empty($key)) {
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            return $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        return $model;
    }

    protected function isRemovable(\WWII\Domain\Hrd\Karyawan\Karyawan $model)
    {
        $modelDetail = $model->getDetailKaryawan();
        if ($modelDetail->getStatus() == 'keluar') {
            return false;
        } else {
            return true;
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        include('view/delete_karyawan.phtml');
    }
}
