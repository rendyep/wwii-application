<?php

namespace WWII\Application\Hrd\Pelamar;

class ViewPelamarAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $templateManager;

    protected $databaseManager;

    protected $entityManager;

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
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'report_pelamar'));
                break;
        }

        $pelamar = $this->getRequestedModel($this->routeManager->getKey());

        $this->render(array('pelamar' => $pelamar));
    }

    protected function getRequestedModel($id)
    {
        $pelamar = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Pelamar\Pelamar')
            ->findOneById($id);

        if ($pelamar == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        return $pelamar;
    }

    public function render(array $data = array())
    {
        extract($data);

        $this->templateManager->renderHeader();
        include('view/view_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
