<?php

namespace WWII\Application\Erp\Finding;

class ReportFindingAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $data;

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

        if ($params['range'] == 'date') {
            $arrayTanggal = explode('/', $params['tanggal']);
            try {
                $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);

                $this->data = $this->entityManager
                    ->getRepository('WWII\Domain\Erp\Finding\Finding')
                    ->findByTanggal($tanggal);
            } catch (\Exception $e) {
                $this->errorMessages[] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        } elseif ($params['range'] == 'month') {
            try {
                $tanggal = new \DateTime($params['tahun'] . '-' . $params['bulan'] . '-01');

                $this->data = $this->entityManager->createQueryBuilder()
                    ->select('finding')
                    ->from('WWII\Domain\Erp\Finding\Finding', 'finding')
                    ->where("finding.tanggal >= '{$tanggal->format('Y-m-d')}'")
                    ->andWhere("finding.tanggal <= '{$tanggal->format('Y-m-t')}'")
                    ->orderBy("finding.tanggal", "DESC")
                    ->getQuery()->getResult();
            } catch (\Exception $e) {
                $this->errorMessages[] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
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

        $this->templateManager->renderHeader();
        include('view/report_finding.phtml');
        $this->templateManager->renderFooter();
    }
}
