<?php

namespace WWII\Application\Hrd\Payroll;

class PayrollController extends \WWII\Controller\AbstractController
{
    public function indexPayrollAction()
    {
        //
    }

    public function generateMonthlyPayrollAction()
    {
        $action = new GenerateMonthlyPayrollAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportMonthlyPayrollAction()
    {
        $action = new ReportMonthlyPayrollAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function editJamKerjaKaryawanAction()
    {
        $action = new editJamKerjaKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportJamKerjaKaryawanAction()
    {
        $action = new ReportJamKerjaKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function deleteMonthlyPayrollAction()
    {
        $action = new DeleteMonthlyPayrollAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportGajiKaryawanAction()
    {
        $action = new ReportGajiKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportFormPajakAction()
    {
        $action = new ReportFormPajakAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
