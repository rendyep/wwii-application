<?php

namespace WWII\Application\Erp\QualityControl;

class ReportGeneralInspectionAssemblingGraphAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $inspectionStatusHelper;

    protected $errorMessages = array();

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;

        $this->inspectionStatusHelper = new \WWII\Common\Helper\Collection\QualityControl\InspectionStatus(
            $this->serviceManager,
            $this->entityManager
        );
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'RESET':
                $this->clearSessionData();
                break;
            default:
                $session = $this->synchronizeSessionData('params', $params);
                break;
        }

        $result = $this->dispatchFilter($session);

        $this->render($result);
    }

    public function dispatchFilter($params)
    {
        if (! empty($params)) {
            $errorMessages = $this->validateData($params);

            if (empty($errorMessages)) {
                $dailyInspection = $this->entityManager->createQueryBuilder()
                    ->select('assemblingInspection')
                    ->from(
                        'WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspection',
                        'assemblingInspection'
                    );

                $arrayTanggal = explode('/', $params['tanggal']);
                $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1]. '-' . $arrayTanggal[0]);
                $dailyInspection->andWhere('assemblingInspection.tanggalInspeksi = :tanggal')
                    ->setParameter('tanggal', $tanggal->format('Y-m-d'));

                $data = $dailyInspection
                    ->getQuery()
                    ->getResult();
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['tanggal'])) {
            $errorMessages['tanggal'] = 'Harus diisi';
        } else {
            try {
                $arrayTanggal = explode('/', $params['tanggal']);
                $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1]. '-' . $arrayTanggal[0]);
            } catch (\Exception $e) {
                $errorMessages['tanggal'] = 'Format tanggal tidak valid (ex. 17/03/2014).';
            }
        }

        return $errorMessages;
    }

    protected function synchronizeSessionData($name, $data)
    {
        $sessionData = $this->getSessionData($name, $data);

        if (empty($data)) {
            return $sessionData;
        } else {
            return $this->addSessionData($name, $data);
        }
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
        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/report_general_inspection_assembling_graph.phtml');
        $this->templateManager->renderFooter();
    }
}
