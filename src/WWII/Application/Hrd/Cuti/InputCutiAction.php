<?php

namespace WWII\Application\Hrd\Cuti;

class InputCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $departmentHelper;

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
            case 'PROSES':
                $this->dispatchProses($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_cuti'));
                break;
        }

        $masterCuti = $this->getRequestedMasterCuti($params['nik']);
        if ($masterCuti != null) {
            $params = $this->populateData($masterCuti, $params);
        }

        $this->render($params);
    }

    protected function dispatchProses($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $masterCuti = $this->getRequestedMasterCuti($params['nik']);

            $pengambilanCuti = new \WWII\Domain\Hrd\Cuti\PengambilanCuti();

            $pengambilanCuti->setKeterangan($params['keterangan']);

            $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
            $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
            $pengambilanCuti->setTanggalAwal($tanggalAwal);

            $pengambilanCuti->setMasterCuti($masterCuti);

            $this->entityManager->persist($pengambilanCuti);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        } else {
            return array(
                'departmentList' => $this->departmentHelper->getDepartmentList(),
                'errorMessages' => $errorMessages,
                'params' => $params,
            );
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (strtoupper($params['btx']) == 'PROSES') {
            $masterCuti = $this->getRequestedMasterCuti($params['nik']);

            if ($masterCuti == null) {
                $errorMessages['nik'] = 'tidak ditemukan';
            } else {
                if ($pelamar->getDetailPelamar()->getStatus() == 'diterima') {
                    $model = $this->entityManager
                        ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
                        ->findOneByPelamar($pelamar->getId());

                    if ($model != null) {
                        $errorMessages['namaLengkap'] = 'sudah tercatat di data karyawan';
                    }
                } else {
                    $errorMessages['namaLengkap'] = 'status pelamar sedang interview atau ditolak';
                }
            }
        }

        if (strtoupper($params['btx']) == 'SIMPAN') {
            $masterCuti = $this->getRequestedMasterCuti($params['nik']);

            if ($masterCuti === null) {
                $errorMessages['nik'] = 'karyawan belum memiliki hak cuti';
            } else {
                if ($masterCuti->getSisaLimit() == 0 || $masterCuti->isExpired()) {
                    $errorMessages['nik'] = 'karyawan tidak memiliki sisa cuti';
                }
            }

            if ($params['tanggalAwal'] == '') {
                $errorMessages['tanggal'] = 'tanggal harus diisi';
            } else {
                $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
                try {
                    $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
                } catch(\Exception $e) {
                    $this->errorMessages['tanggal'] = 'format tidak valid (ex. 17/03/2014)';
                }
            }

            if ($params['tanggalAkhir'] == '') {
                $errorMessages['tanggal'] = 'tanggal harus diisi';
            } else {
                $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
                try {
                    $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);
                } catch(\Exception $e) {
                    $this->errorMessages['tanggal'] = 'format tidak valid (ex. 17/03/2014)';
                }
            }

            if ($params['keterangan'] == '') {
                $errorMessages['keterangan'] = 'harus diisi';
            }
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Cuti\MasterCuti $masterCuti, $params)
    {
        $params = array_merge(
            array(
                'nik' => isset($params['nik']) ? $params['nik'] : '',
                'namaKaryawan' => isset($params['namaKaryawan']) ? $params['namaKaryawan'] : $masterCuti->getNamaKaryawan(),
                'departemen' => isset($params['departemen']) ? $params['departemen'] : $masterCuti->getDepartemen(),
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
            )
        );
    }

    protected function getRequestedMasterCuti($nik)
    {
        $masterCuti = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->where('masterCuti.nik = :nik')
            ->setParameter('nik', $nik)
            ->orderBy('masterCuti.tanggalKadaluarsa', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $masterCuti;
    }

    public function render(array $params = array())
    {
        extract($params);

        $this->templateManager->renderHeader();
        include('view/input_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
