<?php

namespace WWII\Application\Erp\ItInventory;

class ViewKomputerAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        if (!empty($params['key'])) {
            $this->dispatchView($params);
        } else {
            $this->routeManager->redirect(array('action' => 'report_komputer'));
        }

        $this->render();
    }

    protected function dispatchView($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $komputer = $this->entityManager
                ->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
                ->findOneById($params['key']);

            if ($komputer == null)
            {
                $this->flashMessenger->addMessage('Data yang anda cari tidak ditemukan.');
                $this->routeManager->redirect(array('action' => 'report_komputer'));
            } else {
                $this->data = $komputer;
            }
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        return $errorMessages;
    }

    protected function render()
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/view_komputer.phtml');
        $this->templateManager->renderFooter();
    }
}
