<?php

namespace WWII\Application\Hrd\Cuti;

class ApprovisasiCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $maxItemPerPage = 20;

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        if (!isset($_GET['key'])) {
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        } else {
            $params['key'] = $_GET['key'];
        }

        switch (strtoupper($params['btx'])) {
            case 'APPROVE':
                $result = $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_cuti'));
            default:
                $result = $this->dispatchFilter($params);
                break;
        }

        $this->render($result);
    }

    protected function dispatchFilter($params)
    {
        $pengambilanCuti = $this->findRequestedModel($params['key']);

        if ($pengambilanCuti == null) {
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        }

        return array(
            'errorMessages' => array(),
            'params' => $params,
            'data' => $pengambilanCuti
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = array();
        $pengambilanCuti = $this->findRequestedModel($params['key']);

        if ($pengambilanCuti == null) {
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        }

        $jumlahHariCuti = $pengambilanCuti->getJumlahHari();
        $dateStart = $pengambilanCuti->getTanggalAwal();
        $currentMasterCuti = $pengambilanCuti->getMasterCuti();
        $parentMasterCuti = $pengambilanCuti->getMasterCuti()->getParent();
        $childMasterCuti = $pengambilanCuti->getMasterCuti()->getChild();

        $totalSisaHariCuti = 0;
        if ($parentMasterCuti !== null && ! $parentMasterCuti->isExpired()) {
            $totalSisaHariCuti += $parentMasterCuti->getSisaLimit();
        }
        if ($currentMasterCuti !== null && ! $currentMasterCuti->isExpired()) {
            $totalSisaHariCuti += $currentMasterCuti->getSisaLimit();
        }
        if ($childMasterCuti !== null && ! $childMasterCuti->isExpired()) {
            $totalSisaHariCuti += $childMasterCuti->getSisaLimit();
        }

        if ($totalSisaHariCuti < $jumlahHariCuti) {
            $errorMessages[] = 'Jumlah total hari cuti melebihi sisa limit!';
        } else {
            $tmpPengambilanCuti = array();

            if ($parentMasterCuti !== null && ! $parentMasterCuti->isExpired() && $parentMasterCuti->getSisaLimit() > 0) {
                if ($parentMasterCuti->getSisaLimit() > $jumlahHari) {
                    $tmpJumlahHari = $jumlahHariCuti;
                } else {
                    $tmpJumlahHari = $parentMasterCuti->getSisaLimit();
                }

                $jumlahHariCuti -= $tmpJumlahHari;

                $tanggalAwal0 = clone($dateStart);
                $tanggalAkhir0 = clone($dateStart);
                $tanggalAkhir0->add(new \DateInterval("P{$tmpJumlahHari}D"));

                $tmpPengambilanCuti[0] = new \WWII\Domain\Hrd\Cuti\PengambilanCuti();
                $tmpPengambilanCuti[0]->setTanggalAwal($tanggalAwal0);
                $tmpPengambilanCuti[0]->setTanggalAkhir($tanggalAkhir0);
                $tmpPengambilanCuti[0]->setKeterangan($pengambilanCuti->getKeterangan());
                $tmpPengambilanCuti[0]->setPelaksana($pengambilanCuti->getPelaksana());
                $tmpPengambilanCuti[0]->setTanggalInput($pengambilanCuti->getTanggalInput());
                $tmpPengambilanCuti[0]->setDisetujui(true);
                $tmpPengambilanCuti[0]->setTanggalApprovisasi(new \DateTime());
                $tmpPengambilanCuti[0]->setMasterCuti($parentMasterCuti);
            }

            if ($currentMasterCuti !== null
                && ! $currentMasterCuti->isExpired()
                && $currentMasterCuti->getSisaLimit() > 0
                && $jumlahHariCuti > 0)  {
                if ($currentMasterCuti->getSisaLimit() > $jumlahHariCuti) {
                    $tmpJumlahHari = $jumlahHariCuti;
                } else {
                    $tmpJumlahHari = $currentMasterCuti->getSisaLimit();
                }

                $jumlahHariCuti -= $tmpJumlahHari;

                $tanggalAwal1 = clone($dateStart);

                if ($tmpPengambilanCuti[0] !== null) {
                    $tanggalAwal1 = clone($tmpPengambilanCuti[0]->getTanggalAkhir());
                    $tanggalAwal1->add(new \DateInterval('P1D'));
                }

                $tanggalAkhir1 = clone($tanggalAwal1);
                $tanggalAkhir1->add(new \DateInterval('P' . ($tmpJumlahHari - 1) . 'D'));

                $tmpPengambilanCuti[1] = $pengambilanCuti;
                $tmpPengambilanCuti[1]->setTanggalAwal($tanggalAwal1);
                $tmpPengambilanCuti[1]->setTanggalAkhir($tanggalAkhir1);
                $tmpPengambilanCuti[1]->setKeterangan($pengambilanCuti->getKeterangan());
                $tmpPengambilanCuti[1]->setPelaksana($pengambilanCuti->getPelaksana());
                $tmpPengambilanCuti[1]->setTanggalInput($pengambilanCuti->getTanggalInput());
                $tmpPengambilanCuti[1]->setDisetujui(true);
                $tmpPengambilanCuti[1]->setTanggalApprovisasi(new \DateTime());
                $tmpPengambilanCuti[1]->setMasterCuti($currentMasterCuti);
            }

            if ($childMasterCuti !== null &&
                ! $childMasterCuti->isExpired()
                && $childMasterCuti->getSisaLimit() > 0
                && $jumlahHariCuti > 0)  {
                if ($childMasterCuti->getSisaLimit() > $jumlahHariCuti) {
                    $tmpJumlahHari = $jumlahHariCuti;
                } else {
                    $tmpJumlahHari = $childMasterCuti->getSisaLimit();
                }

                $jumlahHariCuti -= $tmpJumlahHari;

                $tanggalAwal2 = clone($dateStart);

                if ($tmpPengambilanCuti[0] !== null) {
                    $tanggalAwal2 = clone($tmpPengambilanCuti[0]->getTanggalAkhir());
                    $tanggalAwal2->add(new \DateInterval('P1D'));
                } elseif ($tmpPengambilanCuti[1] !== null) {
                    $tanggalAwal2 = clone($tmpPengambilanCuti[1]->getTanggalAkhir());
                    $tanggalAwal2->add(new \DateInterval('P1D'));
                }

                $tanggalAkhir2 = clone($tanggalAwal2);
                $tanggalAkhir2->add(new \DateInterval('P' . ($tmpJumlahHari - 1) . 'D'));

                $tmpPengambilanCuti[2] = new \WWII\Domain\Hrd\Cuti\PengambilanCuti();
                $tmpPengambilanCuti[2]->setTanggalAwal($tanggalAwal2);
                $tmpPengambilanCuti[2]->setTanggalAkhir($tanggalAkhir2);
                $tmpPengambilanCuti[2]->setKeterangan($pengambilanCuti->getKeterangan());
                $tmpPengambilanCuti[2]->setPelaksana($pengambilanCuti->getPelaksana());
                $tmpPengambilanCuti[2]->setTanggalInput($pengambilanCuti->getTanggalInput());
                $tmpPengambilanCuti[2]->setDisetujui(true);
                $tmpPengambilanCuti[2]->setTanggalApprovisasi(new \DateTime());
                $tmpPengambilanCuti[2]->setMasterCuti($childMasterCuti);
            }
        }

        if (empty($errorMessages)) {
            foreach ($tmpPengambilanCuti as $item) {
                $this->entityManager->persist($item);
            }

            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Pengambilan cuti berhasil diapprovisasi.');
            $this->routeManager->redirect(array('action' => 'report_cuti'));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $pengambilanCuti
        );
    }

    protected function findRequestedModel($id)
    {
        $pengambilanCuti = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Cuti\PengambilanCuti')
            ->findOneById($id);

        return $pengambilanCuti;
    }

    protected function render(array $result = null)
    {
        $departmentList = $this->departmentHelper->getDepartmentList();

        if (!empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/approvisasi_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
