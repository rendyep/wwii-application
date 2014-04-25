<?php

namespace WWII\Application\Hrd\Pelamar;

class PelamarController extends \WWII\Controller\AbstractController
{
    public function indexPelamarAction()
    {
        $this->reportPelamarAction();
    }

    public function reportPelamarAction()
    {
        $action = new ReportPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addPelamarAction()
    {
        $action = new AddPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editPelamarAction()
    {
        $action = new EditPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function viewPelamarAction()
    {
        $action = new ViewPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deletePelamarAction()
    {
        $action = new DeletePelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function updateStatusPelamarAction()
    {
        $action = new UpdateStatusPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
