<?php

namespace WWII\Application\Hrd\Karyawan;

class ViewKaryawanAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $templateManager;

    protected $databaseManager;

    protected $entityManager;

    protected $sessionContainer;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'report_karyawan'));
                break;
        }

        $karyawan = $this->getRequestedModel();
        $params = $this->populateData($karyawan, $params);

        $this->render($params);
    }

    protected function populateData(\WWII\Domain\Hrd\Karyawan\Karyawan $karyawan, $params)
    {
        $params = array(
            'nik' => $karyawan->getDetailKaryawan()->getNik(),
            'namaLengkap' => $karyawan->getNamaLengkap(),
            'namaPanggilan' => $karyawan->getNamaPanggilan(),
            'jenisKelamin' => $karyawan->getJenisKelamin(),
            'tanggalLahir' => call_user_func(function($karyawan) {
                if ($karyawan->getTanggalLahir() !== null) {
                    return $karyawan->getTanggalLahir()->format('d/m/Y');
                }
                return '-';
            }, $karyawan),
            'agama' => $karyawan->getAgama(),
            'statusPerkawinan' => $karyawan->getStatusPerkawinan(),
            'pendidikan' => $karyawan->getPendidikan(),
            'jurusan' => $karyawan->getJurusan(),
            'alamat' => $karyawan->getAlamat(),
            'kota' => $karyawan->getKota(),
            'kodePos' => $karyawan->getKodePos(),
            'telepon' => $karyawan->getTelepon(),
            'ponsel' => $karyawan->getPonsel(),
            'ponselLain' => $karyawan->getPonselLain(),
            'email' => $karyawan->getEmail(),
            'npwp' => $karyawan->getNpwp(),
            'status' => $karyawan->getDetailKaryawan()->getStatus(),
            'jabatan' => $karyawan->getDetailKaryawan()->getJabatan(),
            'departemen' => $karyawan->getDetailKaryawan()->getDepartemen(),
            'tanggalAwalKontrakKerja' => call_user_func(function($karyawan) {
                if ($karyawan->getDetailKaryawan()->getTanggalAwalKontrakKerja() !== null) {
                    return $karyawan->getDetailKaryawan()->getTanggalAwalKontrakKerja()->format('d/m/Y');
                }
                return '-';
            }, $karyawan),
            'tanggalAkhirKontrakKerja' => call_user_func(function($karyawan) {
                if ($karyawan->getDetailKaryawan()->getTanggalAkhirKontrakKerja() !== null) {
                    return $karyawan->getDetailKaryawan()->getTanggalAkhirKontrakKerja()->format('d/m/Y');
                }
                return '-';
            }, $karyawan),
            'tanggalMasukKerja' => call_user_func(function($karyawan) {
                if ($karyawan->getDetailKaryawan()->getTanggalMasukKerja() !== null) {
                    return $karyawan->getDetailKaryawan()->getTanggalMasukKerja()->format('d/m/Y');
                }
                return '-';
            }, $karyawan),
            'tanggalKeluarKerja' => call_user_func(function($karyawan) {
                if ($karyawan->getDetailKaryawan()->getTanggalKeluarKerja() !== null) {
                    return $karyawan->getDetailKaryawan()->getTanggalKeluarKerja()->format('d/m/Y');
                }
                return '-';
            }, $karyawan),
            'photo' => $karyawan->getPhoto(),
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        return $model;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/view_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
