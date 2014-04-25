<?php

namespace WWII\Application\Hrd\Karyawan;

class EditKaryawanAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $flashMessenger;

    protected $data;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_karyawan'));
                break;
            case 'SIMPAN':
                $this->dispatchSimpan($params);
                break;
            default:
                break;
        }

        $karyawan = $this->getRequestedModel();
        $params = $this->populateData($karyawan, $params);

        $this->render($params);
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $karyawan = $this->getRequestedModel();
            $karyawan->setNamaLengkap($params['namaLengkap']);
            $karyawan->setNamaPanggilan($params['namaPanggilan']);
            $karyawan->setJenisKelamin($params['jenisKelamin']);

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

            $detailKaryawan = $karyawan->getDetailKaryawan();
            if ($detailKaryawan == null) {
                $detailKaryawan = new \WWII\Domain\Hrd\Karyawan\DetailKaryawan();
                $karyawan->setDetailKaryawan($detailKaryawan);
            }

            $detailKaryawan->setNik($params['nik']);
            $karyawan->setDetailKaryawan($detailKaryawan);

            $this->entityManager->persist($karyawan);
            $this->entityManager->flush();

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
            $editedModel = $this->getRequestedModel();

            $duplicateModel = $this->entityManager
                ->createQueryBuilder()
                ->select('karyawan')
                ->from('WWII\Domain\Hrd\Karyawan\Karyawan', 'karyawan')
                ->leftJoin('karyawan.detailKaryawan', 'detailKaryawan')
                ->where('detailKaryawan.nik = :nik')
                    ->setParameter('nik', $params['nik'])
                ->andWhere('karyawan.id <> :id')
                    ->setParameter('id', $editedModel->getId())
                ->getQuery()->getResult();

            if (!empty($duplicateModel)) {
                $errorMessages['nik'] = 'sudah tercatat di data karyawan';
            }
        }

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

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Karyawan\Karyawan $karyawan, $params)
    {
        $params = array_merge(
            array(
                'namaLengkap' => isset($params['namaLengkap']) ? $params['namaLengkap'] : $karyawan->getNamaLengkap(),
                'namaPanggilan' => isset($params['namaPanggilan']) ? $params['namaPanggilan'] : $karyawan->getNamaPanggilan(),
                'jenisKelamin' => isset($params['jenisKelamin']) ? $params['jenisKelamin'] : $karyawan->getJenisKelamin(),
                'namaLengkap' => isset($params['namaLengkap']) ? $params['namaLengkap'] : $karyawan->getNamaLengkap(),
                'tanggalLahir' => call_user_func_array(function($karyawan, $params) {
                    if (empty($params['tanggalLahir'])) {
                        if ($karyawan->getTanggalLahir() !== null) {
                            return $karyawan->getTanggalLahir()->format('d/m/Y');
                        }
                        return null;
                    } else {
                        return $params['tanggalLahir'];
                    }
                }, array($karyawan, $params)),
                'agama' => isset($params['agama']) ? $params['agama'] : $karyawan->getAgama(),
                'statusPerkawinan' => isset($params['statusPerkawinan']) ? $params['statusPerkawinan'] : $karyawan->getStatusPerkawinan(),
                'pendidikan' => isset($params['pendidikan']) ? $params['pendidikan'] : $karyawan->getPendidikan(),
                'jurusan' => isset($params['jurusan']) ? $params['jurusan'] : $karyawan->getJurusan(),
                'alamat' => isset($params['alamat']) ? $params['alamat'] : $karyawan->getAlamat(),
                'kota' => isset($params['kota']) ? $params['kota'] : $karyawan->getKota(),
                'kodePos' => isset($params['kodePos']) ? $params['kodePos'] : $karyawan->getKodePos(),
                'telepon' => isset($params['telepon']) ? $params['telepon'] : $karyawan->getTelepon(),
                'ponsel' => isset($params['ponsel']) ? $params['ponsel'] : $karyawan->getPonsel(),
                'ponselLain' => isset($params['ponselLain']) ? $params['ponselLain'] : $karyawan->getPonselLain(),
                'email' => isset($params['email']) ? $params['email'] : $karyawan->getEmail(),
                'npwp' => isset($params['npwp']) ? $params['npwp'] : $karyawan->getNpwp(),
                'nik' => isset($params['nik']) ? $params['nik'] : $karyawan->getDetailKaryawan()->getNik(),
            ),
            $params
        );

        return $params;
    }

    protected function getRequestedModel()
    {
        $model = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
            ->findOneById($this->routeManager->getKey());

        if ($model == null) {
            $this->flashMessenger->addMessage('Data yang anda minta tidak ada.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
        }

        return $model;
    }

    protected function isEditable(\WWII\Domain\Hrd\Karyawan\Karyawan $model)
    {
        return true;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;

        $this->templateManager->renderHeader();
        include('view/edit_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
