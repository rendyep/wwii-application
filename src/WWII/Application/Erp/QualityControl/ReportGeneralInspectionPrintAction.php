<?php

namespace WWII\Application\Erp\QualityControl;

class ReportGeneralInspectionPrintAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $inspectionStatusHelper;

    protected $result;

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
        $this->result = $this->dispatchOutput($params);

        $this->render($params);
    }

    public function dispatchOutput($params)
    {
        $id = $this->routeManager->getKey();
        $result = $this->entityManager
            ->getRepository('WWII\Domain\Erp\QualityControl\GeneralInspection\DailyInspection')
            ->findOneById($id);

        return array(
            'data' => $result
        );
    }

    public function render($params)
    {
        extract($this->result);
        include('/view/report_general_inspection_print.phtml');
    }
}
