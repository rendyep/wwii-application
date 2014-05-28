<?php

namespace WWII\Application\Erp\QualityControl;

class QualityControlController extends \WWII\Controller\AbstractController
{
    public function indexQualityControlAction()
    {
        //
    }

    public function reportGeneralInspectionAction()
    {
        $action = new ReportGeneralInspectionAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionAction()
    {
        $action = new AddGeneralInspectionAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
