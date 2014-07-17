<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionAssemblingAction
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
                $this->routeManager->redirect(array('action' => 'report_general_$assemblingInspection_assembling'));
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
            $$assemblingInspection = $this->findAssemblingInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('assemblingInspection', $$assemblingInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('assemblingInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
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

            $params['waktu'] = '';
            $params['kodeProduk'] = '';
            $params['namaProduk'] = '';
            $params['level'] = '';
            $params['acceptanceIndex'] = '';
            $params['jumlahInspeksi'] = 0;
            $params['jumlahItemKainTergores'] = 0;
            $params['jumlahItemTidakPresisi'] = 0;
            $params['jumlahItemSalahPosisiLubang'] = 0;
            $params['jumlahItemSalahUkuran'] = 0;
            $params['jumlahItemTergores'] = 0;
            $params['jumlahItemKelebihanLem'] = 0;
            $params['jumlahItemStrukturLonggar'] = 0;
            $params['jumlahItemCoverTerpotong'] = 0;
            $params['jumlahItemRetak'] = 0;
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
            'data' => $this->getSessionData('assemblingInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
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
                'action' => 'report_general_inspection_assembling_print',
                'group' => lcfirst($params['group']),
                'key' => $assemblingInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('assemblingInspection')
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

    protected function findAssemblingInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $$assemblingInspection = $this->entityManager->createQueryBuilder()
            ->select('assemblingInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspection',
                'assemblingInspection'
            )
            ->leftJoin(
                'assemblingInspection.assemblingInspectionTime',
                'assemblingInspectionTime'
            )
            ->leftJoin(
                'assemblingInspectionTime.assemblingInspectionItem',
                'assemblingInspectionItem'
            )
            ->where('assemblingInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('assemblingInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($$assemblingInspection === null || empty($$assemblingInspection)) {
            $$assemblingInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspection();
            $$assemblingInspection->setTanggalInspeksi($tanggalInspeksi);
            $$assemblingInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $$assemblingInspection->setStaffQc($staffQc);
        }

        return $$assemblingInspection;
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
        include('view/add_general_inspection_assembling.phtml');
        $this->templateManager->renderFooter();
    }
}
