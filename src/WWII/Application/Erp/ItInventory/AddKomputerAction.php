<?php

namespace WWII\Application\Erp\ItInventory;

class AddKomputerAction
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
        if (!empty($params)) {
            $this->dispatchSimpan($params);
        }

        $this->render($params);
    }

    public function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $komputer = new \WWII\Domain\Erp\ItInventory\Komputer();
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

            if ($params['tahun'] !== '') {
                $komputer->setTahun($params['tahun']);
            }

            $this->entityManager->persist($komputer);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_komputer'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['mac'])) {
            $errorMessages[] = 'Mac Address harus diisi';
        } else {
            $mac = $this->entityManager
                ->getRepository('WWII\Domain\Erp\ItInventory\Komputer')
                ->findOneByMac($params['mac']);

            if ($mac !== null) {
                $errorMessages[] = 'Mac Address sudah ada';
            }
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

        if (! empty($params['tahun']) && ! (! is_int($params['tahun']) || $params['tahun'] <= 0)) {
            $errorMessages[] = 'Tahun harus berupa integer';
        }

        return $errorMessages;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/add_komputer.phtml');
        $this->templateManager->renderFooter();
    }
}
