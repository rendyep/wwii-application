<?php

namespace WWII\Application\Erp\Finding;

class AddFindingAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_finding'));
                break;
        }

        $this->render($params);
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $finding = new \WWII\Domain\Erp\Finding\Finding();

            $arrayTanggal = explode('/', $params['tanggalKejadian']);
            $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);
            $finding->setTanggal($tanggal);

            $finding->setKejadian($params['kejadian']);
            $finding->setTindakan($params['tindakan']);

            $loginSession = explode(',', $_SESSION['arinaSess']);
            $pelaksana = $loginSession[1];
            $finding->setPelaksana($pelaksana);

            $this->entityManager->persist($finding);
            $this->entityManager->flush();

            if (!empty($_FILES['photos'])) {
                $photos = $this->rearrayFiles($_FILES['photos']);
                $this->savePhotos($photos, $finding);
            }

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_finding'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['tanggalKejadian'])) {
            $errorMessages['tanggalKejadian'] = 'harus diisi';
        } else {
            $arrayTanggal = explode('/', $params['tanggalKejadian']);
            try {
                $tanggal = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);
            } catch(\Exception $e) {
                $this->errorMessages['tanggalLahir'] = 'format tidak valid (ex. 17/03/2014)';
            }
        }

        if (empty($params['kejadian'])) {
            $errorMessages['kejadian'] = 'harus diisi';
        }

        return $errorMessages;
    }

    protected function rearrayFiles($photos)
    {
        $array = array();
        $count = count($photos['name']);
        $keys  = array_keys($photos);

        for ($i=0; $i < $count; $i++) {
            foreach ($keys as $key) {
                $array[$i][$key] = $photos[$key][$i];
            }
        }

        return $array;
    }

    protected function savePhotos($photos, \WWII\Domain\Erp\Finding\Finding $finding)
    {
        $i = 0;
        foreach ($photos as $index => $photo) {
            if (!empty($photo['error'])) {
                switch ($photo['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->errorMessages['photos'] = 'Ukuran file terlalu besar';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->errorMessages['photos'] = 'File tidak terupload dengan sempurna';
                        break;
                    default:
                        break;
                }

                return false;
            } else {
                $extension = substr($photo['name'], strrpos($photo['name'], '.') + 1);
                $fileName = $finding->getId() . '_' . $index . '.' . $extension;
                $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
                    . '/images'
                    . '/' . $this->routeManager->getModule()
                    . '/' . $this->routeManager->getController()
                    . '/' . $fileName;

                if (move_uploaded_file($photo['tmp_name'], $filePath)) {
                    $findingPhoto = new \WWII\Domain\Erp\Finding\FindingPhoto();
                    $findingPhoto->setNamaFile($fileName);
                    $finding->addFindingPhoto($findingPhoto);

                    $this->entityManager->persist($finding);
                    $this->entityManager->flush();
                } else {
                    $this->errorMessages['photos'] = 'Tidak ada gambar yang diupload';
                }
            }
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/add_finding.phtml');
        $this->templateManager->renderFooter();
    }
}
