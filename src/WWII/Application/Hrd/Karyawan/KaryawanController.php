<?php

namespace WWII\Application\Hrd\Karyawan;

class KaryawanController extends \WWII\Controller\AbstractController
{
    public function indexKaryawanAction()
    {
        $this->reportFindingAction();
    }

    public function reportKaryawanAction()
    {
        $action = new ReportKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addKaryawanAction()
    {
        $action = new AddKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function addKaryawanFromPelamarAction()
    {
        $action = new AddKaryawanFromPelamarAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editKaryawanAction()
    {
        $action = new EditKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function viewKaryawanAction()
    {
        $action = new ViewKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deleteKaryawanAction()
    {
        $action = new DeleteKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function updateStatusKaryawanAction()
    {
        $action = new UpdateStatusKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function cetakPkwtAction()
    {
        $action = new CetakPkwtAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function inputCutiAction()
    {
        $action = new InputCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
