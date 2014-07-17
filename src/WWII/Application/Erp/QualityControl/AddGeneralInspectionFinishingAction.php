<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionFinishingAction
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection_finishing'));
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
            $finishingInspection = $this->findFinishingInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('finishingInspection', $finishingInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('finishingInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
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

            $params['waktu'] = '';
            $params['kodeProduk'] = '';
            $params['namaProduk'] = '';
            $params['level'] = '';
            $params['acceptanceIndex'] = '';
            $params['jumlahInspeksi'] = 0;
            $params['jumlahItemTergores'] = 0;
            $params['jumlahItemTerpolusi'] = 0;
            $params['jumlahItemSalahUkuran'] = 0;
            $params['jumlahItemKelebihanLem'] = 0;
            $params['jumlahItemKelebihanCat'] = 0;
            $params['jumlahItemWarna'] = 0;
            $params['jumlahItemBergelembung'] = 0;
            $params['jumlahItemStrukturLonggar'] = 0;
            $params['jumlahItemCoverTerpotong'] = 0;
            $params['jumlahItemArahHorizontal'] = 0;
            $params['jumlahItemSandingBuruk'] = 0;
            $params['jumlahItemPakuKeluar'] = 0;
            $params['jumlahItemLemDegumming'] = 0;
            $params['jumlahItemGap'] = 0;
            $params['jumlahItemBurukLainnya'] = 0;
            $params['jumlahItemKekurangan'] = 0;
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('finishingInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
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
                'action' => 'report_general_inspection_finishing_print',
                'key' => $finishingInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('finishingInspection')
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
        }

        return $errorMessages;
    }

    protected function findFinishingInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $finishingInspection = $this->entityManager->createQueryBuilder()
            ->select('finishingInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\FinishingInspection',
                'finishingInspection'
            )
            ->leftJoin(
                'finishingInspection.finishingInspectionTime',
                'finishingInspectionTime'
            )
            ->leftJoin(
                'finishingInspectionTime.finishingInspectionItem',
                'finishingInspectionItem'
            )
            ->where('finishingInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('finishingInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($finishingInspection === null || empty($finishingInspection)) {
            $finishingInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\FinishingInspection();
            $finishingInspection->setTanggalInspeksi($tanggalInspeksi);
            $finishingInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $finishingInspection->setStaffQc($staffQc);
        }

        return $finishingInspection;
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
        include('view/add_general_inspection_finishing.phtml');
        $this->templateManager->renderFooter();
    }
}
