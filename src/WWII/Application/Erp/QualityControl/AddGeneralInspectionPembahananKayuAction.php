<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionPembahananKayuAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $sessionContainer;

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->sessionContainer = $serviceManager->get('SessionContainer');
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'PROSES':
                $result = $this->dispatchProses($params);
                break;
            case 'ADD':
                $result = $this->dispatchAddItem($params);
                break;
            case 'SIMPAN':
                $result = $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_general_inspection_pembahanan_kayu'));
                break;
            default:
                $this->clearSessionData();
        }

        $this->render($result);
    }

    protected function dispatchProses($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $tanggalInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2] . '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
            );
            $penambahanKayuInspection = $this->findPembahananKayuInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('pembahananKayuInspection', $penambahanKayuInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananKayuInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pembahananKayuItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananKayuInspectionItem();
            $pembahananKayuItem->setCustomer($params['customer']);
            $pembahananKayuItem->setPO($params['PO']);
            $pembahananKayuItem->setKodeProduk($params['kodeProduk']);
            $pembahananKayuItem->setNamaProduk($params['namaProduk']);
            $pembahananKayuItem->setInspectionLevel($params['level']);
            $pembahananKayuItem->setAcceptanceIndex($params['acceptanceIndex']);
            $pembahananKayuItem->setJumlahLot($params['jumlahLot']);
            $pembahananKayuItem->setJumlahInspeksi($params['jumlahInspeksi']);
            $pembahananKayuItem->setJumlahItemMataKayuMati($params['jumlahItemMataKayuMati']);
            $pembahananKayuItem->setJumlahItemHatiKayu($params['jumlahItemHatiKayu']);
            $pembahananKayuItem->setJumlahItemPinHole($params['jumlahItemPinHole']);
            $pembahananKayuItem->setJumlahItemPecah($params['jumlahItemPecah']);
            $pembahananKayuItem->setJumlahItemRetak($params['jumlahItemRetak']);
            $pembahananKayuItem->setJumlahItemUkuranKurang($params['jumlahItemUkuranKurang']);
            $pembahananKayuItem->setJumlahItemUkuranLebih($params['jumlahItemUkuranLebih']);
            $pembahananKayuItem->setJumlahItemBusukBerjamur($params['jumlahItemBusukBerjamur']);
            $pembahananKayuItem->setJumlahItemBlueStain($params['jumlahItemBlueStain']);
            $pembahananKayuItem->setJumlahItemBekasRoda($params['jumlahItemBekasRoda']);
            $pembahananKayuItem->setJumlahItemBekasPisau($params['jumlahItemBekasPisau']);
            $pembahananKayuItem->setJumlahItemBedaWarna($params['jumlahItemBedaWarna']);
            $pembahananKayuItem->setJumlahItemBengkok($params['jumlahItemBengkok']);
            $pembahananKayuItem->setJumlahItemGarisLem($params['jumlahItemGarisLem']);
            $pembahananKayuItem->setJumlahItemGelombang($params['jumlahItemGelombang']);
            $pembahananKayuItem->setJumlahItemSalahPisau($params['jumlahItemSalahPisau']);
            $pembahananKayuItem->setJumlahItemLemTidakStandard($params['jumlahItemLemTidakStandard']);
            $pembahananKayuItem->setJumlahItemSuhuTerlaluTinggi($params['jumlahItemSuhuTerlaluTinggi']);
            $pembahananKayuItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);

            $pembahananKayuInspection = $this->getSessionData('pembahananKayuInspection');

            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $waktuInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                . ' ' . $params['waktu']
            );
            $time = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananKayuInspectionTime')
                ->findOneBy(array(
                    'pembahananKayuInspection' => $pembahananKayuInspection->getId(),
                    'waktuInspeksi' => $waktuInspeksi
                ));

            foreach ($pembahananKayuInspection->getPembahananKayuInspectionTime() as $tmpTime) {
                if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                    $time = $tmpTime;
                }
            }

            if ($time == null) {
                $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananKayuInspectionTime();
                $time->setWaktuInspeksi($waktuInspeksi);
                $time->addPembahananKayuInspectionItem($pembahananKayuItem);
                $pembahananKayuInspection->addPembahananKayuInspectionTime($time);
            } else {
                foreach ($pembahananKayuInspection->getPembahananKayuInspectionTime() as $pembahananKayuInspectionTime) {
                    if ($pembahananKayuInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                        $pembahananKayuInspectionTime->addPembahananKayuInspectionItem($pembahananKayuItem);
                    }
                }
            }

            $params['waktu'] = '';
            $params['customer'] = '';
            $params['po'] = '';
            $params['kodeProduk'] = '';
            $params['namaProduk'] = '';
            $params['level'] = '';
            $params['acceptanceIndex'] = '';
            $params['jumlahInspeksi'] = 0;
            $params['jumlahItemMataKayuMati'] = 0;
            $params['jumlahItemHatiKayu'] = 0;
            $params['jumlahItemPinHole'] = 0;
            $params['jumlahItemPecah'] = 0;
            $params['jumlahItemRetak'] = 0;
            $params['jumlahItemUkuranKurang'] = 0;
            $params['jumlahItemUkuranLebih'] = 0;
            $params['jumlahItemBusukBerjamur'] = 0;
            $params['jumlahItemBlueStain'] = 0;
            $params['jumlahItemBekasRoda'] = 0;
            $params['jumlahItemBekasPisau'] = 0;
            $params['jumlahItemBedaWarna'] = 0;
            $params['jumlahItemBengkok'] = 0;
            $params['jumlahItemGarisLem'] = 0;
            $params['jumlahItemGelombang'] = 0;
            $params['jumlahItemSalahPisau'] = 0;
            $params['jumlahItemLemTidakStandard'] = 0;
            $params['jumlahItemSuhuTerlaluTinggi'] = 0;
            $params['jumlahItemBurukLainnya'] = 0;
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananKayuInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pembahananKayuInspection = $this->getSessionData('pembahananKayuInspection');

            if ($pembahananKayuInspection->getId() !== null) {
                $tmpPembahananKayuInspectionTimes = array();
                $tmpPembahananKayuInspectionItems = array();

                foreach ($pembahananKayuInspection->getPembahananKayuInspectionTime() as $pembahananKayuInspectionTime) {
                    if ($pembahananKayuInspectionTime->getId() === null) {
                        $tmpPembahananKayuInspectionTimes[] = $pembahananKayuInspectionTime;
                        $pembahananKayuInspection->removePembahananKayuInspectionTime($pembahananKayuInspectionTime);
                    } else {
                        foreach (
                            $pembahananKayuInspectionTime->getPembahananKayuInspectionItem()
                                as $pembahananKayuInspectionItem
                        ) {
                            if ($pembahananKayuInspectionItem->getId() === null) {
                                $tmpPembahananKayuInspectionItems[$pembahananKayuInspectionTime->getId()][] =
                                    $pembahananKayuInspectionItem;
                                $pembahananKayuInspectionTime->removePembahananKayuInspectionItem(
                                    $pembahananKayuInspectionItem
                                );
                            }
                        }
                    }
                }

                $pembahananKayuInspection = $this->entityManager->merge($pembahananKayuInspection);

                foreach ($tmpPembahananKayuInspectionTimes as $tmpPembahananKayuInspectionTime) {
                    $pembahananKayuInspection->addPembahananKayuInspectionTime($pembahananKayuInspectionTime);
                }

                foreach ($tmpPembahananKayuInspectionItems as $key => $tmpPembahananKayuInspectionItem) {
                    foreach (
                        $pembahananKayuInspection->getPembahananKayuInspectionTime() as $pembahananKayuInspectionTime
                    ) {
                        if ($pembahananKayuInspectionTime->getId() == $key) {
                            foreach ($tmpPembahananKayuInspectionItem as $item) {
                                $pembahananKayuInspectionTime->addPembahananKayuInspectionItem($item);
                            }
                        }
                    }
                }
            }

            $this->entityManager->persist($pembahananKayuInspection);
            $this->entityManager->flush();

            $this->routeManager->redirect(array(
                'action' => 'report_general_inspection_pembahanan_kayu_print',
                'group' => lcfirst($params['group']),
                'key' => $pembahananKayuInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananKayuInspection')
        );
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        switch (strtoupper($params['btx'])) {
            case 'SIMPAN':
            case 'PROSES':
                if ($params['tanggalInspeksi'] == '') {
                    $errorMessages['tanggalInspeksi'] = 'harus diisi';
                } else {
                    $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
                    try {
                        $tanggalInspeksi = new \DateTime(
                            $arrayTanggalInspeksi[2] . '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                        );
                    } catch(\Exception $e) {
                        $errorMessages['tanggalInspeksi'] = 'format tidak valid (ex. 08/04/2014)';
                    }
                }

                if ($params['lokasi'] == '') {
                    $errorMessages['lokasi'] = 'harus diisi';
                }

                break;
            case 'ADD':
                if ($params['waktu'] == '') {
                    $errorMessages['waktu'] = 'harus diisi';
                } else {
                    try {
                        $now = new \DateTime();
                        $waktu = new \DateTime($now->format('Y-m-d') . ' ' . $params['waktu']);
                    } catch (\Exception $e) {
                        $errorMessages['waktu'] = 'format tidak valid (ex. 07:00)';
                    }
                }

                if ($params['customer'] == '') {
                    $errorMessages['customer'] = 'harus diisi';
                }

                if ($params['PO'] == '') {
                    $errorMessages['PO'] = 'harus diisi';
                }

                if ($params['kodeProduk'] == '') {
                    $errorMessages['kodeProduk'] = 'harus diisi';
                }

                if ($params['namaProduk'] == '') {
                    $errorMessages['namaProduk'] = 'harus diisi';
                }

                if ($params['level'] == '') {
                    $errorMessages['level'] = 'harus dipilih';
                }

                if (empty($params['jumlahLot']) || $params['jumlahLot'] < 2) {
                    $errorMessages['jumlahLot'] = 'harus lebih besar dari 1 (satu)';
                }

                if ($params['jumlahItemMataKayuMati'] == '') {
                    $errorMessages['jumlahItemMataKayuMati'] = 'harus berupa angka';
                }

                if ($params['jumlahItemHatiKayu'] == '') {
                    $errorMessages['jumlahItemHatiKayu'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPinHole'] == '') {
                    $errorMessages['jumlahItemPinHole'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPecah'] == '') {
                    $errorMessages['jumlahItemPecah'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRetak'] == '') {
                    $errorMessages['jumlahItemRetak'] = 'harus berupa angka';
                }

                if ($params['jumlahItemUkuranKurang'] == '') {
                    $errorMessages['jumlahItemUkuranKurang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemUkuranLebih'] == '') {
                    $errorMessages['jumlahItemUkuranLebih'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBusukBerjamur'] == '') {
                    $errorMessages['jumlahItemBusukBerjamur'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBlueStain'] == '') {
                    $errorMessages['jumlahItemBlueStain'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBekasRoda'] == '') {
                    $errorMessages['jumlahItemBekasRoda'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBekasPisau'] == '') {
                    $errorMessages['jumlahItemBekasPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBedaWarna'] == '') {
                    $errorMessages['jumlahItemBedaWarna'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBengkok'] == '') {
                    $errorMessages['jumlahItemBengkok'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGarisLem'] == '') {
                    $errorMessages['jumlahItemGarisLem'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGelombang'] == '') {
                    $errorMessages['jumlahItemGelombang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahPisau'] == '') {
                    $errorMessages['jumlahItemSalahPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemLemTidakStandard'] == '') {
                    $errorMessages['jumlahItemLemTidakStandard'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSuhuTerlaluTinggi'] == '') {
                    $errorMessages['jumlahItemSuhuTerlaluTinggi'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBurukLainnya'] == '') {
                    $errorMessages['jumlahItemBurukLainnya'] = 'harus berupa angka';
                }

                break;
        }

        return $errorMessages;
    }

    protected function findPembahananKayuInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $penambahanKayuInspection = $this->entityManager->createQueryBuilder()
            ->select('pembahananKayuInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananKayuInspection',
                'pembahananKayuInspection'
            )
            ->leftJoin(
                'pembahananKayuInspection.pembahananKayuInspectionTime',
                'pembahananKayuInspectionTime'
            )
            ->leftJoin(
                'pembahananKayuInspectionTime.pembahananKayuInspectionItem',
                'pembahananKayuInspectionItem'
            )
            ->where('pembahananKayuInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('pembahananKayuInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($penambahanKayuInspection === null || empty($penambahanKayuInspection)) {
            $penambahanKayuInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananKayuInspection();
            $penambahanKayuInspection->setTanggalInspeksi($tanggalInspeksi);
            $penambahanKayuInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $penambahanKayuInspection->setStaffQc($staffQc);
        }

        return $penambahanKayuInspection;
    }

    protected function addSessionData($name, $data)
    {
        $sessionNamespace = $this->getSessionNamespace();

        if (!isset($this->sessionContainer->{$sessionNamespace})) {
            $this->sessionContainer->{$sessionNamespace} = new \StdClass();
        }

        $this->sessionContainer->{$sessionNamespace}->{$name} = $data;

        return $this->sessionContainer->{$sessionNamespace}->{$name};
    }

    protected function getSessionData($name)
    {
        $sessionNamespace = $this->getSessionNamespace();

        if (! isset($this->sessionContainer->{$sessionNamespace})) {
            return null;
        } elseif (! isset($this->sessionContainer->{$sessionNamespace}->{$name})) {
            return null;
        } else {
            return $this->sessionContainer->{$sessionNamespace}->{$name};
        }
    }

    protected function clearSessionData()
    {
        $sessionNamespace = $this->getSessionNamespace();

        unset($this->sessionContainer->{$sessionNamespace});
    }

    protected function getSessionNamespace()
    {
        $module = $this->routeManager->getModule();
        $controller = $this->routeManager->getController();
        $action = $this->routeManager->getAction();

        $sessionNamespace = "{$module}_{$controller}_{$action}";

        return $sessionNamespace;
    }

    protected function render(array $result = null)
    {
        $levelList = $this->entityManager
            ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\Level')
            ->findAll();
        $acceptanceIndexList = $this->entityManager
            ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\AcceptanceIndex')
            ->findAll();

        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/add_general_inspection_pembahanan_kayu.phtml');
        $this->templateManager->renderFooter();
    }
}
