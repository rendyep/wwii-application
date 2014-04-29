<?php

namespace WWII\Application\Hrd\Cuti;

class DetailMasterCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'master_cuti'));
                break;
        }

        $masterCuti = $this->getRequestedModel();
        $this->data = $masterCuti;

        $this->render($params);
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            //~->leftJoin('masterCuti.pengambilanCuti', 'pengambilanCuti')
            //~->leftJoin('masterCuti.parent', 'parent')
            //~->leftJoin('parent.pengambilanCuti', 'pengambilanCuti2')
            //~->where('pengambilanCuti2.tanggalAwal > parent.tanggalKadaluarsa')
            ->andWhere('masterCuti.id = :id')
            ->setParameter('id', $this->routeManager->getKey())
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'master_cuti'));
        }

        return $model;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/detail_master_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
