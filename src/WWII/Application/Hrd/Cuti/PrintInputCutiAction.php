<?php

namespace WWII\Application\Hrd\Cuti;

class PrintInputCutiAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
    }

    public function dispatch($params)
    {
        $this->dispatchOutput($params);

        $this->render($params);
    }

    public function dispatchOutput($params)
    {
        $sessionNamespace = 'hrd_cuti_input_cuti';

        $this->data = $this->sessionContainer->{$sessionNamespace}->data;
    }

    public function render($params)
    {
        $data = $this->data;
        include('/view/print_input_cuti.phtml');
        //~unset($this->sessionContainer->{$sessionNamespace});
    }
}
