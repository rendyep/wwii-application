<?php

namespace WWII\Application\Erp\QualityControl;

class AddGeneralInspectionWhitewoodAction
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
                $this->routeManager->redirect(array('action' => 'report_general_inspection_whitewood'));
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
            $whitewoodInspection = $this->findWhitewoodInspection($tanggalInspeksi, $params['lokasi']);
            $this->addSessionData('whitewoodInspection', $whitewoodInspection);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('whitewoodInspection')
        );
    }

    protected function dispatchAddItem($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $whitewoodItem = new \WWII\Domain\Erp\QualityControl\GeneralInspection\WhitewoodInspectionItem();
            $whitewoodItem->setCustomer($params['customer']);
            $whitewoodItem->setPO($params['PO']);
            $whitewoodItem->setKodeProduk($params['kodeProduk']);
            $whitewoodItem->setNamaProduk($params['namaProduk']);
            $whitewoodItem->setInspectionLevel($params['level']);
            $whitewoodItem->setAcceptanceIndex($params['acceptanceIndex']);
            $whitewoodItem->setJumlahLot($params['jumlahLot']);
            $whitewoodItem->setJumlahInspeksi($params['jumlahInspeksi']);
            $whitewoodItem->setJumlahItemSalahProses($params['jumlahItemSalahProses']);
            $whitewoodItem->setJumlahItemKualitasBuruk($params['jumlahItemKualitasBuruk']);
            $whitewoodItem->setJumlahItemKualitasTidakBenar($params['jumlahItemKualitasTidakBenar']);
            $whitewoodItem->setJumlahItemPosisiLubangSalah($params['jumlahItemPosisiLubangSalah']);
            $whitewoodItem->setJumlahItemSalahUkuran($params['jumlahItemSalahUkuran']);
            $whitewoodItem->setJumlahItemSalahJenisPisau($params['jumlahItemSalahJenisPisau']);
            $whitewoodItem->setJumlahItemGoresanPisau($params['jumlahItemGoresanPisau']);
            $whitewoodItem->setJumlahItemRobek($params['jumlahItemRobek']);
            $whitewoodItem->setJumlahItemRetak($params['jumlahItemRetak']);
            $whitewoodItem->setJumlahItemMenjadiHitam($params['jumlahItemMenjadiHitam']);
            $whitewoodItem->setJumlahItemSandingBuruk($params['jumlahItemSandingBuruk']);
            $whitewoodItem->setJumlahItemGoresanTekanan($params['jumlahItemGoresanTekanan']);
            $whitewoodItem->setJumlahItemPakuKeluar($params['jumlahItemPakuKeluar']);
            $whitewoodItem->setJumlahItemAssemblyBuruk($params['jumlahItemAssemblyBuruk']);
            $whitewoodItem->setJumlahItemPerbaikanBuruk($params['jumlahItemPerbaikanBuruk']);
            $whitewoodItem->setJumlahItemDegumming($params['jumlahItemDegumming']);
            $whitewoodItem->setJumlahItemKelebihanLem($params['jumlahItemKelebihanLem']);
            $whitewoodItem->setJumlahItemSuhuTerlaluTinggi($params['jumlahItemSuhuTerlaluTinggi']);
            $whitewoodItem->setJumlahItemBurukLainnya($params['jumlahItemBurukLainnya']);

            $whitewoodInspection = $this->getSessionData('whitewoodInspection');

            $arrayTanggalInspeksi = explode('/', $params['tanggalInspeksi']);
            $waktuInspeksi = new \DateTime(
                $arrayTanggalInspeksi[2]. '-' . $arrayTanggalInspeksi[1] . '-' . $arrayTanggalInspeksi[0]
                . ' ' . $params['waktu']
            );
            $time = $this->entityManager
                ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\WhitewoodInspectionTime')
                ->findOneBy(array(
                    'whitewoodInspection' => $whitewoodInspection->getId(),
                    'waktuInspeksi' => $waktuInspeksi
                ));

            foreach ($whitewoodInspection->getWhitewoodInspectionTime() as $tmpTime) {
                if ($tmpTime->getWaktuInspeksi() == $waktuInspeksi) {
                    $time = $tmpTime;
                }
            }

            if ($time == null) {
                $time = new \WWII\Domain\Erp\QualityControl\GeneralInspection\WhitewoodInspectionTime();
                $time->setWaktuInspeksi($waktuInspeksi);
                $time->addWhitewoodInspectionItem($whitewoodItem);
                $whitewoodInspection->addWhitewoodInspectionTime($time);
            } else {
                foreach ($whitewoodInspection->getWhitewoodInspectionTime() as $whitewoodInspectionTime) {
                    if ($whitewoodInspectionTime->getWaktuInspeksi() == $time->getWaktuInspeksi()) {
                        $whitewoodInspectionTime->addWhitewoodInspectionItem($whitewoodItem);
                    }
                }
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('whitewoodInspection')
        );
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $whitewoodInspection = $this->getSessionData('whitewoodInspection');

            if ($whitewoodInspection->getId() !== null) {
                $tmpWhitewoodInspectionTimes = array();
                $tmpWhitewoodInspectionItems = array();

                foreach ($whitewoodInspection->getWhitewoodInspectionTime() as $whitewoodInspectionTime) {
                    if ($whitewoodInspectionTime->getId() === null) {
                        $tmpWhitewoodInspectionTimes[] = $whitewoodInspectionTime;
                        $whitewoodInspection->removeWhitewoodInspectionTime($whitewoodInspectionTime);
                    } else {
                        foreach (
                            $whitewoodInspectionTime->getWhitewoodInspectionItem() as $whitewoodInspectionItem
                        ) {
                            if ($whitewoodInspectionItem->getId() === null) {
                                $tmpWhitewoodInspectionItems[$whitewoodInspectionTime->getId()][] =
                                    $whitewoodInspectionItem;
                                $whitewoodInspectionTime->removeWhitewoodInspectionItem(
                                    $whitewoodInspectionItem
                                );
                            }
                        }
                    }
                }

                $whitewoodInspection = $this->entityManager->merge($whitewoodInspection);

                foreach ($tmpWhitewoodInspectionTimes as $tmpWhitewoodInspectionTime) {
                    $whitewoodInspection->addWhitewoodInspectionTime($whitewoodInspectionTime);
                }

                foreach ($tmpWhitewoodInspectionItems as $key => $tmpWhitewoodInspectionItem) {
                    foreach ($whitewoodInspection->getWhitewoodInspectionTime() as $whitewoodInspectionTime) {
                        if ($whitewoodInspectionTime->getId() == $key) {
                            foreach ($tmpWhitewoodInspectionItem as $item) {
                                $whitewoodInspectionTime->addWhitewoodInspectionItem($item);
                            }
                        }
                    }
                }
            }

            $this->entityManager->persist($whitewoodInspection);
            $this->entityManager->flush();

            $this->routeManager->redirect(array(
                'action' => 'report_general_inspection_whitewood_print',
                'key' => $whitewoodInspection->getId(),
                'print' => 1
            ));
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $this->getSessionData('whitewoodInspection')
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

                if ($params['jumlahItemSalahProses'] == '') {
                    $errorMessages['jumlahItemSalahProses'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKualitasBuruk'] == '') {
                    $errorMessages['jumlahItemKualitasBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemKualitasTidakBenar'] == '') {
                    $errorMessages['jumlahItemKualitasTidakBenar'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPosisiLubangSalah'] == '') {
                    $errorMessages['jumlahItemPosisiLubangSalah'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahUkuran'] == '') {
                    $errorMessages['jumlahItemSalahUkuran'] = 'harus berupa angka';
                }

                if ($params['jumlahItemSalahJenisPisau'] == '') {
                    $errorMessages['jumlahItemSalahJenisPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemGoresanPisau'] == '') {
                    $errorMessages['jumlahItemGoresanPisau'] = 'harus berupa angka';
                }

                if ($params['jumlahItemRobek'] == '') {
                    $errorMessages['jumlahItemRobek'] = 'harus berupa angka';
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

                if ($params['jumlahItemGoresanTekanan'] == '') {
                    $errorMessages['jumlahItemGoresanTekanan'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPakuKeluar'] == '') {
                    $errorMessages['jumlahItemPakuKeluar'] = 'harus berupa angka';
                }

                if ($params['jumlahItemAssemblyBuruk'] == '') {
                    $errorMessages['jumlahItemAssemblyBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemPerbaikanBuruk'] == '') {
                    $errorMessages['jumlahItemPerbaikanBuruk'] = 'harus berupa angka';
                }

                if ($params['jumlahItemDegumming'] == '') {
                    $errorMessages['jumlahItemDegumming'] = 'harus berupa angka';
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

                break;
        }

        return $errorMessages;
    }

    protected function findWhitewoodInspection(\DateTime $tanggalInspeksi, $lokasi)
    {
        $whitewoodInspection = $this->entityManager->createQueryBuilder()
            ->select('whitewoodInspection')
            ->from(
                'WWII\Domain\Erp\QualityControl\GeneralInspection\WhitewoodInspection',
                'whitewoodInspection'
            )
            ->leftJoin(
                'whitewoodInspection.whitewoodInspectionTime',
                'whitewoodInspectionTime'
            )
            ->leftJoin(
                'whitewoodInspectionTime.whitewoodInspectionItem',
                'whitewoodInspectionItem'
            )
            ->where('whitewoodInspection.tanggalInspeksi = :tanggalInspeksi')
                ->setParameter('tanggalInspeksi', $tanggalInspeksi)
            ->andWhere('whitewoodInspection.lokasi = :lokasi')
                ->setParameter('lokasi', $lokasi)
            ->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($whitewoodInspection === null || empty($whitewoodInspection)) {
            $whitewoodInspection = new \WWII\Domain\Erp\QualityControl\GeneralInspection\WhitewoodInspection();
            $whitewoodInspection->setTanggalInspeksi($tanggalInspeksi);
            $whitewoodInspection->setLokasi($lokasi);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $staffQc = $loginSession[3];
            $whitewoodInspection->setStaffQc($staffQc);
        }

        return $whitewoodInspection;
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
        include('view/add_general_inspection_whitewood.phtml');
        $this->templateManager->renderFooter();
    }
}
