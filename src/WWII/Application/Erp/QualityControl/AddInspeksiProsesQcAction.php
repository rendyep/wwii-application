<?php

namespace WWII\Application\Erp\QualityControl;

class AddInspeksiProsesQcAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Helper\Collection\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        if (empty($params)) {
            $this->clearSession();
        }

        switch (strtoupper($params['btx'])) {
            case 'ADD':
                $this->dispatchAddItem($params);
                break;
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
        }

        $this->render($params);
    }

    protected function dispatchAddItem($params)
    {
        $session = $this->getSession();

        $errorMessages = $this->validateItem($params);

        if (empty($errorMessages)) {
            //
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $arrayTanggal = explode('/', $params['tanggal']);
            $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);
            $inspeksi->setTanggal($tanggal);

            $inspeksi->setGroup($params['group']);
            $inspeksi->setPelaksana($this->getPelaksana());

            //~$this->entityManager->persist($inspeksi);
            //~$this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(null, null, 'report_inspeksi_proses_qc');
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateItem($params)
    {
        $errorMessages = array();

        if ($params['waktu'] == '') {
            $errorMessages['waktu'] = 'harus diisi';
        } else {
            //~try {
            //~} catch (\Exception $e) {
                //~$errorMessages['waktu'] = 'waktu tidak valid (ex. 10:20)';
            //~}
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if ($params['tanggal'] == '') {
            $errorMessages['tanggal'] = 'harus diisi';
        } else {
            $arrayTanggal = explode('/', $params['tanggal']);
            try {
                $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggal'] = 'format tidak valid (ex. 17/03/2014)';
            }
        }

        if ($params['departemen'] = '') {
            $errorMessages['departemen'] = 'harus diisi';
        }

        if ($params['petugas'] = '') {
            $errorMessages['petugas'] = 'harus diisi';
        }

        return $errorMessages;
    }

    protected function getPelaksana()
    {
        $data = explode(',', $_SESSION['arinaSess']);

        if (isset($data[1])) {
            return $data[1];
        } else {
            return null;
        }
    }

    protected function getSession()
    {
        $module = $this->routeManager->getModule();
        $controller = $this->routeManager->getController();

        if (!isset($this->sessionContainer->{$module})) {
            $this->sessionContainer->{$module} = new \stdClass();
        } elseif (!isset($this->sessionContainer->{$module}->{$controller})) {
            $this->sessionContainer->{$module}->{$controller} = null;
        }

        return $this->sessionContainer->{$module}->{$controller};
    }

    protected function clearSession()
    {
        if (isset($this->sessionContainer->{$module})) {
            unset($this->sessionContainer->{$module});
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        include('view/add_inspeksi_proses_qc.phtml');
    }
}
