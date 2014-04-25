<?php

namespace WWII\Application\Hrd\Pelamar;

class EditPelamarAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $departmentHelper;

    protected $data;

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\ActiveDepartment($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        if (!$this->isEditable()) {
            $this->flashMessenger->addMessage('Data tidak bisa direvisi.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        switch (strtoupper($params['btx'])) {
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_pelamar'));
                break;
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
            default:
                break;
        }

        $pelamar = $this->getRequestedModel();
        $params = $this->populateData($pelamar, $params);
        $params['departmentList'] = $this->departmentHelper->getDepartmentList();

        $this->render($params);
    }

    protected function dispatchSimpan($params)
    {
        $pelamar = $this->getRequestedModel();
        if ($pelamar == null) {
            $this->flashMessenger->addMessage('Data tidak ditemukan.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pelamar->setNamaLengkap($params['namaLengkap']);
            $pelamar->setNamaPanggilan($params['namaPanggilan']);
            $pelamar->setJenisKelamin($params['jenisKelamin']);

            $arrayTanggal = explode('/', $params['tanggalLahir']);
            $tanggalLahir = new \DateTime($arrayTanggal[2] . '-' . $arrayTanggal[1] . '-' . $arrayTanggal[0]);
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

            $detailPelamar = $pelamar->getDetailPelamar();
            if ($detailPelamar == null) {
                $detailPelamar = new \WWII\Domain\Hrd\Pelamar\DetailPelamar();
                $pelamar->setDetailPelamar($detailPelamar);
            }

            if (!empty($params['tanggalInterview'])) {
                $arrayTanggalInterview = explode('/', $params['tanggalInterview']);
                $tanggalInterview = new \DateTime($arrayTanggalInterview[2] . '-' . $arrayTanggalInterview[1] . '-' . $arrayTanggalInterview[0]);
                $detailPelamar->setTanggalInterview($tanggalInterview);
            }

            $detailPelamar->setPosisi($params['posisi']);

            $this->entityManager->persist($pelamar);
            $this->entityManager->flush();

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
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
                $errorMessages['tanggalLahir'] = 'format tidak valid (ex. 17/03/2014).';
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

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar, $params)
    {
        $params = array_merge(
            array(
                'namaLengkap' => isset($params['namaLengkap']) ? $params['namaLengkap'] : $pelamar->getNamaLengkap(),
                'namaPanggilan' => isset($params['namaPanggilan']) ? $params['namaPanggilan'] : $pelamar->getNamaPanggilan(),
                'jenisKelamin' => isset($params['jenisKelamin']) ? $params['jenisKelamin'] : $pelamar->getJenisKelamin(),
                'tanggalLahir' => call_user_func(function($pelamar) {
                    if ($pelamar->getTanggalLahir() !== null) {
                        return $pelamar->getTanggalLahir()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $pelamar),
                'agama' => isset($params['agama']) ? $params['agama'] : $pelamar->getAgama(),
                'statusPerkawinan' => isset($params['statusPerkawinan']) ? $params['statusPerkawinan'] : $pelamar->getStatusPerkawinan(),
                'pendidikan' => $pelamar->getPendidikan(),
                'jurusan' => isset($params['jurusan']) ? $params['jurusan'] : $pelamar->getJurusan(),
                'alamat' => isset($params['alamat']) ? $params['alamat'] : $pelamar->getAlamat(),
                'kota' => isset($params['kota']) ? $params['kota'] : $pelamar->getKota(),
                'kodePos' => isset($params['kodePos']) ? $params['kodePos'] : $pelamar->getKodePos(),
                'telepon' => isset($params['telepon']) ? $params['telepon'] : $pelamar->getTelepon(),
                'ponsel' => isset($params['ponsel']) ? $params['ponsel'] : $pelamar->getPonsel(),
                'ponselLain' => isset($params['ponselLain']) ? $params['ponselLain'] : $pelamar->getPonselLain(),
                'email' => isset($params['email']) ? $params['email'] : $pelamar->getEmail(),
                'npwp' => isset($params['npwp']) ? $params['npwp'] : $pelamar->getNpwp(),
                'posisi' => isset($params['posisi']) ? $params['posisi'] : $pelamar->getDetailPelamar()->getPosisi(),
                'tanggalInterview' => call_user_func(function($pelamar) {
                    if ($pelamar->getDetailPelamar()->getTanggalInterview() !== null) {
                        return $pelamar->getDetailPelamar()->getTanggalInterview()->format('d/m/Y');
                    } else {
                        return null;
                    }
                }, $pelamar),
            ),
            $params
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Pelamar\Pelamar')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'report_pelamar'));
        }

        return $model;
    }

    protected function isEditable()
    {
        $model = $this->getRequestedModel();

        $detailPelamar = $model->getDetailPelamar();
        if ($detailPelamar->getStatus() == 'diterima') {
            $modelKaryawan = $this->entityManager
                ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
                ->findOneByPelamar($model->getId());

            if ($modelKaryawan == null) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function render(array $params = array())
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/edit_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
