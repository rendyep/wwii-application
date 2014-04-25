<?php

namespace WWII\Application\Hrd\Pelamar;

class DeletePelamarAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $templateManager;

    protected $databaseManager;

    protected $entityManager;

    protected $sessionContainer;

    protected $flashMessenger;

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
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_pelamar'));
                break;
            case 'HAPUS':
                $this->dispatchDelete($params);
                break;
            default:
                break;
        }

        $pelamar = $this->getRequestedModel();
        $params = $this->populateData($pelamar, $params);

        $this->render($params);
    }

    protected function dispatchDelete($params)
    {
        $pelamar = $this->getRequestedModel();

        if (!$this->isRemovable($pelamar)) {
            $this->flashMessenger->addMessage('Data pelamar tidak bisa dihapus.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        $this->deleteFilePelamar($pelamar);
        $this->deletePhotoPelamar($pelamar);

        $this->entityManager->remove($pelamar);
        $this->entityManager->flush();

        $this->flashMessenger->addMessage('Data berhasil dihapus.');
        $this->routeManager->redirect(array('action' => 'report_pelamar'));
    }

    protected function deleteFilePelamar(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/files/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        foreach ($pelamar->getFilePelamar() as $filePelamar) {
            if (file_exists($filePath . $filePelamar->getNamaFile())) {
                unlink($filePath . $filePelamar->getNamaFile());
            }
        }
    }

    protected function deletePhotoPelamar(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/images/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        if (file_exists($filePath . $pelamar->getPhoto())) {
            unlink($filePath . $pelamar->getPhoto());
        }
    }

    protected function populateData(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar, $params)
    {
        $params = array(
            'namaLengkap' => $pelamar->getNamaLengkap(),
            'alamat' => $pelamar->getAlamat(),
            'kota' => $pelamar->getKota(),
            'pendidikan' => $pelamar->getPendidikan(),
            'jurusan' => $pelamar->getJurusan(),
            'posisi' => $pelamar->getDetailPelamar()->getPosisi(),
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Pelamar\Pelamar')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        return $model;
    }

    protected function isRemovable(\WWII\DOmain\Hrd\Pelamar\Pelamar $model)
    {
        $modelDetail = $model->getDetailPelamar();
        if ($modelDetail->getStatus() == 'diterima') {
            $modelKaryawan = $this->entityManager
                ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
                ->findOneByPelamar($model->getId());

            if ($modelKaryawan == null) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/delete_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
