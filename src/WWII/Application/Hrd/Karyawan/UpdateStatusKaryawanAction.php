<?php

namespace WWII\Application\Hrd\Karyawan;

class UpdateStatusKaryawanAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'PROSES' :
                $this->dispatchProses($params);
                break;
            case 'SIMPAN' :
                $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_karyawan'));
                break;
        }

        $karyawan = $this->getRequestedModelByNik($params['nik']);
        if ($karyawan != null) {
            $params = $this->populateData($karyawan, $params);
        }

        $this->render($params);
    }

    protected function dispatchProses($params)
    {
        $errorMessages = $this->validateData($params);

        if (!empty($errorMessages)) {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $karyawan = $this->getRequestedModelByNik($params['nik']);
            $detailKaryawan = $karyawan->getDetailKaryawan();

            $detailKaryawan->setJabatan($params['jabatan']);
            $detailKaryawan->setDepartemen($params['departemen']);
            $detailKaryawan->setStatus($params['status']);

            switch ($params['status']) {
                case 'kontrak':
                    $arrayTanggalAwalKontrakKerja = explode('/', $params['tanggalAwalKontrakKerja']);
                    $tanggalAwalKontrakKerja = new \DateTime($arrayTanggalAwalKontrakKerja[2] . '-' . $arrayTanggalAwalKontrakKerja[1] . '-' . $arrayTanggalAwalKontrakKerja[0]);
                    $detailKaryawan->setTanggalAkhirKontrakKerja($tanggalAwalKontrakKerja);
                case 'tetap':
                    $arrayTanggalAkhirKontrakKerja = explode('/', $params['tanggalAkhirKontrakKerja']);
                    $tanggalAkhirKontrakKerja = new \DateTime($arrayTanggalAkhirKontrakKerja[2] . '-' . $arrayTanggalAkhirKontrakKerja[1] . '-' . $arrayTanggalAkhirKontrakKerja[0]);
                    $detailKaryawan->setTanggalAkhirKontrakKerja($tanggalAkhirKontrakKerja);

                    $arrayTanggalMasukKerja = explode('/', $params['tanggalMasukKerja']);
                    $tanggalMasukKerja = new \DateTime($arrayTanggalMasukKerja[2] . '-' . $arrayTanggalMasukKerja[1] . '-' . $arrayTanggalMasukKerja[0]);
                    $detailKaryawan->setTanggalMasukKerja($tanggalMasukKerja);
                    break;
                case 'keluar':
                    $arrayTanggalKeluarKerja = explode('/', $params['tanggalKeluarKerja']);
                    $tanggalKeluarKerja = new \DateTime($arrayTanggalKeluarKerja[2] . '-' . $arrayTanggalKeluarKerja[1] . '-' . $arrayTanggalKeluarKerja[0]);
                    $detailKaryawan->setTanggalKeluarKerja($tanggalKeluarKerja);
                    break;
            }

            $this->entityManager->persist($karyawan);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (strtoupper($params['btx']) == 'PROSES') {
            if (empty($params['nik'])) {
                $errorMessages['nik'] = 'harus diisi';
            } else {
                $model = $this->getRequestedModelByNik($params['nik']);

                if ($model == null) {
                    $errorMessages['nik'] = 'tidak ditemukan di data pelamar';
                } elseif ($model->getDetailKaryawan()->getStatus() == 'keluar') {
                    $errorMessages['nik'] = 'karyawan ini sudah keluar';
                }
            }
        }

        if (strtoupper($params['btx']) == 'SIMPAN') {
            if ($params['jabatan'] == '') {
                $errorMessages['jabatan'] = 'harus dipilih';
            }

            if ($params['departemen'] == '') {
                $errorMessages['departemen'] = 'harus dipilih';
            } else {
                $department = $this->departmentHelper->findOneByNama($params['departemen']);

                if ($department == null) {
                    $errorMessages['departemen'] = 'tidak valid';
                }
            }

            if ($params['status'] == '') {
                $errorMessages['status'] = 'harus dipilih';
            }

            switch ($params['status']) {
                case 'kontrak':
                    if ($params['tanggalAwalKontrakKerja'] == '') {
                        $errorMessages['tanggalAwalKontrakKerja'] = 'harus diisi';
                    } else {
                        $arrayTanggalAwalKontrakKerja = explode('/', $params['tanggalAwalKontrakKerja']);
                        try {
                            $tanggalAwalKontrakKerja = new \DateTime($arrayTanggalAwalKontrakKerja[2] . '-' . $arrayTanggalAwalKontrakKerja[1] . '-' . $arrayTanggalAwalKontrakKerja[0]);
                        } catch(\Exception $e) {
                            $errorMessages['tanggalAwalKontrakKerja'] = 'format tidak valid (ex. 17/03/2014).';
                        }
                    }
                case 'tetap':
                    if ($params['tanggalAkhirKontrakKerja'] == '') {
                        $errorMessages['tanggalAkhirKontrakKerja'] = 'harus diisi';
                    } else {
                        $arrayTanggalAkhirKontrakKerja = explode('/', $params['tanggalAkhirKontrakKerja']);
                        try {
                            $tanggalAkhirKontrakKerja = new \DateTime($arrayTanggalAkhirKontrakKerja[2] . '-' . $arrayTanggalAkhirKontrakKerja[1] . '-' . $arrayTanggalAkhirKontrakKerja[0]);
                        } catch(\Exception $e) {
                            $errorMessages['tanggalAkhirKontrakKerja'] = 'format tidak valid (ex. 17/03/2014).';
                        }
                    }
                    if ($params['tanggalMasukKerja'] == '') {
                        $errorMessages['tanggalMasukKerja'] = 'harus diisi';
                    } else {
                        $arrayTanggalMasukKerja = explode('/', $params['tanggalMasukKerja']);
                        try {
                            $tanggalMasukKerja = new \DateTime($arrayTanggalMasukKerja[2] . '-' . $arrayTanggalMasukKerja[1] . '-' . $arrayTanggalMasukKerja[0]);
                        } catch(\Exception $e) {
                            $errorMessages['tanggalMasukKerja'] = 'format tidak valid (ex. 17/03/2014).';
                        }
                    }
                    break;
                case 'keluar':
                    if ($params['tanggalKeluarKerja'] == '') {
                        if ($params['status'] == 'keluar') {
                            $errorMessages['tanggalKeluarKerja'] = 'harus diisi';
                        }
                    }
                    break;
            }
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Karyawan\Karyawan $karyawan, $params)
    {
        $params = array_merge(
            array(
                'nik' => $karyawan->getDetailKaryawan()->getNik(),
                'namaLengkap' => $karyawan->getNamaLengkap(),
                'jabatan' => $karyawan->getDetailKaryawan()->getJabatan(),
                'departemen' => $karyawan->getDetailKaryawan()->getDepartemen(),
                'status' => $karyawan->getDetailKaryawan()->getStatus(),
                'tanggalAwalKontrakKerja' => call_user_func(function($karyawan) {
                    if ($karyawan->getDetailKaryawan()->getTanggalAwalKontrakKerja() !== null) {
                        return $karyawan->getDetailKaryawan()->getTanggalAwalKontrakKerja()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $karyawan),
                'tanggalAkhirKontrakKerja' => call_user_func(function($karyawan) {
                    if ($karyawan->getDetailKaryawan()->getTanggalAkhirKontrakKerja() !== null) {
                        return $karyawan->getDetailKaryawan()->getTanggalAkhirKontrakKerja()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $karyawan),
                'tanggalMasukKerja' => call_user_func(function($karyawan) {
                    if ($karyawan->getDetailKaryawan()->getTanggalMasukKerja() !== null) {
                        return $karyawan->getDetailKaryawan()->getTanggalMasukKerja()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $karyawan),
                'tanggalKeluarKerja' => call_user_func(function($karyawan) {
                    if ($karyawan->getDetailKaryawan()->getTanggalKeluarKerja() !== null) {
                        return $karyawan->getDetailKaryawan()->getTanggalKeluarKerja()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $karyawan),
            ),
            $params
        );

        return $params;
    }

    protected function getRequestedModelByNik($nik)
    {
        $detailKaryawan = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Karyawan\DetailKaryawan')
            ->findOneByNik($nik);

        if ($detailKaryawan !== null) {
            return $detailKaryawan->getKaryawan();
        } else {
            return null;
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/update_status_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
