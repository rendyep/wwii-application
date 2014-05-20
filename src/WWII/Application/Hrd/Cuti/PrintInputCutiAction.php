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
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI':
                $this->routeManager->redirect(array('action' => 'report_cuti'));
                break;
            default:
                $this->dispatchOutput($params);
                break;
        }

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

        $sessionNamespace = 'hrd_cuti_input_cuti';
        unset($this->sessionContainer->{$sessionNamespace});
    }
}
