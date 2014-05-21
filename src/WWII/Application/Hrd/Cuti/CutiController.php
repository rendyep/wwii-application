<?php

namespace WWII\Application\Hrd\Cuti;

class CutiController extends \WWII\Controller\AbstractController
{
    public function indexCutiAction()
    {
        $this->MasterCutiAction();
    }

    public function masterCutiAction()
    {
        $action = new MasterCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function detailMasterCutiAction()
    {
        $action = new DetailMasterCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function printDetailMasterCutiAction()
    {
        $action = new PrintDetailMasterCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportCutiAction()
    {
        $action = new ReportCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function inputCutiAction()
    {
        $action = new InputCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function printInputCutiAction()
    {
        $action = new PrintInputCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editCutiAction()
    {
        $action = new EditCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function viewCutiAction()
    {
        $action = new ViewCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deleteCutiAction()
    {
        $action = new DeleteCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function approvisasiCutiAction()
    {
        $action = new ApprovisasiCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
