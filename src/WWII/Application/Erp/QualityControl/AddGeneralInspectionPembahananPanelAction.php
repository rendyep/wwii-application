<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionPembahananPanelAction
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection_pembahanan_panel'));
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
            $penambahanPanelInspection = $this->findPembahananPanelInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('pembahananPanelInspection', $penambahanPanelInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananPanelInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pembahananPanelItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananPanelInspectionItem();
            $pembahananPanelItem->setCustomer($params['customer']);
            $pembahananPanelItem->setPO($params['PO']);
            $pembahananPanelItem->setKodeProduk($params['kodeProduk']);
            $pembahananPanelItem->setNamaProduk($params['namaProduk']);
            $pembahananPanelItem->setInspectionLevel($params['level']);
            $pembahananPanelItem->setAcceptanceIndex($params['acceptanceIndex']);
            $pembahananPanelItem->setJumlahLot($params['jumlahLot']);
            $pembahananPanelItem->setJumlahInspeksi($params['jumlahInspeksi']);
            $pembahananPanelItem->setJumlahItemSalahPerakitan($params['jumlahItemSalahPerakitan']);
            $pembahananPanelItem->setJumlahItemMaterial($params['jumlahItemMaterial']);
            $pembahananPanelItem->setJumlahItemSalahMaterial($params['jumlahItemSalahMaterial']);
            $pembahananPanelItem->setJumlahItemArahBungaVeneerSalah($params['jumlahItemArahBungaVeneerSalah']);
            $pembahananPanelItem->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
            $pembahananPanelItem->setJumlahItemRonggaGap($params['jumlahItemRonggaGap']);
            $pembahananPanelItem->setJumlahItemGoresanPisau($params['jumlahItemGoresanPisau']);
            $pembahananPanelItem->setJumlahItemLengkunganTidakSama($params['jumlahItemLengkunganTidakSama']);
            $pembahananPanelItem->setJumlahItemRetak($params['jumlahItemRetak']);
            $pembahananPanelItem->setJumlahItemMenjadiHitam($params['jumlahItemMenjadiHitam']);
            $pembahananPanelItem->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
            $pembahananPanelItem->setJumlahItemTekananTidak($params['jumlahItemTekananTidak']);
            $pembahananPanelItem->setJumlahItemPotonganCuwil($params['jumlahItemPotonganCuwil']);
            $pembahananPanelItem->setJumlahItemAssemblyBuruk($params['jumlahItemAssemblyBuruk']);
            $pembahananPanelItem->setJumlahItemKesikuanSudut($params['jumlahItemKesikuanSudut']);
            $pembahananPanelItem->setJumlahItemDegumming($params['jumlahItemDegumming']);
            $pembahananPanelItem->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
            $pembahananPanelItem->setJumlahItemKurangLem($params['jumlahItemKurangLem']);
            $pembahananPanelItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);
            $pembahananPanelItem->setJumlahItemKurangPanjang($params['jumlahItemKurangPanjang']);
            $pembahananPanelItem->setJumlahItemKurangLebar($params['jumlahItemKurangLebar']);
            $pembahananPanelItem->setJumlahItemPanjangLebih($params['jumlahItemPanjangLebih']);
            $pembahananPanelItem->setJumlahItemLebarLebih($params['jumlahItemLebarLebih']);
            $pembahananPanelItem->setJumlahItemCutterMark($params['jumlahItemCutterMark']);
            $pembahananPanelItem->setJumlahItemPotonganTidakSiku($params['jumlahItemPotonganTidakSiku']);
            $pembahananPanelItem->setJumlahItemLetakAlurSalah($params['jumlahItemLetakAlurSalah']);
            $pembahananPanelItem->setJumlahItemKurangTebal($params['jumlahItemKurangTebal']);
            $pembahananPanelItem->setJumlahItemKurangTipis($params['jumlahItemKurangTipis']);
            $pembahananPanelItem->setJumlahItemMaterialGelombang($params['jumlahItemMaterialGelombang']);
            $pembahananPanelItem->setJumlahItemListTidakSama($params['jumlahItemListTidakSama']);
            $pembahananPanelItem->setJumlahItemTekananTidakMaksimal($params['jumlahItemTekananTidakMaksimal']);
            $pembahananPanelItem->setJumlahItemGelombang($params['jumlahItemGelombang']);
            $pembahananPanelItem->setJumlahItemRakitanTerbalik($params['jumlahItemRakitanTerbalik']);
            $pembahananPanelItem->setJumlahItemOverSending($params['jumlahItemOverSending']);
            $pembahananPanelItem->setJumlahItemKurangSending($params['jumlahItemKurangSending']);

            $pembahananPanelInspection = $this->getSessionData('pembahananPanelInspection');
