<?php

namespace WWII\Application\Hrd\Karyawan;

class AddKaryawanAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSql\Department($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'SIMPAN':
                $this->dispatchSimpan($params);
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
            $karyawan = new \WWII\Domain\Hrd\Karyawan\Karyawan();

            $karyawan->setNomorKtp($params['nomorKtp']);
            $karyawan->setNamaLengkap($params['namaLengkap']);
            $karyawan->setNamaPanggilan($params['namaPanggilan']);
            $karyawan->setJenisKelamin($params['jenisKelamin']);
            $karyawan->setTempatLahir($params['tempatLahir']);

            $arrayTanggalLahir = explode('/', $params['tanggalLahir']);
            $tanggalLahir = new \DateTime($arrayTanggalLahir[2] . '-' . $arrayTanggalLahir[1] . '-' . $arrayTanggalLahir[0]);
            $karyawan->setTanggalLahir($tanggalLahir);

            $karyawan->setAgama($params['agama']);
            $karyawan->setStatusPerkawinan($params['statusPerkawinan']);
            $karyawan->setPendidikan($params['pendidikan']);
            $karyawan->setJurusan($params['jurusan']);
            $karyawan->setAlamat($params['alamat']);
            $karyawan->setKota($params['kota']);
            $karyawan->setKodePos($params['kodePos']);
            $karyawan->setTelepon($params['telepon']);
            $karyawan->setPonsel($params['ponsel']);
            $karyawan->setPonselLain($params['ponselLain']);
            $karyawan->setEmail($params['email']);
            $karyawan->setNpwp($params['npwp']);

            $detailKaryawan = new \WWII\Domain\Hrd\Karyawan\DetailKaryawan();

            $detailKaryawan->setNik($params['nik']);
            $detailKaryawan->setJabatan($params['jabatan']);
            $detailKaryawan->setDepartemen($params['departemen']);

            $arrayTanggalAwalKontrakKerja = explode('/', $params['tanggalAwalKontrakKerja']);
            $tanggalAwalKontrakKerja = new \DateTime($arrayTanggalAwalKontrakKerja[2] . '-' . $arrayTanggalAwalKontrakKerja[1] . '-' . $arrayTanggalAwalKontrakKerja[0]);
            $detailKaryawan->setTanggalAwalKontrakKerja($tanggalAwalKontrakKerja);

            $arrayTanggalAkhirKontrakKerja = explode('/', $params['tanggalAkhirKontrakKerja']);
            $tanggalAkhirKontrakKerja = new \DateTime($arrayTanggalAkhirKontrakKerja[2] . '-' . $arrayTanggalAkhirKontrakKerja[1] . '-' . $arrayTanggalAkhirKontrakKerja[0]);
            $detailKaryawan->setTanggalAkhirKontrakKerja($tanggalAkhirKontrakKerja);

            $arrayTanggalMasukKerja = explode('/', $params['tanggalMasukKerja']);
            $tanggalMasukKerja = new \DateTime($arrayTanggalMasukKerja[2] . '-' . $arrayTanggalMasukKerja[1] . '-' . $arrayTanggalMasukKerja[0]);
            $detailKaryawan->setTanggalMasukKerja($tanggalMasukKerja);

            $karyawan->setDetailKaryawan($detailKaryawan);

            $this->entityManager->persist($karyawan);
            $this->entityManager->flush();

            if (!empty($_FILES['photo'])) {
                $this->savePhotoKaryawan($_FILES['photo'], $karyawan);
            }

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if ($params['nik'] == '') {
            $errorMessages['nik'] = 'harus diisi';
        } else {
            $data = $this->entityManager
                ->getRepository('WWII\Domain\Hrd\Karyawan\DetailKaryawan')
                ->findOneByNik($params['nik']);

            if ($data !== null) {
                $errorMessages['nik'] = 'sudah tercatat di data karyawan';
            }
        }

        if ($params['nomorKtp'] == '') {
            $errorMessages['nomorKtp'] = 'harus diisi';
        }

        if ($params['namaLengkap'] == '') {
            $errorMessages['namaLengkap'] = 'harus diisi';
        }

        if ($params['jenisKelamin'] == '') {
            $errorMessages['jenisKelamin'] = 'harus dipilih';
        }

        if ($params['tempatLahir'] == '') {
            $errorMessages['tempatLahir'] = 'harus dipilih';
        }

        if ($params['tanggalLahir'] == '') {
            $errorMessages['tanggalLahir'] = 'harus diisi';
        } else {
            $arrayTanggalLahir = explode('/', $params['tanggalLahir']);
            try {
                $tanggalLahir = new \DateTime($arrayTanggalLahir[2] . '-' . $arrayTanggalLahir[1] . '-' . $arrayTanggalLahir[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalLahir'] = 'format tidak valid (ex. 17/03/2014)';
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

        if ($params['jabatan'] == '') {
            $errorMessages['jabatan'] = 'harus dipilih';
        }

        if ($params['departemen'] == '') {
            $errorMessages['departemen'] = 'harus dipilih';
        } else {
            $department = $this->departmentHelper->findOneByNama($params['departemen']);

            if ($department == null) {
                $errorMessages['departemen'] = 'tidak valid';
            }
        }

        if ($params['status'] == '') {
            $errorMessages['status'] = 'harus dipilih';
        }

        if ($params['tanggalAwalKontrakKerja'] == '') {
            if ($params['status'] == 'kontrak') {
                $errorMessages['tanggalAwalKontrakKerja'] = 'harus diisi';
            }
        } else {
            $arrayTanggalAwalKontrakKerja = explode('/', $params['tanggalAwalKontrakKerja']);
            try {
                $tanggalAwalKontrakKerja = new \DateTime($arrayTanggalAwalKontrakKerja[2] . '-' . $arrayTanggalAwalKontrakKerja[1] . '-' . $arrayTanggalAwalKontrakKerja[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalAwalKontrakKerja'] = 'format tidak valid (ex. 17/03/2014).';
            }
        }

        if ($params['tanggalAkhirKontrakKerja'] == '') {
            if ($params['status'] == 'kontrak') {
                $errorMessages['tanggalAkhirKontrakKerja'] = 'harus diisi';
            }
        } else {
            $arrayTanggalAkhirKontrakKerja = explode('/', $params['tanggalAkhirKontrakKerja']);
            try {
                $tanggalAkhirKontrakKerja = new \DateTime($arrayTanggalAkhirKontrakKerja[2] . '-' . $arrayTanggalAkhirKontrakKerja[1] . '-' . $arrayTanggalAkhirKontrakKerja[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalAkhirKontrakKerja'] = 'format tidak valid (ex. 17/03/2014).';
            }
        }

        if ($params['tanggalMasukKerja'] == '') {
            $errorMessages['tanggalMasukKerja'] = 'harus diisi';
        } else {
            $arrayTanggalMasukKerja = explode('/', $params['tanggalMasukKerja']);
            try {
                $tanggalMasukKerja = new \DateTime($arrayTanggalMasukKerja[2] . '-' . $arrayTanggalMasukKerja[1] . '-' . $arrayTanggalMasukKerja[0]);
            } catch(\Exception $e) {
                $errorMessages['tanggalMasukKerja'] = 'format tidak valid (ex. 17/03/2014).';
            }
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !==  UPLOAD_ERR_NO_FILE) {
            if ($_FILES['photo']['type'] != 'image/jpeg') {
                $errorMessages['photo'] = 'format file harus JPG/JPEG';
            }
        }

        return $errorMessages;
    }

    protected function savePhotoKaryawan($file, \WWII\Domain\Hrd\Karyawan\Karyawan $karyawan)
    {
        $filePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/images/'
            . $this->routeManager->getModule() . '/'
            . $this->routeManager->getController() . '/';

        $extension = substr($file['name'], strrpos($file['name'], '.') + 1);
        $fileName = $karyawan->getId() . '.' . $extension;

        if (move_uploaded_file($file['tmp_name'], $filePath . $fileName)) {
            $karyawan->setPhoto($fileName);
            $this->entityManager->persist($karyawan);
            $this->entityManager->flush();
        }
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/add_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
