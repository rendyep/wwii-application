<?php

namespace WWII\Application\Hrd\Cuti;

class ReportCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $data;

    protected $maxItemPerPage = 20;

    protected $errorMessages = array();

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
        $this->synchronizeSession($params);

        $this->dispatchFilter($params);

        $this->render($params);
    }

    public function dispatchFilter($params)
    {
        if (empty($params)) {
            return;
        }

        $query = $this->entityManager->createQueryBuilder()
            ->select('pengambilanCuti')
            ->from('WWII\Domain\Hrd\Cuti\PengambilanCuti', 'pengambilanCuti')
            ->leftJoin('pengambilanCuti.masterCuti', 'masterCuti')
            ->orderBy('pengambilanCuti.tanggalAwal', 'ASC');

        if (!empty($params['nik'])) {
            $query->andWhere('masterCuti.nik = :nik')
                ->setParameter('nik', $params['nik']);
        }

        if (!empty($params['departemen'])) {
            $query->andWhere('masterCuti.departemen = :departemen')
                ->setParameter('departemen', $params['departemen']);
        }

        if (!empty($params['tanggalAwal'])) {
            $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
            try {
                $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
                $query->andWhere('detailPelamar.tanggalInterview >= :tanggalAwal')
                    ->setParameter('tanggalAwal', $tanggalAwal->format('Y-m-d'));
            } catch (\Exception $e) {
                $this->errorMessages['tanggal'] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        }

        if (!empty($params['tanggalAkhir'])) {
            $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
            try {
                $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);
                $query->andWhere('detailPelamar.tanggalInterview <= :tanggalAkhir')
                    ->setParameter('tanggalAkhir', $tanggalAkhir->format('Y-m-d'));
            } catch (\Exception $e) {
                $this->errorMessages['tanggal'] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        }

        $page = $this->routeManager->getPage();
        $query->setfirstResult(($page - 1) * $this->maxItemPerPage);
        $query->setMaxResults($this->maxItemPerPage);

        if (empty($this->errorMessages)) {
            $this->data = new \WWII\Common\Util\Paginator($this->serviceManager, $query->getQuery());
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
        include('view/report_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