var_dump($pembahananPanelItem->getJumlahItemListTidakSama());
var_dump($params['jumlahItemListTidakSama']);

            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $waktuInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                . ' ' . $params['waktu']
            );
            $time = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananPanelInspectionTime')
                ->findOneBy(array(
                    'pembahananPanelInspection' => $pembahananPanelInspection->getId(),
                    'waktuInspeksi' => $waktuInspeksi
                ));

            foreach ($pembahananPanelInspection->getPembahananPanelInspectionTime() as $tmpTime) {
                if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                    $time = $tmpTime;
                }
            }

            if ($time == null) {
                $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananPanelInspectionTime();
                $time->setWaktuInspeksi($waktuInspeksi);
                $time->addPembahananPanelInspectionItem($pembahananPanelItem);
                $pembahananPanelInspection->addPembahananPanelInspectionTime($time);
            } else {
                foreach ($pembahananPanelInspection->getPembahananPanelInspectionTime() as $pembahananPanelInspectionTime) {
                    if ($pembahananPanelInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                        $pembahananPanelInspectionTime->addPembahananPanelInspectionItem($pembahananPanelItem);
                    }
                }
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananPanelInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pembahananPanelInspection = $this->getSessionData('pembahananPanelInspection');

            if ($pembahananPanelInspection->getId() !== null) {
                $tmpPembahananPanelInspectionTimes = array();
                $tmpPembahananPanelInspectionItems = array();

                foreach ($pembahananPanelInspection->getPembahananPanelInspectionTime() as $pembahananPanelInspectionTime) {
                    if ($pembahananPanelInspectionTime->getId() === null) {
                        $tmpPembahananPanelInspectionTimes[] = $pembahananPanelInspectionTime;
                        $pembahananPanelInspection->removePembahananPanelInspectionTime($pembahananPanelInspectionTime);
                    } else {
                        foreach (
                            $pembahananPanelInspectionTime->getPembahananPanelInspectionItem()
                                as $pembahananPanelInspectionItem
                        ) {
                            if ($pembahananPanelInspectionItem->getId() === null) {
                                $tmpPembahananPanelInspectionItems[$pembahananPanelInspectionTime->getId()][] =
                                    $pembahananPanelInspectionItem;
                                $pembahananPanelInspectionTime->removePembahananPanelInspectionItem(
                                    $pembahananPanelInspectionItem
                                );
                            }
                        }
                    }
                }

                $pembahananPanelInspection = $this->entityManager->merge($pembahananPanelInspection);

                foreach ($tmpPembahananPanelInspectionTimes as $tmpPembahananPanelInspectionTime) {
                    $pembahananPanelInspection->addPembahananPanelInspectionTime($pembahananPanelInspectionTime);
                }

                foreach ($tmpPembahananPanelInspectionItems as $key => $tmpPembahananPanelInspectionItem) {
                    foreach (
                        $pembahananPanelInspection->getPembahananPanelInspectionTime() as $pembahananPanelInspectionTime
                    ) {
                        if ($pembahananPanelInspectionTime->getId() == $key) {
                            foreach ($tmpPembahananPanelInspectionItem as $item) {
                                $pembahananPanelInspectionTime->addPembahananPanelInspectionItem($item);
                            }
                        }
                    }
                }
            }

            $this->entityManager->persist($pembahananPanelInspection);
            $this->entityManager->flush();

            $this->routeManager->redirect(array(
                'action' => 'report_general_inspection_pembahanan_panel_print',
                'group' => lcfirst($params['group']),
                'key' => $params['group'] . ':' . $pembahananPanelInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('pembahananPanelInspection')
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

                if ($params['jumlahItemSalahPerakitan'] == '') {
                    $errorMessages['jumlahItemSalahPerakitan'] = 'harus berupa angka';
                }

                if ($params['jumlahItemMaterial'] == '') {
                    $errorMessages['jumlahItemMaterial'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahMaterial'] == '') {
                    $errorMessages['jumlahItemSalahMaterial'] = 'harus berupa angka';
                }

                if ($params['jumlahItemArahBungaVeneerSalah'] == '') {
                    $errorMessages['jumlahItemArahBungaVeneerSalah'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahUkuran'] == '') {
                    $errorMessages['jumlahItemSalahUkuran'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRonggaGap'] == '') {
                    $errorMessages['jumlahItemRonggaGap'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGoresanPisau'] == '') {
                    $errorMessages['jumlahItemGoresanPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemLengkunganTidakSama'] == '') {
                    $errorMessages['jumlahItemLengkunganTidakSama'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRetak'] == '') {
                    $errorMessages['jumlahItemRetak'] = 'harus berupa angka';
                }

                if ($params['jumlahItemMenjadiHitam'] == '') {
                    $errorMessages['jumlahItemMenjadiHitam'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSandingBuruk'] == '') {
                    $errorMessages['jumlahItemSandingBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemTekananTidak'] == '') {
                    $errorMessages['jumlahItemTekananTidak'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPotonganCuwil'] == '') {
                    $errorMessages['jumlahItemPotonganCuwil'] = 'harus berupa angka';
                }

                if ($params['jumlahItemAssemblyBuruk'] == '') {
                    $errorMessages['jumlahItemAssemblyBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKesikuanSudut'] == '') {
                    $errorMessages['jumlahItemKesikuanSudut'] = 'harus berupa angka';
                }

                if ($params['jumlahItemDegumming'] == '') {
                    $errorMessages['jumlahItemDegumming'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKelebihanLem'] == '') {
                    $errorMessages['jumlahItemKelebihanLem'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangLem'] == '') {
                    $errorMessages['jumlahItemKurangLem'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBurukLainnya'] == '') {
                    $errorMessages['jumlahItemBurukLainnya'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangPanjang'] == '') {
                    $errorMessages['jumlahItemKurangPanjang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangLebar'] == '') {
                    $errorMessages['jumlahItemKurangLebar'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPanjangLebih'] == '') {
                    $errorMessages['jumlahItemPanjangLebih'] = 'harus berupa angka';
                }

                if ($params['jumlahItemLebarLebih'] == '') {
                    $errorMessages['jumlahItemLebarLebih'] = 'harus berupa angka';
                }

                if ($params['jumlahItemCutterMark'] == '') {
                    $errorMessages['jumlahItemCutterMark'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPotonganTidakSiku'] == '') {
                    $errorMessages['jumlahItemPotonganTidakSiku'] = 'harus berupa angka';
                }

                if ($params['jumlahItemLetakAlurSalah'] == '') {
                    $errorMessages['jumlahItemLetakAlurSalah'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangTebal'] == '') {
                    $errorMessages['jumlahItemKurangTebal'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangTipis'] == '') {
                    $errorMessages['jumlahItemKurangTipis'] = 'harus berupa angka';
                }

                if ($params['jumlahItemMaterialGelombang'] == '') {
                    $errorMessages['jumlahItemMaterialGelombang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemListTidakSama'] == '') {
                    $errorMessages['jumlahItemListTidakSama'] = 'harus berupa angka';
                }

                if ($params['jumlahItemTekananTidakMaksimal'] == '') {
                    $errorMessages['jumlahItemTekananTidakMaksimal'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGelombang'] == '') {
                    $errorMessages['jumlahItemGelombang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRakitanTerbalik'] == '') {
                    $errorMessages['jumlahItemRakitanTerbalik'] = 'harus berupa angka';
                }

                if ($params['jumlahItemOverSending'] == '') {
                    $errorMessages['jumlahItemOverSending'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKurangSending'] == '') {
                    $errorMessages['jumlahItemKurangSending'] = 'harus berupa angka';
                }

                break;
        }

        return $errorMessages;
    }

    protected function findPembahananPanelInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $penambahanPanelInspection = $this->entityManager->createQueryBuilder()
            ->select('pembahananPanelInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananPanelInspection',
                'pembahananPanelInspection'
            )
            ->leftJoin(
                'pembahananPanelInspection.pembahananPanelInspectionTime',
                'pembahananPanelInspectionTime'
            )
            ->leftJoin(
                'pembahananPanelInspectionTime.pembahananPanelInspectionItem',
                'pembahananPanelInspectionItem'
            )
            ->where('pembahananPanelInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('pembahananPanelInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($penambahanPanelInspection === null || empty($penambahanPanelInspection)) {
            $penambahanPanelInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PembahananPanelInspection();
            $penambahanPanelInspection->setTanggalInspeksi($tanggalInspeksi);
            $penambahanPanelInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $penambahanPanelInspection->setStaffQc($staffQc);
        }

        return $penambahanPanelInspection;
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
        include('view/add_general_inspection_pembahanan_panel.phtml');
        $this->templateManager->renderFooter();
    }
}
