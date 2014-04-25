<?php

namespace WWII\Application\Erp\ItInventory;

class EditKomputerAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        if (!isset($_GET['key'])) {
            $this->routeManager->redirect(array('action' => 'report_komputer'));
        }

        $komputer = $this->entityManager
            ->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
            ->findOneById($_GET['key']);

        if ($komputer == null) {
            $this->flashMessenger->addMessage('Data yang anda cari tidak ditemukan.');
            $this->routeManager->redirect(array('action' => 'report_komputer'));
        } else {
            $this->data = $komputer;
        }

        if (!empty($params)) {
            $this->dispatchSimpan($params);
        }

        $params = $this->populateData();

        $this->render();
    }

    public function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $komputer = $this->entityManager
                ->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
                ->findOneById($_GET['key']);

            $komputer->setNomorSeri($params['nomorSeri']);
            $komputer->setIp($params['ip']);
            $komputer->setMac($params['mac']);
            $komputer->setCpu($params['cpu']);
            $komputer->setHardDisk($params['hardDisk']);
            $komputer->setRam($params['ram']);
            $komputer->setLcd($params['lcd']);
            $komputer->setOpticalDrive($params['opticalDrive']);
            $komputer->setOs($params['sistemOperasi']);
            $komputer->setEmail($params['email']);
            $komputer->setUser($params['user']);
            $komputer->setUserAccount($params['userAccount']);
            $komputer->setLokasi($params['lokasi']);
            $komputer->setTahun($params['tahun']);

            $this->entityManager->persist($komputer);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_komputer'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function populateData()
    {
        $komputer = $this->entityManager
            ->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
            ->findOneById($_GET['key']);

        $params = array(
            'nomorSeri' => $komputer->getNomorSeri(),
            'ip' => $komputer->getIp(),
            'mac' => $komputer->getMac(),
            'cpu' => $komputer->getCpu(),
            'hardDisk' => $komputer->getHardDisk(),
            'ram' => $komputer->getRam(),
            'lcd' => $komputer->getLcd(),
            'opticalDrive' => $komputer->getOpticalDrive(),
            'sistemOperasi' => $komputer->getOs(),
            'email' => $komputer->getEmail(),
            'user' => $komputer->getUser(),
            'userAccount' => $komputer->getUserAccount(),
            'lokasi' => $komputer->getLokasi(),
            'tahun' => $komputer->getTahun
        );

        return $params;
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['mac'])) {
            $errorMessages[] = 'Mac Address harus diisi';
        }

        if (empty($params['sistemOperasi'])) {
            $errorMessages[] = 'Sistem Operasi harus diisi';
        }

        if (empty($params['user'])) {
            $errorMessages[] = 'User harus diisi';
        }

        if (empty($params['lokasi'])) {
            $errorMessages[] = 'Lokasi harus diisi';
        }

        return $errorMessages;
    }

    public function render()
    {
        $errorMessages = $this->errorMessages;
        $data = $this->data;

        $this->templateManager->renderHeader();
        include('view/edit_komputer.phtml');
        $this->templateManager->renderFooter();
    }
}
