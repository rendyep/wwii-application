<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionAction
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection_single_record'));
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
            $inspection = $this->findInspection($tanggalInspeksi, $params['group'], $params['lokasi']);
            $this->addSessionData(lcfirst($params['group']) . 'Inspection', $inspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData(lcfirst($params['group']) . 'Inspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            switch (strtoupper($params['group'])) {
                case 'ASSEMBLING':
                    $assemblingItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspectionItem();
                    $assemblingItem->setKodeProduk($params['kodeProduk']);
                    $assemblingItem->setNamaProduk($params['namaProduk']);
                    $assemblingItem->setInspectionLevel($params['level']);
                    $assemblingItem->setAcceptanceIndex($params['acceptanceIndex']);
                    $assemblingItem->setJumlahLot($params['jumlahLot']);
                    $assemblingItem->setJumlahInspeksi($params['jumlahInspeksi']);
                    $assemblingItem->setJumlahItemKainTergores($params['jumlahItemKainTergores']);
                    $assemblingItem->setJumlahItemTidakPresisi($params['jumlahItemTidakPresisi']);
                    $assemblingItem->setJumlahItemSalahPosisiLubang($params['jumlahItemSalahPosisiLubang']);
                    $assemblingItem->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
                    $assemblingItem->setJumlahItemTergores($params['jumlahItemTergores']);
                    $assemblingItem->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
                    $assemblingItem->setJumlahItemStrukturLonggar($params['jumlahItemStrukturLonggar']);
                    $assemblingItem->setJumlahItemCoverTerpotong($params['jumlahItemCoverTerpotong']);
                    $assemblingItem->setJumlahItemRetak($params['jumlahItemRetak']);
                    $assemblingItem->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
                    $assemblingItem->setJumlahItemPakuKeluar($params['jumlahItemPakuKeluar']);
                    $assemblingItem->setJumlahItemLemDegumming($params['jumlahItemLemDegumming']);
                    $assemblingItem->setJumlahItemGap($params['jumlahItemGap']);
                    $assemblingItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);
                    $assemblingItem->setJumlahItemKekurangan($params['jumlahItemKekurangan']);

                    $assemblingInspection = $this->getSessionData('assemblingInspection');

                    $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
                    $waktuInspeksi = new \DateTime(
                        $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                        . ' ' . $params['waktu']
                    );
                    $time = $this->entityManager
                        ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspectionTime')
                        ->findOneBy(array(
                            'assemblingInspection' => $assemblingInspection->getId(),
                            'waktuInspeksi' => $waktuInspeksi
                        ));

                    foreach ($assemblingInspection->getAssemblingInspectionTime() as $tmpTime) {
                        if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                            $time = $tmpTime;
                        }
                    }

                    if ($time == null) {
                        $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspectionTime();
                        $time->setWaktuInspeksi($waktuInspeksi);
                        $time->addAssemblingInspectionItem($assemblingItem);
                        $assemblingInspection->addAssemblingInspectionTime($time);
                    } else {
                        foreach ($assemblingInspection->getAssemblingInspectionTime() as $assemblingInspectionTime) {
                            if ($assemblingInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                                $assemblingInspectionTime->addAssemblingInspectionItem($assemblingItem);
                            }
                        }
                    }

                    break;
                case 'FINISHING':
                    $finishingItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\FinishingInspectionItem();
                    $finishingItem->setKodeProduk($params['kodeProduk']);
                    $finishingItem->setNamaProduk($params['namaProduk']);
                    $finishingItem->setInspectionLevel($params['level']);
                    $finishingItem->setAcceptanceIndex($params['acceptanceIndex']);
                    $finishingItem->setJumlahLot($params['jumlahLot']);
                    $finishingItem->setJumlahInspeksi($params['jumlahInspeksi']);
                    $finishingItem->setJumlahItemTergores($params['jumlahItemTergores']);
                    $finishingItem->setJumlahItemTerpolusi($params['jumlahItemTerpolusi']);
                    $finishingItem->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
                    $finishingItem->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
                    $finishingItem->setJumlahItemKelebihanCat($params['jumlahItemKelebihanCat']);
                    $finishingItem->setJumlahItemWarna($params['jumlahItemWarna']);
                    $finishingItem->setJumlahItemBergelembung($params['jumlahItemBergelembung']);
                    $finishingItem->setJumlahItemStrukturLonggar($params['jumlahItemStrukturLonggar']);
                    $finishingItem->setJumlahItemCoverTerpotong($params['jumlahItemCoverTerpotong']);
                    $finishingItem->setJumlahItemArahHorizontal($params['jumlahItemArahHorizontal']);
                    $finishingItem->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
                    $finishingItem->setJumlahItemPakuKeluar($params['jumlahItemPakuKeluar']);
                    $finishingItem->setJumlahItemLemDegumming($params['jumlahItemLemDegumming']);
                    $finishingItem->setJumlahItemGap($params['jumlahItemGap']);
                    $finishingItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);
                    $finishingItem->setJumlahItemKekurangan($params['jumlahItemKekurangan']);

                    $finishingInspection = $this->getSessionData('finishingInspection');

                    $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
                    $waktuInspeksi = new \DateTime(
                        $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                        . ' ' . $params['waktu']
                    );
                    $time = $this->entityManager
                        ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\FinishingInspectionTime')
                        ->findOneBy(array(
                            'finishingInspection' => $finishingInspection->getId(),
                            'waktuInspeksi' => $waktuInspeksi
                        ));

                    foreach ($finishingInspection->getFinishingInspectionTime() as $tmpTime) {
                        if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                            $time = $tmpTime;
                        }
                    }

                    if ($time == null) {
                        $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\FinishingInspectionTime();
                        $time->setWaktuInspeksi($waktuInspeksi);
                        $time->addFinishingInspectionItem($finishingItem);
                        $finishingInspection->addFinishingInspectionTime($time);
                    } else {
                        foreach ($finishingInspection->getFinishingInspectionTime() as $finishingInspectionTime) {
                            if ($finishingInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                                $finishingInspectionTime->addFinishingInspectionItem($finishingItem);
                            }
                        }
                    }

                    break;
                case 'PACKAGING':
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

                    break;
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData(lcfirst($params['group']) . 'Inspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            switch (strtoupper($params['group'])) {
                case 'ASSEMBLING':
                    $assemblingInspection = $this->getSessionData('assemblingInspection');

                    if ($assemblingInspection->getId() !== null) {
                        $tmpAssemblingInspectionTimes = array();
                        $tmpAssemblingInspectionItems = array();

                        foreach ($assemblingInspection->getAssemblingInspectionTime() as $assemblingInspectionTime) {
                            if ($assemblingInspectionTime->getId() === null) {
                                $tmpAssemblingInspectionTimes[] = $assemblingInspectionTime;
                                $assemblingInspection->removeAssemblingInspectionTime($assemblingInspectionTime);
                            } else {
                                foreach (
                                    $assemblingInspectionTime->getAssemblingInspectionItem()
                                        as $assemblingInspectionItem
                                ) {
                                    if ($assemblingInspectionItem->getId() === null) {
                                        $tmpAssemblingInspectionItems[$assemblingInspectionTime->getId()][] =
                                            $assemblingInspectionItem;
                                        $assemblingInspectionTime->removeAssemblingInspectionItem(
                                            $assemblingInspectionItem
                                        );
                                    }
                                }
                            }
                        }

                        $assemblingInspection = $this->entityManager->merge($assemblingInspection);

                        foreach ($tmpAssemblingInspectionTimes as $tmpAssemblingInspectionTime) {
                            $assemblingInspection->addAssemblingInspectionTime($assemblingInspectionTime);
                        }

                        foreach ($tmpAssemblingInspectionItems as $key => $tmpAssemblingInspectionItem) {
                            foreach (
                                $assemblingInspection->getAssemblingInspectionTime() as $assemblingInspectionTime
                            ) {
                                if ($assemblingInspectionTime->getId() == $key) {
                                    foreach ($tmpAssemblingInspectionItem as $item) {
                                        $assemblingInspectionTime->addAssemblingInspectionItem($item);
                                    }
                                }
                            }
                        }
                    }

                    $this->entityManager->persist($assemblingInspection);
                    $this->entityManager->flush();

                    $this->routeManager->redirect(array(
                        'action' => 'report_general_inspection_single_record_print',
                        'group' => lcfirst($params['group']),
                        'key' => $params['group'] . ':' . $assemblingInspection->getId(),
                        'print' => 1
                    ));

                    break;
                case 'FINISHING':
                    $finishingInspection = $this->getSessionData('finishingInspection');

                    if ($finishingInspection->getId() !== null) {
                        $tmpFinishingInspectionTimes = array();
                        $tmpFinishingInspectionItems = array();

                        foreach ($finishingInspection->getFinishingInspectionTime() as $finishingInspectionTime) {
                            if ($finishingInspectionTime->getId() === null) {
                                $tmpFinishingInspectionTimes[] = $finishingInspectionTime;
                                $finishingInspection->removeFinishingInspectionTime($finishingInspectionTime);
                            } else {
                                foreach (
                                    $finishingInspectionTime->getDailyInspectionItem() as $finishingInspectionItem
                                ) {
                                    if ($finishingInspectionItem->getId() === null) {
                                        $tmpFinishingInspectionItems[$dailyInspectionTime->getId()][] =
                                            $finishingInspectionItem;
                                        $finishingInspectionTime->removeFinishingInspectionItem(
                                            $finishingInspectionItem
                                        );
                                    }
                                }
                            }
                        }

                        $finishingInspection = $this->entityManager->merge($finishingInspection);

                        foreach ($tmpFinishingInspectionTimes as $tmpFinishingInspectionTime) {
                            $finishingInspection->addFinishingInspectionTime($finishingInspectionTime);
                        }

                        foreach ($tmpFinishingInspectionItems as $key => $tmpFinishingInspectionItem) {
                            foreach ($finishingInspection->getFinishingInspectionTime() as $finishingInspectionTime) {
                                if ($finishingInspectionTime->getId() == $key) {
                                    foreach ($tmpFinishingInspectionItem as $item) {
                                        $finishingInspectionTime->addFinishingInspectionItem($item);
                                    }
                                }
                            }
                        }
                    }

                    $this->entityManager->persist($finishingInspection);
                    $this->entityManager->flush();

                    $this->routeManager->redirect(array(
                        'action' => 'report_general_inspection_single_record_print',
                        'key' => $params['group'] . ':' . $finishingInspection->getId(),
                        'print' => 1
                    ));

                    break;
                case 'PACKAGING':
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
                        'action' => 'report_general_inspection_single_record_print',
                        'group' => lcfirst($params['group']),
                        'key' => $params['group'] . ':' . $packagingInspection->getId(),
                        'print' => 1
                    ));

                    break;
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('dailyInspection')
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

                if ($params['group'] == '') {
                    $errorMessages['group'] = 'harus dipilih';
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

                switch (strtoupper($params['group'])) {
                    case 'ASSEMBLING':
                        if ($params['jumlahItemKainTergores'] == '') {
                            $errorMessages['jumlahItemKainTergores'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemTidakPresisi'] == '') {
                            $errorMessages['jumlahItemTidakPresisi'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemSalahPosisiLubang'] == '') {
                            $errorMessages['jumlahItemSalahPosisiLubang'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemSalahUkuran'] == '') {
                            $errorMessages['jumlahItemSalahUkuran'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemTergores'] == '') {
                            $errorMessages['jumlahItemTergores'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemKelebihanLem'] == '') {
                            $errorMessages['jumlahItemKelebihanLem'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemStrukturLonggar'] == '') {
                            $errorMessages['jumlahItemStrukturLonggar'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemCoverTerpotong'] == '') {
                            $errorMessages['jumlahItemCoverTerpotong'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemRetak'] == '') {
                            $errorMessages['jumlahItemRetak'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemSandingBuruk'] == '') {
                            $errorMessages['jumlahItemSandingBuruk'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemPakuKeluar'] == '') {
                            $errorMessages['jumlahItemPakuKeluar'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemLemDegumming'] == '') {
                            $errorMessages['jumlahItemLemDegumming'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemGap'] == '') {
                            $errorMessages['jumlahItemGap'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemBurukLainnya'] == '') {
                            $errorMessages['jumlahItemBurukLainnya'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemKekurangan'] == '') {
                            $errorMessages['jumlahItemKekurangan'] = 'harus berupa angka';
                        }

                        break;
                    case 'FINISHING':
                        if ($params['jumlahItemTergores'] == '') {
                            $errorMessages['jumlahItemTergores'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemTerpolusi'] == '') {
                            $errorMessages['jumlahItemTerpolusi'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemSalahUkuran'] == '') {
                            $errorMessages['jumlahItemSalahUkuran'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemKelebihanLem'] == '') {
                            $errorMessages['jumlahItemKelebihanLem'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemKelebihanCat'] == '') {
                            $errorMessages['jumlahItemKelebihanCat'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemWarna'] == '') {
                            $errorMessages['jumlahItemWarna'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemBergelembung'] == '') {
                            $errorMessages['jumlahItemBergelembung'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemStrukturLonggar'] == '') {
                            $errorMessages['jumlahItemStrukturLonggar'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemCoverTerpotong'] == '') {
                            $errorMessages['jumlahItemCoverTerpotong'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemArahHorizontal'] == '') {
                            $errorMessages['jumlahItemArahHorizontal'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemSandingBuruk'] == '') {
                            $errorMessages['jumlahItemSandingBuruk'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemPakuKeluar'] == '') {
                            $errorMessages['jumlahItemPakuKeluar'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemLemDegumming'] == '') {
                            $errorMessages['jumlahItemLemDegumming'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemGap'] == '') {
                            $errorMessages['jumlahItemGap'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemBurukLainnya'] == '') {
                            $errorMessages['jumlahItemBurukLainnya'] = 'harus berupa angka';
                        }

                        if ($params['jumlahItemKekurangan'] == '') {
                            $errorMessages['jumlahItemKekurangan'] = 'harus berupa angka';
                        }

                        break;
                    case 'PACKAGING':
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
                break;
        }

        return $errorMessages;
    }

    protected function findInspection(\DateTime $tanggalInspeksi, $group, $lokasi)
    {
        $inspection = $this->entityManager->createQueryBuilder()
            ->select(lcfirst($group) . 'Inspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\\' . ucfirst($group) . 'Inspection',
                lcfirst($group) . 'Inspection'
            )
            ->leftJoin(
                lcfirst($group) . 'Inspection.' . lcfirst($group) . 'InspectionTime',
                lcfirst($group) . 'InspectionTime'
            )
            ->leftJoin(
                lcfirst($group) . 'InspectionTime.' . lcfirst($group) . 'InspectionItem',
                lcfirst($group) . 'InspectionItem'
            )
            ->where(lcfirst($group) . 'Inspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere(lcfirst($group) . 'Inspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($inspection === null || empty($inspection)) {
            $domain = '\WWII\Domain\Erp\QualityControl\GeneralInspection\\' . ucfirst($group) . 'Inspection';
            $inspection = new $domain();
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
        include('view/add_general_inspection.phtml');
        $this->templateManager->renderFooter();
    }
}
