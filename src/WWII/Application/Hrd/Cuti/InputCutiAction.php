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

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department($this->serviceManager, $this->entityManager);
        $this->employeeHelper = new \WWII\Common\Helper\Collection\MsSQL\Employee($this->serviceManager, $this->entityManager);
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

        if (!empty($errorMessages)) {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $masterCuti = $this->getRequestedMasterCuti($params['nik']);
            $masterCutiParent = null;

            if ($masterCuti->getParent() !== null
                && $masterCuti->getParent()->getPerpanjanganCuti() !== null
                && !$masterCuti->getParent()->getPerpanjanganCuti()->isExpired()
                && $masterCuti->getParent()->getSisaLimit() > 0) {
                $masterCutiParent = $masterCuti->getParent();
            }

            $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
            $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);

            $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
            $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);

            $pengambilanCuti = new \WWII\Domain\Hrd\Cuti\PengambilanCuti();

            $pengambilanCuti->setKeterangan($params['keterangan']);
            $pengambilanCuti->setTanggalInput(new \DateTime());
            $loginSession = explode(',', $_SESSION['arinaSess']);
            $pelaksana = $loginSession[1];
            $pengambilanCuti->setPelaksana($pelaksana);

            if ($masterCutiParent === null) {
                $pengambilanCuti->setTanggalAwal($tanggalAwal);
                $pengambilanCuti->setTanggalAkhir($tanggalAkhir);
                $pengambilanCuti->setMasterCuti($masterCuti);

                $this->entityManager->persist($pengambilanCuti);
            } elseif ($masterCutiParent !== null
                && $tanggalAwal->diff($tanggalAkhir)->format('%a') <= $masterCutiParent->getSisaLimit()) {
                $pengambilanCuti->setTanggalAwal($tanggalAwal);
                $pengambilanCuti->setTanggalAkhir($tanggalAkhir);
                $pengambilanCuti->setMasterCuti($masterCutiParent);

                $this->entityManager->persist($pengambilanCuti);
            } else {
                $pengambilanCutiParent = clone($pengambilanCuti);

                $tanggalAkhirParent = clone($tanggalAwal);
                $tanggalAkhirParent->add(new \DateInterval('P' . ($masterCutiParent->getSisaLimit()-1) . 'D'));

                $tanggalAwalChild = clone($tanggalAkhirParent);
                $tanggalAwalChild->add(new \DateInterval('P1D'));

                $pengambilanCutiParent->setTanggalAwal($tanggalAwal);
                $pengambilanCutiParent->setTanggalAkhir($tanggalAkhirParent);
                $pengambilanCutiParent->setMasterCuti($masterCutiParent);

                $pengambilanCuti->setTanggalAwal($tanggalAwalChild);
                $pengambilanCuti->setTanggalAkhir($tanggalAkhir);
                $pengambilanCuti->setMasterCuti($masterCuti);

                $this->entityManager->persist($pengambilanCutiParent);
                $this->entityManager->persist($pengambilanCuti);
            }

            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['nik'])) {
            $errorMessages['nik'] = $this->getErrorMessage(0);
        } elseif ($this->employeeHelper->isActive($params['nik']) === false) {
            $errorMessages['nik'] = $this->getErrorMessage(1);
        } else {
            $masterCuti = $this->getRequestedMasterCuti($params['nik']);

            if ($masterCuti === null) {
                $errorMessages['nik'] = $this->getErrorMessage(2);
            } elseif ($masterCuti->getSisaLimit() === 0) {
                if ($masterCuti->getParent() !== null) {
                    if ($masterCuti->getParent()->getPerpanjanganCuti() !== null) {
                        if ($masterCuti->getParent()->getPerpanjanganCuti()->isExpired()) {
                            $errorMessages['nik'] = $this->getErrorMessage(3);
                        } elseif ($masterCuti->getParent()->getPerpanjanganCuti()->getSisaLimit() === 0) {
                            $errorMessages['nik'] = $this->getErrorMessage(3);
                        }
                    }
                } else {
                    $this->getErrorMessage(3);
                }
            }

            if (strtoupper($params['btx']) == 'SIMPAN') {
                if ($params['tanggalAwal'] == '' || $params['tanggalAkhir'] == '') {
                    $errorMessages['tanggal'] = $this->getErrorMessage(4);
                } else {
                    $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
                    $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
                    try {
                        $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
                        $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);
                    } catch(\Exception $e) {
                        $this->errorMessages['tanggal'] = $this->getErrorMessage(5);
                    }
                }

                if ($params['keterangan'] == '') {
                    $errorMessages['keterangan'] = 'harus diisi';
                }
            }
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Cuti\MasterCuti $masterCuti, $params)
    {
        $params = array_merge(
            array(
                'nik' => isset($params['nik']) ? $params['nik'] : '',
                'namaKaryawan' => $masterCuti->getNamaKaryawan(),
                'departemen' => isset($params['departemen']) ? $params['departemen'] : $masterCuti->getDepartemen(),
                'sisaLimitPeriodeAktif' => $masterCuti->getSisaLimit(),
                'tanggalKadaluarsaPeriodeAktif' => call_user_func(function($masterCuti) {
                    if (!$masterCuti->isExpired()) {
                        return $masterCuti->getTanggalKadaluarsa()->format('d/m/Y');
                    } elseif($masterCuti->getPerpanjanganCuti() !== null && !$masterCuti->getPerpanjanganCuti()->isExpired()) {
                        return $masterCuti->getPerpanjanganCuti()->getTanggalKadaluarsa()->format('d/m/Y');
                    } else {
                        return '-';
                    }
                }, $masterCuti),
                'sisaLimitPeriodeSebelumnya' => call_user_func(function($masterCuti) {
                    if ($masterCuti->getParent() !== null
                        && $masterCuti->getParent()->getPerpanjanganCuti() !== null
                        && !$masterCuti->getParent()->getPerpanjanganCuti()->isExpired()) {
                        return $masterCuti->getParent()->getSisaLimit();
                    } else {
                        return '-';
                    }
                }, $masterCuti),
                'tanggalKadaluarsaPeriodeSebelumnya' => call_user_func(function($masterCuti) {
                    if ($masterCuti->getParent() !== null
                        && $masterCuti->getParent()->getPerpanjanganCuti() !== null
                        && !$masterCuti->getParent()->getPerpanjanganCuti()->isExpired()) {
                        return $masterCuti->getParent()->getPerpanjanganCuti()->getTanggalKadaluarsa()->format('d/m/Y');
                    } else {
                        return '-';
                    }
                }, $masterCuti),
            ),
            $params
        );

        return $params;
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

    public function getErrorMessage($code = null)
    {
        switch ($code) {
            case 0:
                return 'NIK harus diisi';
            case 1:
                return 'karyawan sudah tidak aktif';
            case 2:
                return 'karyawan belum memiliki hak cuti';
            case 3:
                return 'karyawan tidak memiliki sisa cuti';
            case 4:
                return 'tanggal harus diisi';
            case 5:
                return 'format tidak valid (ex. 17/03/2014)';
            default:
                'karyawan belum memiliki hak cuti';
        }
    }

    public function render(array $params = null)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/input_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
