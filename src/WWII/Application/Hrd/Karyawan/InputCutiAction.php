<?php

namespace WWII\Application\Hrd\Karyawan;

class InputCutiAction
{
    protected $serviceManager;

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
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'SIMPAN' :
                $this->dispatchSimpan($params);
                break;
            case 'BATAL' :
                $this->routeManager->redirect(array('action' => 'report_cuti');
                break;
        }

        $this->render($params);
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if ($params['nik'] == '') {
            $errorMessages['nik'] = 'harus diisi';
        }

        if ($params['tanggalAwal'] == '') {
            $errorMessages['tanggalAwal'] = 'harus diisi';
        } else {
            $arrayTanggalAwal = explode('/', $params['tanggalAwal']);
            try {
                $tanggalAwal = new \DateTime($arrayTanggalAwal[2] . '-' . $arrayTanggalAwal[1] . '-' . $arrayTanggalAwal[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalAwal'] = 'format tidak valid (ex. 17/04/2014).';
            }
        }

        if ($params['tanggalAkhir'] == '') {
            $errorMessages['tanggalAkhir'] = 'harus diisi';
        } else {
            $arrayTanggalAkhir = explode('/', $params['tanggalAkhir']);
            try {
                $tanggalAkhir = new \DateTime($arrayTanggalAkhir[2] . '-' . $arrayTanggalAkhir[1] . '-' . $arrayTanggalAkhir[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalAkhir'] = 'format tidak valid (ex. 17/04/2014).';
            }

            if (empty($errorMessages['tanggalAkhir'])) {
                if ($tanggalAwal > $tanggalAkhir) {
                    $errorMessages['tanggalAkhir'] = 'tidak boleh kurang dari tanggal awal';
                }
            }
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        include('view/input_cuti.phtml');
    }
}
