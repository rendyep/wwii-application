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

    public function requestCutiAction()
    {
        $action = new RequestCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function approvisasiCutiAction()
    {
    }

    public function reportCutiAction()
    {
        $action = new ReportCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function dataCutiAction()
    {
        $action = new DataCutiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function autocompleteKaryawanAction()
    {
        $action = new AutocompleteKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }
}
