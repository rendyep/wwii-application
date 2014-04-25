<?php

namespace WWII\Application\Error;

class ErrorController extends \WWII\Controller\AbstractController
{
    public function indexAction()
    {
        $action = new IndexAction($this->serviceManager, $this->entityManager);
        $action->dispatch($_GET);
    }
}
