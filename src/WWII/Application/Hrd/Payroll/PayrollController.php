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

    public function detailJamKerjaKaryawanAction()
    {
        $action = new DetailJamKerjaKaryawanAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
