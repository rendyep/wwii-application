<?php

namespace WWII\Application\Erp\QualityControl;

class ReportGeneralInspectionAssemblingPrintAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

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
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->inspectionStatusHelper = new \WWII\Common\Helper\Collection\QualityControl\InspectionStatus(
            $this->serviceManager,
            $this->entityManager
        );
    }

    public function dispatch($params)
    {
        $result = $this->dispatchOutput($params);

        $this->render($result);
    }

    public function dispatchOutput($params)
    {
        $result = $this->entityManager
            ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\AssemblingInspection')
            ->findOneById($this->routeManager->getKey());

        return array(
            'params' => $params,
            'data' => $result
        );
    }

    public function render(array $result = null)
    {
        if (! empty($result)) {
            extract($result);
        }

        include('/view/report_general_inspection_assembling_print.phtml');
    }
}
