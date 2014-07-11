<?php

namespace WWII\Application\Erp\QualityControl;

class QualityControlController extends \WWII\Controller\AbstractController
{
    public function indexQualityControlAction()
    {
        //
    }

    public function addGeneralInspectionAssemblingAction()
    {
        $action = new AddGeneralInspectionAssemblingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionFinishingAction()
    {
        $action = new AddGeneralInspectionFinishingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionPackagingAction()
    {
        $action = new AddGeneralInspectionPackagingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionWhitewoodAction()
    {
        $action = new AddGeneralInspectionWhitewoodAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addGeneralInspectionPembahananPanelAction()
    {
        $action = new AddGeneralInspectionPembahananPanelAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionAssemblingAction()
    {
        $action = new ReportGeneralInspectionAssemblingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionFinishingAction()
    {
        $action = new ReportGeneralInspectionFinishingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionPackagingAction()
    {
        $action = new ReportGeneralInspectionPackagingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionWhitewoodAction()
    {
        $action = new ReportGeneralInspectionWhitewoodAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionPembahananPanelAction()
    {
        $action = new ReportGeneralInspectionPembahananPanelAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionAssemblingPrintAction()
    {
        $action = new ReportGeneralInspectionAssemblingPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionFinishingPrintAction()
    {
        $action = new ReportGeneralInspectionFinishingPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionPackagingPrintAction()
    {
        $action = new ReportGeneralInspectionPackagingPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionWhitewoodPrintAction()
    {
        $action = new ReportGeneralInspectionWhitewoodPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionPembahananPanelPrintAction()
    {
        $action = new ReportGeneralInspectionPembahananPanelPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportGeneralInspectionAssemblingGraphAction()
    {
        $action = new ReportGeneralInspectionAssemblingGraphAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionFinishingGraphAction()
    {
        $action = new ReportGeneralInspectionFinishingGraphAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionPackagingGraphAction()
    {
        $action = new ReportGeneralInspectionPackagingGraphAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionWhitewoodGraphAction()
    {
        $action = new ReportGeneralInspectionWhitewoodGraphAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGeneralInspectionPembahananPanelGraphAction()
    {
        $action = new ReportGeneralInspectionPembahananPanelGraphAction($this->serviceManager, $this->entityManager);
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
