<?php

namespace WWII\Application\Erp\Finding;

class FindingController extends \WWII\Controller\AbstractController
{
    public function indexFindingAction()
    {
        $this->ReportFindingAction();
    }

    public function reportFindingAction()
    {
        $action = new ReportFindingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportFindingPrintAction()
    {
        $action = new ReportFindingPrintAction($this->serviceManager, $this->entityManager);
        $action->dispatch(null);
    }

    public function addFindingAction()
    {
        $action = new AddFindingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editFindingAction()
    {
        $action = new EditFindingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function viewFindingAction()
    {
        $action = new ViewFindingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deleteFindingAction()
    {
        $action = new DeleteFindingAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
