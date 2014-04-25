<?php

namespace WWII\Application\Hrd\Pelamar;

class ReportPelamarAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\ActiveDepartment($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        $this->synchronizeSession($params);

        $this->dispatchFilter($params);

        $this->render($params);
    }

    public function dispatchFilter($params)
    {
        if (empty($params)) {
            return;
        }

        $queryKaryawan = $this->entityManager->createQueryBuilder()
            ->select('karyawan.namaLengkap')
            ->from('WWII\Domain\Hrd\Karyawan\Karyawan', 'karyawan');

        $pelamar = $this->entityManager->createQueryBuilder()
            ->select('pelamar')
            ->from('WWII\Domain\Hrd\Pelamar\Pelamar', 'pelamar')
            ->leftJoin('pelamar.detailPelamar', 'detailPelamar')
            ->where('pelamar.namaLengkap NOT IN (' . $queryKaryawan . ')');

        if (!empty($params['tanggalAwal'])) {
            $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
            try {
                $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
                $pelamar->andWhere('detailPelamar.tanggalInterview >= :tanggalAwal')
                    ->setParameter('tanggalAwal', $tanggalAwal->format('Y-m-d'));
            } catch (\Exception $e) {
                $this->errorMessages['tanggal'] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        }

        if (!empty($params['tanggalAkhir'])) {
            $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
            try {
                $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);
                $pelamar->andWhere('detailPelamar.tanggalInterview <= :tanggalAkhir')
                    ->setParameter('tanggalAkhir', $tanggalAkhir->format('Y-m-d'));
            } catch (\Exception $e) {
                $this->errorMessages['tanggal'] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        }

        if (!empty($params['posisi'])) {
            $pelamar->andWhere('detailPelamar.posisi = :posisi')
                ->setParameter('posisi', $params['posisi']);
        }

        if (!empty($params['status'])) {
            $pelamar->andWhere('detailPelamar.status = :status')
                ->setParameter('status', $params['status']);
        }

        if (empty($this->errorMessages)) {
            $this->data = $pelamar->getQuery()->getResult();
        }
    }

    protected function synchronizeSession(&$params)
    {
        $module = $this->routeManager->getModule();
        $controller = $this->routeManager->getController();
        $action = $this->routeManager->getAction();

        if (!isset($this->sessionContainer->{$module})) {
            $this->sessionContainer->{$module} = new \stdClass();
        }

        if (!isset($this->sessionContainer->{$module}->{$controller})) {
            $this->sessionContainer->{$module}->{$controller} = new \stdClass();
        }

        if (!isset($this->sessionContainer->{$module}->{$controller}->{$action})) {
            $this->sessionContainer->{$module}->{$controller}->{$action} = array();
        }

        if (empty($params)) {
            $params = $this->sessionContainer->{$module}->{$controller}->{$action};
        } else {
            $this->sessionContainer->{$module}->{$controller}->{$action} = $params;
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/report_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
