<?php

namespace WWII\Application\Index;

class IndexAction
{
    protected $serviceManager;

    protected $entityManager;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManager $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        $this->render($params);
    }

    protected function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        include('view/index.phtml');
    }
}
