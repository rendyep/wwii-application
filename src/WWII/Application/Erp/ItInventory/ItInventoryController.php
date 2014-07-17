<?php

namespace WWII\Application\Erp\ItInventory;

class ItInventoryController extends \WWII\Controller\AbstractController
{
    public function indexAction()
    {
        $this->reportKomputerAction();
    }

    public function indexKomputerAction()
    {
        $this->reportKomputerAction($this->serviceManager, $this->entityManager);
    }

    public function addKomputerAction()
    {
        $action = new AddKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editKomputerAction()
    {
        $action = new EditKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function viewKomputerAction()
    {
        $action = new ViewKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function scanBarcodeKomputerAction()
    {
        $action = new ScanBarcodeKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function deleteKomputerAction()
    {
        $action = new DeleteKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportKomputerAction()
    {
        $action = new ReportKomputerAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function indexSistemOperasiAction()
    {
        $this->reportSistemOperasiAction();
    }

    public function addSistemOperasiAction()
    {
        $action = new AddSistemOperasiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editSistemOperasiAction()
    {
        $action = new EditSistemOperasiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deleteSistemOperasiAction()
    {
        $action = new DeleteSistemOperasiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportSistemOperasiAction()
    {
        $action = new ReportSistemOperasiAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function indexPeripheralAction()
    {
        $this->reportPeripheralAction();
    }

    public function addPeripheralAction()
    {
        $action = new AddPeripheralAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editPeripheralAction()
    {
        $action = new EditPeripheralAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deletePeripheralAction()
    {
        $action = new DeletePeripheralAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }

    public function reportPeripheralAction()
    {
        $action = new ReportPeripheralAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
