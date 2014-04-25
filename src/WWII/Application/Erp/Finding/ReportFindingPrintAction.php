<?php

namespace WWII\Application\Erp\Finding;

class ReportFindingPrintAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        $module = $this->routeManager->getModule();
        $controller = $this->routeManager->getController();
        $action = 'report_finding';

        if (isset($this->sessionContainer->{$module}->{$controller}->{$action})) {
            $this->dispatchOutput($this->sessionContainer->{$module}->{$controller}->{$action});
        }

        $this->render($params);
    }

    public function dispatchOutput($params)
    {
        if ($params['range'] == 'date') {
            $arrayTanggal = explode('/', $params['tanggal']);
            try {
                $tanggal = new \DateTime($arrayTanggal[2]
                    . '-' . $arrayTanggal[1]
                    . '-' . $arrayTanggal[0]);
            } catch (Exception $e) {
                $this->errorMessages[] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }

            $this->data = $this->entityManager
                ->getRepository('\WWII\Domain\Erp\Finding\Finding')
                ->findByTanggal($tanggal);
        } elseif ($params['range'] == 'month') {
            try {
                $tanggal = new \DateTime($params['tahun'] . '-' . $params['bulan'] . '-01');
            } catch (Exception $e) {
                $this->errorMessages[] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }

            $this->data = $this->entityManager->createQueryBuilder()
                ->select('finding')
                ->from('WWII\Domain\Erp\Finding\Finding', 'finding')
                ->where("finding.tanggal >= '{$tanggal->format('Y-m-d')}'")
                ->andWhere("finding.tanggal <= '{$tanggal->format('Y-m-t')}'")
                ->getQuery()->getResult();
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->clean();
        include('/view/report_finding_print.phtml');
    }
}
