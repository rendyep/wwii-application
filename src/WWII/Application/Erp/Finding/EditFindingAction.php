<?php

namespace WWII\Application\Erp\Finding;

class EditFindingAction
{
    protected $serviceManager;

    protected $routeManager;

    protected $databaseManager;

    protected $entityManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

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
        switch (strtoupper($params['btx'])) {
            case 'BATAL' :
                $this->routeManager->redirect(array('action' => 'report_finding'));
                break;
            case 'SIMPAN' :
                $this->dispatchSimpan($params);
                break;
            default:
                break;
        }

        $finding = $this->getRequestedModel();
        $params = $this->populateData($finding, $params);

        $this->render($params);
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $finding = $this->getRequestedModel();

            $arrayTanggalKejadian = explode('/', $params['tanggalKejadian']);
            $tanggalKejadian = new \DateTime(
                $arrayTanggalKejadian[2]
                . '-' . $arrayTanggalKejadian[1]
                . '-' . $arrayTanggalKejadian[0]);
            $finding->setTanggal($tanggalKejadian);

            $finding->setKejadian($params['kejadian']);
            $finding->setTindakan($params['tindakan']);

            $this->entityManager->persist($finding);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil direvisi.');
            $this->routeManager->redirect(array('action' => 'report_finding'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if ($params['tanggalKejadian'] == '') {
            $errorMessages['tanggalKejadian'] = 'harus diisi';
        } else {
            $arrayTanggalKejadian = explode('/', $params['tanggalKejadian']);
            try {
                $tanggalKejadian = new \DateTime($arrayTanggalKejadian[2] . '-' . $arrayTanggalKejadian[1] . '-' . $arrayTanggalKejadian[0]);
            } catch(\Exception $e) {
                $this->errorMessages['tanggalKejadian'] = 'format tidak valid (ex. 17/03/2014)';
            }
        }

        if ($params['kejadian'] == '') {
            $errorMessages['kejadian'] = 'harus diisi';
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Erp\Finding\Finding $finding, $params)
    {
        $params = array(
            'tanggalKejadian' => call_user_func_array(function($finding, $params) {
                if (empty($params['tanggalKejadian'])) {
                    if ($finding->getTanggal() !== null) {
                        return $finding->getTanggal()->format('d/m/Y');
                    }
                    return null;
                } else {
                    return $params['tanggalKejadian'];
                }
            }, array($finding, $params)),
            'kejadian' => isset($params['kejadian']) ? $params['kejadian'] : $finding->getKejadian(),
            'tindakan' => isset($params['tindakan']) ? $params['tindakan'] : $finding->getTindakan(),
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $finding = $this->entityManager
            ->getRepository('WWII\Domain\Erp\Finding\Finding')
            ->findOneById($this->routeManager->getKey());

        if ($finding == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(null, null, 'report_finding');
        }

        return $finding;
    }

    protected function isEditable(\WWII\Domain\Erp\Finding\Finding $model)
    {
        return true;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/edit_finding.phtml');
        $this->templateManager->renderFooter();
    }
}
