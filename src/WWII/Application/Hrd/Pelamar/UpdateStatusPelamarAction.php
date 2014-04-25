<?php

namespace WWII\Application\Hrd\Pelamar;

class UpdateStatusPelamarAction
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
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\ActiveDepartment($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'PROSES':
                $this->dispatchProses($params);
                break;
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_pelamar'));
                break;
        }

        $pelamar = $this->getRequestedModelByNamaLengkap($params['namaLengkap']);
        if ($pelamar != null) {
            $params = $this->populateData($pelamar, $params);
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
            $pelamar = $this->getRequestedModelByNamaLengkap($params['namaLengkap']);
            $detailPelamar = $pelamar->getDetailPelamar();

            $detailPelamar->setPosisi($params['posisi']);
            $detailPelamar->setStatus($params['status']);

            switch ($params['status']) {
                case 'interview':
                    $arrayTanggalInterview = explode('/', $params['tanggalInterview']);
                    $tanggalInterview = new \DateTime($arrayTanggalInterview[2]
                        . '-' . $arrayTanggalInterview[1]
                        . '-' . $arrayTanggalInterview[0]);
                    $detailPelamar->setTanggalInterview($tanggalInterview);
                    break;
                case 'diterima':
                    $arrayTanggalRencanaMasukKerja = explode('/', $params['tanggalRencanaMasukKerja']);
                    $tanggalRencanaMasukKerja = new \DateTime($arrayTanggalRencanaMasukKerja[2]
                        . '-' . $arrayTanggalRencanaMasukKerja[1]
                            . '-' . $arrayTanggalRencanaMasukKerja[0]);
                    $detailPelamar->setTanggalRencanaMasukKerja($tanggalRencanaMasukKerja);
                    break;
                case 'ditolak';
                    break;
            }

            $this->entityManager->persist($pelamar);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (strtoupper($params['btx']) == 'PROSES') {
            if (empty($params['namaLengkap'])) {
                $errorMessages['namaLengkap'] = 'harus diisi';
            } else {
                $model = $this->getRequestedModelByNamaLengkap($params['namaLengkap']);

                if ($model == null) {
                    $errorMessages['namaLengkap'] = 'tidak ditemukan di data pelamar';
                } elseif ($model->getDetailPelamar()->getStatus() == 'diterima') {
                    $modelKaryawan = $this->entityManager
                        ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
                        ->findOneByPelamar($model->getId());

                    if ($modelKaryawan !== null) {
                        $errorMessages['namaLengkap'] = 'karyawan ini sudah diterima dan tercatat di database karyawan';
                    }
                }
            }
        } elseif (strtoupper($params['btx']) == 'SIMPAN') {
            if ($params['posisi'] == '') {
                $errorMessages['posisi'] = 'harus dipilih';
            }

            switch ($params['status']) {
                case 'interview':
                    if ($params['tanggalInterview'] == '') {
                        $errorMessages['tanggalInterview'] = 'harus diisi';
                    } else {
                        $arrayTanggalInterview = explode('/', $params['tanggalInterview']);
                        try {
                            $tanggalInterview = new \DateTime($arrayTanggalInterview[2]
                                . '-' . $arrayTanggalInterview[1]
                                . '-' . $arrayTanggalInterview[0]);
                        } catch(\Exception $e) {
                            $errorMessages['tanggalInterview'] = 'format tidak valid (ex. 17/03/2014).';
                        }
                    }
                    break;
                case 'diterima':
                    if ($params['tanggalRencanaMasukKerja'] == '') {
                        $errorMessages['tanggalRencanaMasukKerja'] = 'harus diisi';
                    } else {
                        $arrayTanggalRencanaMasukKerja = explode('/', $params['tanggalRencanaMasukKerja']);
                        try {
                            $tanggalRencanaMasukKerja = new \DateTime($arrayTanggalRencanaMasukKerja[2]
                                . '-' . $arrayTanggalRencanaMasukKerja[1]
                                . '-' . $arrayTanggalRencanaMasukKerja[0]);
                        } catch(\Exception $e) {
                            $errorMessages['tanggalRencanaMasukkerja'] = 'format tidak valid (ex. 17/03/2014).';
                        }
                    }
                    break;
                case 'ditolak':
                    break;
                default:
                    $errorMessages['status'] = 'harus dipilih';
                    break;
            }
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar, $params)
    {
        $params = array_merge(
            array(
                'namaLengkap' => $pelamar->getNamaLengkap(),
                'posisi' => $pelamar->getDetailPelamar()->getPosisi(),
                'status' => $pelamar->getDetailPelamar()->getStatus(),
                'tanggalInterview' => call_user_func(function($pelamar) {
                    if ($pelamar->getDetailPelamar()->getTanggalInterview() !== null) {
                        return $pelamar->getDetailPelamar()->getTanggalInterview()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $pelamar),
                'tanggalRencanaMasukKerja' => call_user_func(function($pelamar) {
                    if ($pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja() !== null) {
                        return $pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $pelamar),
            ),
            $params
        );

        return $params;
    }

    protected function getRequestedModelByNamaLengkap($namaLengkap)
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Pelamar\Pelamar')
            ->findOneByNamaLengkap($namaLengkap);

        return $model;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/update_status_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
