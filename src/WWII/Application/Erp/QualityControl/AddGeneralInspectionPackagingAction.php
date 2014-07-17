<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionPackagingAction
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection_packaging'));
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
            $inspection = $this->findPackagingInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('packagingInspection', $inspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('packagingInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $packagingItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PackagingInspectionItem();
            $packagingItem->setKodeProduk($params['kodeProduk']);
            $packagingItem->setNamaProduk($params['namaProduk']);
            $packagingItem->setInspectionLevel($params['level']);
            $packagingItem->setAcceptanceIndex($params['acceptanceIndex']);
            $packagingItem->setJumlahLot($params['jumlahLot']);
            $packagingItem->setJumlahInspeksi($params['jumlahInspeksi']);
            $packagingItem->setJumlahItemSalahFlowProses($params['jumlahItemSalahFlowProses']);
            $packagingItem->setJumlahItemKualitasBuruk($params['jumlahItemKualitasBuruk']);
            $packagingItem->setJumlahItemSalahKualitas($params['jumlahItemSalahKualitas']);
            $packagingItem->setJumlahItemSalahPosisiLubang($params['jumlahItemSalahPosisiLubang']);
            $packagingItem->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
            $packagingItem->setJumlahItemBekasGoresanPisau($params['jumlahItemBekasGoresanPisau']);
            $packagingItem->setJumlahItemSobek($params['jumlahItemSobek']);
            $packagingItem->setJumlahItemRetak($params['jumlahItemRetak']);
            $packagingItem->setJumlahItemHitam($params['jumlahItemHitam']);
            $packagingItem->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
            $packagingItem->setJumlahItemGoresanTekanan($params['jumlahItemGoresanTekanan']);
            $packagingItem->setJumlahItemPakuKeluar($params['jumlahItemPakuKeluar']);
            $packagingItem->setJumlahItemBerdiriBuruk($params['jumlahItemBerdiriBuruk']);
            $packagingItem->setJumlahItemPerbaikanBuruk($params['jumlahItemPerbaikanBuruk']);
            $packagingItem->setJumlahItemLemDegumming($params['jumlahItemLemDegumming']);
            $packagingItem->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
            $packagingItem->setJumlahItemSuhuTerlaluTinggi($params['jumlahItemSuhuTerlaluTinggi']);
            $packagingItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);
            $packagingItem->setJumlahItemKekurangan($params['jumlahItemKekurangan']);

            $packagingInspection = $this->getSessionData('packagingInspection');

            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $waktuInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                . ' ' . $params['waktu']
            );
            $time = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\PackagingInspectionTime')
                ->findOneBy(array(
                    'packagingInspection' => $packagingInspection->getId(),
                    'waktuInspeksi' => $waktuInspeksi
                ));

            foreach ($packagingInspection->getPackagingInspectionTime() as $tmpTime) {
                if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                    $time = $tmpTime;
                }
            }

            if ($time == null) {
                $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PackagingInspectionTime();
                $time->setWaktuInspeksi($waktuInspeksi);
                $time->addPackagingInspectionItem($packagingItem);
                $packagingInspection->addPackagingInspectionTime($time);
            } else {
                foreach ($packagingInspection->getPackagingInspectionTime() as $packagingInspectionTime) {
                    if ($packagingInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                        $packagingInspectionTime->addPackagingInspectionItem($packagingItem);
                    }
                }
            }

            $params['waktu'] = '';
            $params['kodeProduk'] = '';
            $params['namaProduk'] = '';
            $params['level'] = '';
            $params['acceptanceIndex'] = '';
            $params['jumlahInspeksi'] = 0;
            $params['jumlahItemSalahFlowProses'] = 0;
            $params['jumlahItemKualitasBuruk'] = 0;
            $params['jumlahItemSalahKualitas'] = 0;
            $params['jumlahItemSalahPosisiLubang'] = 0;
            $params['jumlahItemSalahUkuran'] = 0;
            $params['jumlahItemBekasGoresanPisau'] = 0;
            $params['jumlahItemSobek'] = 0;
            $params['jumlahItemRetak'] = 0;
            $params['jumlahItemHitam'] = 0;
            $params['jumlahItemSandingBuruk'] = 0;
            $params['jumlahItemGoresanTekanan'] = 0;
            $params['jumlahItemPakuKeluar'] = 0;
            $params['jumlahItemBerdiriBuruk'] = 0;
            $params['jumlahItemPerbaikanBuruk'] = 0;
            $params['jumlahItemLemDegumming'] = 0;
            $params['jumlahItemKelebihanLem'] = 0;
            $params['jumlahItemSuhuTerlaluTinggi'] = 0;
            $params['jumlahItemBurukLainnya'] = 0;
            $params['jumlahItemKekurangan'] = 0;
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('packagingInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
                $packagingInspection = $this->getSessionData('packagingInspection');

            if ($packagingInspection->getId() !== null) {
                $tmpPackagingInspectionTimes = array();
                $tmpPackagingInspectionItems = array();

                foreach ($packagingInspection->getPackagingInspectionTime() as $packagingInspectionTime) {
                    if ($packagingInspectionTime->getId() === null) {
                        $tmpPackagingInspectionTimes[] = $packagingInspectionTime;
                        $packagingInspection->removePackagingInspectionTime($packagingInspectionTime);
                    } else {
                        foreach (
                            $packagingInspectionTime->getDailyInspectionItem() as $packagingInspectionItem
                        ) {
                            if ($packagingInspectionItem->getId() === null) {
                                $tmpPackagingInspectionItems[$dailyInspectionTime->getId()][] =
                                    $packagingInspectionItem;
                                $packagingInspectionTime->removePackagingInspectionItem(
                                    $packagingInspectionItem
                                );
                            }
                        }
                    }
                }

                $packagingInspection = $this->entityManager->merge($packagingInspection);

                foreach ($tmpPackagingInspectionTimes as $tmpPackagingInspectionTime) {
                    $packagingInspection->addPackagingInspectionTime($packagingInspectionTime);
                }

                foreach ($tmpPackagingInspectionItems as $key => $tmpPackagingInspectionItem) {
                    foreach ($packagingInspection->getPackagingInspectionTime() as $packagingInspectionTime) {
                        if ($packagingInspectionTime->getId() == $key) {
                            foreach ($tmpPackagingInspectionItem as $item) {
                                $packagingInspectionTime->addPackagingInspectionItem($item);
                            }
                        }
                    }
                }
            }

            $this->entityManager->persist($packagingInspection);
            $this->entityManager->flush();

            $this->routeManager->redirect(array(
                'action' => 'report_general_inspection_packaging_print',
                'key' => $packagingInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('packagingInspection')
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

                if ($params['jumlahItemSalahFlowProses'] == '') {
                    $errorMessages['jumlahItemSalahFlowProses'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKualitasBuruk'] == '') {
                    $errorMessages['jumlahItemKualitasBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahKualitas'] == '') {
                    $errorMessages['jumlahItemSalahKualitas'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahPosisiLubang'] == '') {
                    $errorMessages['jumlahItemSalahPosisiLubang'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahUkuran'] == '') {
                    $errorMessages['jumlahItemSalahUkuran'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBekasGoresanPisau'] == '') {
                    $errorMessages['jumlahItemBekasGoresanPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSobek'] == '') {
                    $errorMessages['jumlahItemSobek'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRetak'] == '') {
                    $errorMessages['jumlahItemRetak'] = 'harus berupa angka';
                }

                if ($params['jumlahItemHitam'] == '') {
                    $errorMessages['jumlahItemHitam'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSandingBuruk'] == '') {
                    $errorMessages['jumlahItemSandingBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGoresanTekanan'] == '') {
                    $errorMessages['jumlahItemGoresanTekanan'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPakuKeluar'] == '') {
                    $errorMessages['jumlahItemPakuKeluar'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBerdiriBuruk'] == '') {
                    $errorMessages['jumlahItemBerdiriBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPerbaikanBuruk'] == '') {
                    $errorMessages['jumlahItemPerbaikanBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemLemDegumming'] == '') {
                    $errorMessages['jumlahItemLemDegumming'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKelebihanLem'] == '') {
                    $errorMessages['jumlahItemKelebihanLem'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSuhuTerlaluTinggi'] == '') {
                    $errorMessages['jumlahItemSuhuTerlaluTinggi'] = 'harus berupa angka';
                }

                if ($params['jumlahItemBurukLainnya'] == '') {
                    $errorMessages['jumlahItemBurukLainnya'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKekurangan'] == '') {
                    $errorMessages['jumlahItemKekurangan'] = 'harus berupa angka';
                }

                break;
        }

        return $errorMessages;
    }

    protected function findPackagingInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $inspection = $this->entityManager->createQueryBuilder()
            ->select('packagingInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\PackagingInspection',
                'packagingInspection'
            )
            ->leftJoin(
                'packagingInspection.packagingInspectionTime',
                'packagingInspectionTime'
            )
            ->leftJoin(
                'packagingInspectionTime.packagingInspectionItem',
                'packagingInspectionItem'
            )
            ->where('packagingInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('packagingInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($inspection === null || empty($inspection)) {
            $inspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\PackagingInspection();
            $inspection->setTanggalInspeksi($tanggalInspeksi);
            $inspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $inspection->setStaffQc($staffQc);
        }

        return $inspection;
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
        include('view/add_general_inspection_packaging.phtml');
        $this->templateManager->renderFooter();
    }
}
