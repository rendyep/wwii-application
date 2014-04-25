<?php

namespace WWII\Application\Index;

class IndexController extends \WWII\Controller\AbstractController
{
    public function indexAction()
    {
        $action = new IndexAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }
}
