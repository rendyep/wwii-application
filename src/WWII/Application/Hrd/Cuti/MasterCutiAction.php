<?php

namespace WWII\Application\Hrd\Cuti;

class MasterCutiAction
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

        $now = new \DateTime();

        $masterCuti = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->leftJoin('masterCuti.perpanjanganCuti', 'perpanjanganCuti')
            ->leftJoin('masterCuti.child', 'child')
            ->where('(masterCuti.tanggalKadaluarsa > :tanggalKadaluarsaMasterCuti'
                . ' OR perpanjanganCuti.tanggalKadaluarsa > :tanggalKadaluarsaPerpanjanganCuti)')
            ->andWhere('child IS NULL')
            ->orderBy('perpanjanganCuti.tanggalKadaluarsa', 'ASC')
            ->addOrderBy('masterCuti.tanggalKadaluarsa', 'ASC')
            ->setParameter('tanggalKadaluarsaMasterCuti', $now->format('Y-m-d'))
            ->setParameter('tanggalKadaluarsaPerpanjanganCuti', $now->format('Y-m-d'));

        if (!empty($params['departemen'])) {
            $masterCuti->andWhere('masterCuti.departemen = :departemen')
                ->setParameter('departemen', $params['departemen']);
        }

        $page = $this->routeManager->getPage();
        $masterCuti->setfirstResult(($page - 1) * $this->maxItemPerPage);
        $masterCuti->setMaxResults($this->maxItemPerPage);

        if (empty($this->errorMessages)) {
            $this->data = new \WWII\Common\Util\Paginator($this->serviceManager, $masterCuti->getQuery());
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
        include('view/master_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
