<?php

namespace WWII\Application\Hrd\Karyawan;

class ReportKaryawanAction
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

        $karyawan = $this->entityManager->createQueryBuilder()
            ->select('karyawan')
            ->from('WWII\Domain\Hrd\Karyawan\Karyawan', 'karyawan')
            ->leftJoin('karyawan.detailKaryawan', 'detailKaryawan');

        if (!empty($params['nik'])) {
            $karyawan->andWhere('detailKaryawan.nik = :nik')
                ->setParameter('nik', $params['nik']);
        }

        if (!empty($params['departemen'])) {
            $karyawan->andWhere('detailKaryawan.departemen = :departemen')
                ->setParameter('departemen', $params['departemen']);
        }

        if (!empty($params['status'])) {
            $karyawan->andWhere('detailKaryawan.status = :status')
                ->setParameter('status', $params['status']);
        }

        $this->data = $karyawan->getQuery()->getResult();
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
        include('view/report_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
