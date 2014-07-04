<?php

namespace WWII\Application\Erp\QualityControl;

class QualityControlController extends \WWII\Controller\AbstractController
{
    public function indexQualityControlAction()
    {
        //
    }

    public function reportGeneralInspectionSingleRecordAction()
    {
        $action = new ReportGeneralInspectionSingleRecordAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionSingleRecordPrintAction()
    {
        $action = new ReportGeneralInspectionSingleRecordPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionGraphAction()
    {
        $action = new ReportGeneralInspectionGraphAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionAction()
    {
        $action = new AddGeneralInspectionAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function autocompleteProdukAction()
    {
        $action = new AutocompleteProdukAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function requestSampleSizeAction()
    {
        $action = new RequestSampleSizeAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }
}
