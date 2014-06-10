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

    protected $departmentHelper;

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
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department(
            $this->serviceManager,
            $this->entityManager
        );
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection'));
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
            $dailyInspection = $this->findDailyInspection($tanggalInspeksi, $params['group'], $params['lokasi']);
            $this->addSessionData('dailyInspection', $dailyInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('dailyInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $lootRange = $this->entityManager->createQueryBuilder()
                ->select('lotRange')
                ->from('WWII\Domain\Erp\QualityControl\GeneralInspection\LotRange', 'lotRange')
                ->leftJoin('lotRange.level', 'level')
                ->where('level.code = :level')
                    ->setParameter('level', $params['level'])
                ->andWhere('lotRange.minLot <= :minLot')
                    ->setParameter('minLot', $params['jumlahLot'])
                ->andWhere('lotRange.maxLot >= :maxLot')
                    ->setParameter('maxLot', $params['jumlahLot'])
                ->orderBy('lotRange.minLot', 'asc')
                ->getQuery()
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getOneOrNullResult();

            $category = $lootRange->getCategory();

            $acceptanceIndex = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\AcceptanceIndex')
                ->findOneByCode($params['acceptanceIndex']);

            $acceptanceLimit = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\AcceptanceLimit')
                ->findOneBy(array('category' => $category, 'acceptanceIndex' => $acceptanceIndex));

            $item = new \WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspectionItem();
            $item->setKodeProduk($params['kodeProduk']);
            $item->setNamaProduk($params['namaProduk']);
            $item->setInspectionLevel($params['level']);
            $item->setAcceptanceIndex($params['acceptanceIndex']);
            $item->setJumlahLot($params['jumlahLot']);
            $item->setJumlahInspeksi($acceptanceLimit->getSampleSize());
            $item->setJumlahItemKainTergores($params['jumlahItemKainTergores']);
            $item->setJumlahItemTidakPresisi($params['jumlahItemTidakPresisi']);
            $item->setJumlahItemSalahPosisiLubang($params['jumlahItemSalahPosisiLubang']);
            $item->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
            $item->setJumlahItemTergores($params['jumlahItemTergores']);
            $item->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
            $item->setJumlahItemStrukturLonggar($params['jumlahItemStrukturLonggar']);
            $item->setJumlahItemCoverTerpotong($params['jumlahItemCoverTerpotong']);
            $item->setJumlahItemRetak($params['jumlahItemRetak']);
            $item->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
            $item->setJumlahItemPakuKeluar($params['jumlahItemPakuKeluar']);
            $item->setJumlahItemLemDegumming($params['jumlahItemLemDegumming']);
            $item->setJumlahItemGap($params['jumlahItemGap']);
            $item->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);
            $item->setJumlahItemKekurangan($params['jumlahItemKekurangan']);

            $dailyInspection = $this->getSessionData('dailyInspection');

            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $waktuInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                . ' ' . $params['waktu'] . ':00'
            );
            $time = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspectionTime')
                ->findOneBy(array(
                    'dailyInspection' => $dailyInspection->getId(),
                    'waktuInspeksi' => $waktuInspeksi
                ));

            if ($time == null) {
                $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspectionTime();
                $time->setWaktuInspeksi($waktuInspeksi);
                $time->addDailyInspectionItem($item);
                $dailyInspection->addDailyInspectionTime($time);
            } else {
                foreach ($dailyInspection->getDailyInspectionTime() as $dailyInspectionTime) {
                    if ($dailyInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                        $dailyInspectionTime->addDailyInspectionItem($item);
                        break;
                    }
                }
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('dailyInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $dailyInspection = $this->getSessionData('dailyInspection');

            if ($dailyInspection->getId() !== null) {
                $tmpDailyInspectionTimes = array();
                $tmpDailyInspectionItems = array();

                foreach ($dailyInspection->getDailyInspectionTime() as $dailyInspectionTime) {
                    if ($dailyInspectionTime->getId() === null) {
                        $tmpDailyInspectionTimes[] = $dailyInspectionTime;
                        $dailyInspection->removeDailyInspectionTime($dailyInspectionTime);
                    } else {
                        foreach ($dailyInspectionTime->getDailyInspectionItem() as $dailyInspectionItem) {
                            if ($dailyInspectionItem->getId() === null) {
                                $tmpDailyInspectionItems[$dailyInspectionTime->getId()][] = $dailyInspectionItem;
                                $dailyInspectionTime->removeDailyInspectionItem($dailyInspectionItem);
                            }
                        }
                    }
                }

                $dailyInspection = $this->entityManager->merge($dailyInspection);

                foreach ($tmpDailyInspectionItems as $key => $items) {
                    foreach ($dailyInspection->getDailyInspectionTime() as $dailyInspectionTime) {
                        if ($dailyInspectionTime->getId() == $key) {
                            foreach ($items as $item) {
                                $dailyInspectionTime->addDailyInspectionItem($item);
                            }
                            break;
                        }
                    }
                }

                foreach ($tmpDailyInspectionTimes as $time) {
                    $dailyInspection->addDailyInspectionTime($time);
                }
            } else {
                echo '<pre>';
                var_dump($dailyInspection);
                echo '</pre>';
            }

            $this->entityManager->persist($dailyInspection);
            $this->entityManager->flush();

            $this->routeManager->redirect(array(
                'action' => 'report_general_inspection_print',
                'key' => $dailyInspection->getId(),
                'print' => 1
            ));
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
                } elseif ($this->departmentHelper->findOneByNama($params['group']) === null) {
                    $errorMessages['group'] = 'tidak ditemukan';
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
        }

        return $errorMessages;
    }

    protected function findDailyInspection(\DateTime $tanggalInspeksi, $group, $lokasi)
    {
        $dailyInspection = $this->entityManager->createQueryBuilder()
            ->select('dailyInspection')
            ->from('WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspection', 'dailyInspection')
            ->leftJoin('dailyInspection.dailyInspectionTime', 'dailyInspectionTime')
            ->leftJoin('dailyInspectionTime.dailyInspectionItem', 'dailyInspectionItem')
            ->where('dailyInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('dailyInspection.group = :group')
                ->setParameter('group', $group)
            ->andWhere('dailyInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($dailyInspection === null || empty($dailyInspection)) {
            $dailyInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspection();
            $dailyInspection->setTanggalInspeksi($tanggalInspeksi);
            $dailyInspection->setGroup($group);
            $dailyInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[1];
            $dailyInspection->setStaffQc($staffQc);
        }

        return $dailyInspection;
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
        $departmentList = $this->departmentHelper->getDepartmentList();
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
