<?php

namespace WWII\Application\Erp\QualityControl;

class QualityControlController extends \WWII\Controller\AbstractController
{
    public function addInspeksiProsesQcAction()
    {
        $action = new AddInspeksiProsesQcAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }

    public function reportKaryawanAction()
    {
        $action = new ReportInspeksiProsesQcAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_POST);
    }
}
