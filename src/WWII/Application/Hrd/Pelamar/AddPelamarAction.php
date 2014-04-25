<?php

namespace WWII\Application\Hrd\Pelamar;

class AddPelamarAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $departmentHelper;

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\ActiveDepartment($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'SIMPAN':
                $result = $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_pelamar'));
                break;
        }

        $this->render($params);
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pelamar = new \WWII\Domain\Hrd\Pelamar\Pelamar();
            $pelamar->setNamaLengkap($params['namaLengkap']);
            $pelamar->setNamaPanggilan($params['namaPanggilan']);
            $pelamar->setJenisKelamin($params['jenisKelamin']);

            $arrayTanggalLahir = explode('/', $params['tanggalLahir']);
            $tanggalLahir = new \DateTime($arrayTanggalLahir[2] . '-' . $arrayTanggalLahir[1] . '-' . $arrayTanggalLahir[0]);
            $pelamar->setTanggalLahir($tanggalLahir);

            $pelamar->setAgama($params['agama']);
            $pelamar->setStatusPerkawinan($params['statusPerkawinan']);
            $pelamar->setPendidikan($params['pendidikan']);
            $pelamar->setJurusan($params['jurusan']);
            $pelamar->setAlamat($params['alamat']);
            $pelamar->setKota($params['kota']);
            $pelamar->setKodePos($params['kodePos']);
            $pelamar->setTelepon($params['telepon']);
            $pelamar->setPonsel($params['ponsel']);
            $pelamar->setPonselLain($params['ponselLain']);
            $pelamar->setEmail($params['email']);
            $pelamar->setNpwp($params['npwp']);

            $detailPelamar = new \WWII\Domain\Hrd\Pelamar\DetailPelamar();

            if (!empty($params['tanggalInterview'])) {
                $arrayTanggalInterview = explode('/', $params['tanggalInterview']);
                $tanggalInterview = new \DateTime($arrayTanggalInterview[2] . '-' . $arrayTanggalInterview[1] . '-' . $arrayTanggalInterview[0]);
                $detailPelamar->setTanggalInterview($tanggalInterview);
            }

            $detailPelamar->setPosisi($params['posisi']);
            $pelamar->setDetailPelamar($detailPelamar);

            $this->entityManager->persist($pelamar);
            $this->entityManager->flush();

            if (!empty($_FILES['filePelamar'])) {
                $filePelamar = $this->rearrayFiles($_FILES['filePelamar']);
                $this->saveFilePelamar($filePelamar, $pelamar);
            }

            if (!empty($_FILES['photo'])) {
                $this->savePhotoPelamar($_FILES['photo'], $pelamar);
            }

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        } else {
            return array(
                'errorMessages' => $errorMessages,
                'params' => $params,
            );
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if ($params['namaLengkap'] == '') {
            $errorMessages['namaLengkap'] = 'harus diisi';
        }

        if ($params['jenisKelamin'] == '') {
            $errorMessages['jenisKelamin'] = 'harus dipilih';
        }

        if ($params['tanggalLahir'] == '') {
            $errorMessages['tanggalLahir'] = 'harus diisi';
        } else {
            $arrayTanggalLahir = explode('/', $params['tanggalLahir']);
            try {
                $tanggalLahir = new \DateTime($arrayTanggalLahir[2] . '-' . $arrayTanggalLahir[1] . '-' . $arrayTanggalLahir[0]);
            } catch(\Exception $e) {
                $this->errorMessages['tanggalLahir'] = 'format tidak valid (ex. 17/03/2014)';
            }
        }

        if ($params['agama'] == '') {
            $errorMessages['agama'] = 'harus dipilih';
        }

        if ($params['statusPerkawinan'] == '') {
            $errorMessages['statusPerkawinan'] = 'harus diisi';
        }

        if ($params['pendidikan'] == '') {
            $errorMessages['pendidikan'] = 'harus dipilih';
        }

        if ($params['alamat'] == '') {
            $errorMessages['alamat'] = 'harus diisi';
        }

        if ($params['kota'] == '') {
            $errorMessages['kota'] = 'harus diisi';
        }

        if ($params['posisi'] == '') {
            $errorMessages['posisi'] = 'harus dipilih';
        } else {
            $department = $this->departmentHelper->findOneByNama($params['posisi']);

            if ($department == null) {
                $errorMessages['posisi'] = 'tidak valid';
            }
        }

        if ($params['tanggalInterview'] == '') {
            $errorMessages['tanggalInterview'] = 'harus diisi';
         } else {
            $arrayTanggalInterview = explode('/', $params['tanggalInterview']);
            try {
                $tanggalInterview = new \DateTime($arrayTanggalInterview[2] . '-' . $arrayTanggalInterview[1] . '-' . $arrayTanggalInterview[0]);
            } catch(\Exception $e) {
                $this->errorMessages['tanggalInterview'] = 'format tidak valid (ex. 17/03/2014).';
            }
        }

        if (isset($_FILES['filePelamar'])) {
            $filePelamar = $this->rearrayFiles($_FILES['filePelamar']);
            foreach ($filePelamar as $key => $file) {
                if ($file['type'] != 'application/pdf') {
                    $errorMessages['filePelamar'][$key] = 'File ' . ($key+1) . ': format file harus PDF';
                }
            }
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !=  UPLOAD_ERR_NO_FILE) {
            if ($_FILES['photo']['type'] != 'image/jpeg') {
                $errorMessages['photo'] = 'format file harus JPG/JPEG';
            }
        }

        return $errorMessages;
    }

    protected function rearrayFiles($files)
    {
        $array = array();
        $count = count($files['name']);
        $keys  = array_keys($files);

        for ($i=0; $i < $count; $i++) {
            foreach ($keys as $key) {
                $array[$i][$key] = $files[$key][$i];
            }
        }

        return $array;
    }

    protected function saveFilePelamar($files, \WWII\Domain\Hrd\Pelamar\Pelamar $pelamar)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/files/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        foreach ($files as $key => $file) {
            $extension = substr($file['name'], strrpos($file['name'], '.') + 1);
            $fileName = $pelamar->getId() . '_' . $key . '.' . $extension;

            if (move_uploaded_file($file['tmp_name'], $filePath . $fileName)) {
                $filePelamar = new \WWII\Domain\Hrd\Pelamar\FilePelamar();
                $filePelamar->setNamaFile($fileName);
                $filePelamar->setPelamar($pelamar);

                $this->entityManager->persist($filePelamar);
                $this->entityManager->flush();
            }
        }
    }

    protected function savePhotoPelamar($file, \WWII\Domain\Hrd\Pelamar\Pelamar $pelamar)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/images/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        $extension = substr($file['name'], strrpos($file['name'], '.') + 1);
        $fileName = $pelamar->getId() . '.' . $extension;

        if (move_uploaded_file($file['tmp_name'], $filePath . $fileName)) {
            $pelamar->setPhoto($fileName);
            $this->entityManager->persist($pelamar);
            $this->entityManager->flush();
        }
    }

    public function render(array $params = array())
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/add_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
