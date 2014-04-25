<?php

namespace WWII\Application\Hrd\Karyawan;

class AddKaryawanFromPelamarAction
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
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\ActiveDepartment($this->serviceManager, $this->entityManager);
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'PROSES' :
                $this->dispatchProses($params);
                break;
            case 'SIMPAN' :
                $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $this->routeManager->redirect(array('action' => 'report_karyawan'));
                break;
        }

        $pelamar = $this->getRequestedPelamar($params['namaLengkap']);
        if ($pelamar != null) {
            $params = $this->populateData($pelamar, $params);
        }

        $this->render($params);
    }

    protected function dispatchProses($params)
    {
        $errorMessages = $this->validateData($params);

        if (!empty($errorMessages)) {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function dispatchSimpan($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $pelamar = $this->getRequestedPelamar($params['namaLengkap']);

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
            $pelamar->getDetailPelamar()->setStatus('diterima');
            $karyawan->setPelamar($pelamar);

            $this->entityManager->persist($karyawan);
            $this->entityManager->flush();

            $this->savePhotoKaryawan($_FILES['photo'], $karyawan, $pelamar);

            $this->flashMessenger->addMessage('Data berhasil disimpan.');
            $this->routeManager->redirect(array('action' => 'report_karyawan'));
            //~$this->routeManager->redirect(array('action' => 'cetak_pkwt', $karyawan->getId()));
        } else {
            $this->errorMessages = array_merge($this->errorMessages, $errorMessages);
        }
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (strtoupper($params['btx']) == 'PROSES') {
            $pelamar = $this->getRequestedPelamar($params['namaLengkap']);

            if ($pelamar == null) {
                $errorMessages['namaLengkap'] = 'tidak ditemukan di data pelamar';
            } else {
                if ($pelamar->getDetailPelamar()->getStatus() == 'diterima') {
                    $model = $this->entityManager
                        ->getRepository('WWII\Domain\Hrd\Karyawan\Karyawan')
                        ->findOneByPelamar($pelamar->getId());

                    if ($model != null) {
                        $errorMessages['namaLengkap'] = 'sudah tercatat di data karyawan';
                    }
                } else {
                    $errorMessages['namaLengkap'] = 'status pelamar sedang interview atau ditolak';
                }
            }
        }

        if (strtoupper($params['btx']) == 'SIMPAN') {
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

            if ($params['tanggalAwalKontrakKerja'] == '') {
                $errorMessages['tanggalAwalKontrakKerja'] = 'harus diisi';
            } else {
                $arrayTanggalAwalKontrakKerja = explode('/', $params['tanggalAwalKontrakKerja']);
                try {
                    $tanggalAwalKontrakKerja = new \DateTime($arrayTanggalAwalKontrakKerja[2] . '-' . $arrayTanggalAwalKontrakKerja[1] . '-' . $arrayTanggalAwalKontrakKerja[0]);
                } catch(\Exception $e) {
                    $errorMessages['tanggalAwalKontrakKerja'] = 'format tidak valid (ex. 17/03/2014).';
                }
            }

            if ($params['tanggalAkhirKontrakKerja'] == '') {
                $errorMessages['tanggalAkhirKontrakKerja'] = 'harus diisi';
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
        }

        return $errorMessages;
    }

    protected function populateData(\WWII\Domain\Hrd\Pelamar\Pelamar $pelamar, $params)
    {
        $now = new \DateTime();

        $params = array_merge(
            array(
                'nik' => isset($params['nik']) ? $params['nik'] : '',
                'nomorKtp' => isset($params['nomorKtp']) ? $params['nomorKtp'] : '',
                'namaLengkap' => isset($params['namaLengkap']) ? $params['namaLengkap'] : $pelamar->getNamaLengkap(),
                'namaPanggilan' => isset($params['namaPanggilan']) ? $params['namaPanggilan'] : $pelamar->getNamaPanggilan(),
                'jenisKelamin' => $pelamar->getJenisKelamin(),
                'tempatLahir' => isset($params['tempatLahir']) ? $params['tempatLahir'] : '',
                'tanggalLahir' => call_user_func_array(function($pelamar, $params) {
                    if (empty($params['tanggalLahir'])) {
                        if ($pelamar->getTanggalLahir() !== null) {
                            return $pelamar->getTanggalLahir()->format('d/m/Y');
                        } else {
                            return null;
                        }
                    } else {
                        return $params['tanggalLahir'];
                    }
                }, array($pelamar, $params)),
                'agama' => isset($params['agama']) ? $params['agama'] : $pelamar->getAgama(),
                'statusPerkawinan' => isset($params['statusPerkawinan']) ? $params['statusPerkawinan'] : $pelamar->getStatusPerkawinan(),
                'pendidikan' => isset($params['pendidikan']) ? $params['pendidikan'] : $pelamar->getPendidikan(),
                'jurusan' => isset($params['jurusan']) ? $params['jurusan'] : $pelamar->getJurusan(),
                'alamat' => isset($params['alamat']) ? $params['alamat'] : $pelamar->getAlamat(),
                'kota' => isset($params['kota']) ? $params['kota'] : $pelamar->getKota(),
                'kodePos' => isset($params['kodePos']) ? $params['kodePos'] : $pelamar->getKodePos(),
                'telepon' => isset($params['telepon']) ? $params['telepon'] : $pelamar->getTelepon(),
                'ponsel' => isset($params['ponsel']) ? $params['ponsel'] : $pelamar->getPonsel(),
                'ponselLain' => isset($params['ponselLain']) ? $params['ponselLain'] : $pelamar->getPonselLain(),
                'email' => isset($params['email']) ? $params['email'] : $pelamar->getEmail(),
                'npwp' => isset($params['npwp']) ? $params['npwp'] : $pelamar->getNpwp(),
                'departemen' => isset($params['departemen']) ? $params['departemen'] : $pelamar->getDetailPelamar()->getPosisi(),
                'tanggalAwalKontrakKerja' => call_user_func_array(function($pelamar, $params) {
                    if (empty($params['tanggalAwalKontrakKerja'])) {
                        if ($pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja() !== null) {
                            return $pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja()->format('d/m/Y');
                        } else {
                            return null;
                        }
                    } else {
                        return $params['tanggalAwalKontrakKerja'];
                    }
                }, array($pelamar, $params)),
                'tanggalAkhirKontrakKerja' => isset($params['tanggalAkhirKontrakKerja']) ? $params['tanggalAkhirKontrakKerja'] : '',
                'tanggalMasukKerja' => call_user_func_array(function($pelamar, $params) {
                    if (empty($params['tanggalMasukKerja'])) {
                        if ($pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja() !== null) {
                            return $pelamar->getDetailPelamar()->getTanggalRencanaMasukKerja()->format('d/m/Y');
                        } else {
                            return null;
                        }
                    } else {
                        return $params['tanggalMasukKerja'];
                    }
                }, array($pelamar, $params)),
                'photo' => $pelamar->getPhoto(),
            ),
            $params
        );

        return $params;
    }

    protected function savePhotoKaryawan($file, \WWII\Domain\Hrd\Karyawan\Karyawan $karyawan, \WWII\Domain\Hrd\Pelamar\Pelamar $pelamar)
    {
        if ($file['error'] ==  UPLOAD_ERR_NO_FILE) {
            $imagePath = dirname($_SERVER['SCRIPT_FILENAME'])
            . '/images/'
            . $this->routeManager->getModule() . '/pelamar/';

            $newImagePath = dirname($_SERVER['SCRIPT_FILENAME'])
                . '/images/'
                . $this->routeManager->getModule() . '/'
                . $this->routeManager->getController() . '/';
            $extension = substr($pelamar->getPhoto(), strrpos($pelamar->getPhoto(), '.') + 1);
            $fileName = $karyawan->getId() . '.' . $extension;

            if (file_exists($imagePath . $pelamar->getPhoto()) && copy($imagePath . $pelamar->getPhoto(), $newImagePath . $fileName)) {
                $karyawan->setPhoto($fileName);
            }
        } else {
            $imagePath = dirname($_SERVER['SCRIPT_FILENAME'])
                . '/images/'
                . $this->routeManager->getModule() . '/'
                . $this->routeManager->getController() . '/';

            $extension = substr($file['name'], strrpos($file['name'], '.') + 1);
            $fileName = $karyawan->getId() . '.' . $extension;

            if (move_uploaded_file($file['tmp_name'], $imagePath . $fileName)) {
                $karyawan->setPhoto($fileName);
            }
        }

        $this->entityManager->persist($karyawan);
        $this->entityManager->flush();
    }

    protected function getRequestedPelamar($namaLengkap)
    {
        $pelamar = $this->entityManager
            ->getRepository('WWII\Domain\Hrd\Pelamar\Pelamar')
            ->findOneByNamaLengkap($namaLengkap);

        return $pelamar;
    }

    public function render($params)
    {
        $errorMessages = $this->errorMessages;
        $departmentList = $this->departmentHelper->getDepartmentList();

        $this->templateManager->renderHeader();
        include('view/add_karyawan_from_pelamar.phtml');
        $this->templateManager->renderFooter();
    }
}
