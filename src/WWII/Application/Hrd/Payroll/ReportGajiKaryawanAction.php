<?php

namespace WWII\Application\Hrd\Payroll;

class ReportGajiKaryawanAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $maxItemPerPage = 20;

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->routeManager = $serviceManager->get('RouteManager');
        $this->templateManager = $serviceManager->get('TemplateManager');
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->sessionContainer = $serviceManager->get('SessionContainer');
        $this->flashMessenger = $serviceManager->get('FlashMessenger');
        $this->entityManager = $entityManager;
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'RESET':
                $this->clearSessionData();
                break;
            default:
                $session = $this->synchronizeSessionData('params', $params);
                break;
        }

        if (! empty($session)) {
            $result = $this->dispatchFilter($session);
        }

        $this->render($result);
    }

    public function dispatchFilter($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");

            $optionalFilter = '';

            switch (strtoupper($params['company'])) {
                case 'WWII':
                    $optionalFilter .= " AND SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 1) = '0' ";
                    break;
                case 'SMK':
                    $optionalFilter .= " AND (SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 1) IN ('3', '8')"
                        . " OR UPPER(SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 2)) = 'SM') ";
                    break;
                case 'ICS':
                    $optionalFilter .= " AND (SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 1) IN ('4', '9')"
                        . " OR UPPER(SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 2)) = 'IC') ";
                    break;
                case 'SKCM':
                    $optionalFilter .= " AND (SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 1) IN ('5', '6')"
                        . " OR UPPER(SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 2)) = 'SK') ";
                    break;
                case 'PPC':
                    $optionalFilter .= " AND (SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 1) = '7'"
                        . " OR UPPER(SUBSTRING(t_PALM_PersonnelFileMst.fCode, 1, 2)) = 'PP') ";
            }

            $query = $this->databaseManager->prepare("
                SELECT
                    t_PALM_PersonnelFileMst.fCode,
                    t_PALM_PersonnelFileMst.fName,
                    a_Personnel_PayrollItem.fStatus,
                    t_BMSM_DeptMst.fDeptName,
                    fGender = CASE
                        WHEN
                            t_PALM_PersonnelFileMst.fSex = 0
                        THEN
                            'L'
                        ELSE
                            'P'
                    END,
                    a_Personnel_PTKPMst.fPTKPCode,
                    a_Personnel_PTKPMst.fPTKPValue,
                    a_Personnel_PayrollMst.fDateTime,
                    t_PALM_PersonnelFileMst.fInDate,
                    fOutDate = CASE
                        WHEN
                            t_PALM_PersonnelFileMst.fDDate  >= '{$selectedDate->format('Y-m-01')}'
                            AND t_PALM_PersonnelFileMst.fDDate  <= '{$selectedDate->format('Y-m-t')}'
                        THEN
                            t_PALM_PersonnelFileMst.fDDate
                        ELSE
                            NULL
                    END,
                    a_Personnel_PayrollItem.fTanggalPeriodeAwal,
                    a_Personnel_PayrollItem.fTanggalPeriodeAkhir,
                    a_Personnel_PayrollItem.fBasicWage,
                    a_Personnel_PayrollItem.fTunjanganTetap,
                    a_Personnel_PayrollItem.fTunjanganSkill,
                    a_Personnel_PayrollItem.fTunjanganInsentif,
                    a_Personnel_PayrollItem.fIsTunjanganPajak,
                    a_Personnel_PayrollItem.fJSBasic,
                    fNPWP = t_PALM_PersonnelFileMst.fRemark,
                    fJamsostek = t_PALM_PolicyItem.fPersonNo,
                    fBankAccountNo = t_PLSD_AccountsMst.fAccounts,
                    a_Personnel_PayrollItem.fJumlahKehadiran,
                    a_Personnel_PayrollItem.fJumlahIjin,
                    a_Personnel_PayrollItem.fJumlahCuti,
                    a_Personnel_PayrollItem.fJumlahSakit,
                    a_Personnel_PayrollItem.fJumlahHariLibur,
                    a_Personnel_PayrollItem.fJamKerjaUser,
                    a_Personnel_PayrollItem.fJamKerjaTerjadwal
                FROM
                    a_Personnel_PayrollItem
                LEFT JOIN
                    a_Personnel_PayrollMst ON a_Personnel_PayrollMst.fId = a_Personnel_PayrollItem.fPayrollMstId
                LEFT JOIN
                    t_PALM_PersonnelFileMst ON t_PALM_PersonnelFileMst.fCode = a_Personnel_PayrollItem.fCode
                LEFT JOIN
                    t_BMSM_DeptMst ON t_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
                LEFT JOIN
                    t_PLSD_AccountsMst ON t_PLSD_AccountsMst.fCode = a_Personnel_PayrollItem.fCode
                LEFT JOIN
                    t_PALM_PolicyItem ON t_PALM_PolicyItem.fCode = a_Personnel_PayrollItem.fCode
                LEFT JOIN
                    a_Personnel_PTKPMst on a_Personnel_PTKPMst.fPTKPCode = a_Personnel_PayrollItem.fPTKPCode
                WHERE
                    a_Personnel_PayrollMst.fDateTime >= '{$selectedDate->format('Y-m-01')}'
                    AND a_Personnel_PayrollMst.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                    {$optionalFilter}
                ORDER BY
                    a_Personnel_PayrollItem.fCode ASC
            ");
            $query->execute();

            $periode = new \DateTime($item['fDateTime']);

            $data = array();
            while($item = $query->fetch(\PDO::FETCH_ASSOC)) {
                $item['fDateTime'] = new \DateTime($item['fDateTime']);
                $item['fTanggalPeriodeAwal'] = new \DateTime($item['fTanggalPeriodeAwal']);
                $item['fTanggalPeriodeAkhir'] = new \DateTime($item['fTanggalPeriodeAkhir']);

                $item['fNow'] = new \DateTime($item['fDateTime']->format('Y-m-t'));

                if (! empty($item['fInDate'])) {
                    $item['fInDate'] = new \DateTime($item['fInDate']);
                }

                if (! empty($item['fOutDate'])) {
                    $item['fOutDate'] = new \DateTime($item['fOutDate']);
                }

                $item['fJumlahHariKerja'] = $item['fJumlahKehadiran'] + $item['fJumlahCuti'] + $item['fJumlahSakit']
                    + $item['fJumlahHariLibur'] + $item['fJumlahIjin'];
                if ($item['fJumlahHariKerja'] > 30) {
                    $item['fJumlahHariKerja'] = 30;
                }

                $item['fTotal'] = $item['fBasicWage']
                    + $item['fTunjanganTetap']
                    + $item['fTunjanganSkill']
                    + $item['fTunjanganInsentif'];

                $item['fUpahKerja'] = ($item['fJumlahHariKerja'] - $item['fJumlahIjin'])
                    / $item['fJumlahHariKerja']
                    * $item['fBasicWage'];
                $item['fUpahTunjanganTetap'] = ($item['fJumlahHariKerja'] - $item['fJumlahIjin'])
                    / $item['fJumlahHariKerja']
                    * $item['fTunjanganTetap'];
                $item['fUpahTunjanganSkill'] =($item['fJumlahHariKerja'] - $item['fJumlahIjin'])
                    / $item['fJumlahHariKerja']
                    * $item['fTunjanganSkill'];
                $item['fUpahTunjanganInsentif'] = ($item['fJumlahHariKerja'] - $item['fJumlahIjin'])
                    / $item['fJumlahHariKerja']
                    * $item['fTunjanganInsentif'];

                $item['fUpahLembur'] = 0;
                $item['fSubsidiMakan'] = 0;
                $item['fKoreksi'] = 0;

                if ($item['fIsTunjanganPajak']) {
                    $item['fTunjanganPajak'] = 0;
                    if (! empty($item['fNPWP'])) {
                        $item['fTunjanganPajak'] = $item['fTunjanganPajak'] * (120/100);
                    }
                } else {
                    $item['fTunjanganPajak'] = 0;
                }

                $item['fJumlahTunjangan'] = $item['fUpahTunjanganTetap']
                    + $item['fUpahTunjanganSkill']
                    + $item['fUpahTunjanganInsentif']
                    + $item['fUpahLembur']
                    + $item['fSubsidiMakan']
                    + $item['fKoreksi'];
                $item['fTotalAllTunjangan'] = $item['fJumlahTunjangan'] + $item['fTunjanganPajak'];
                $item['fGajiKotor'] = $item['fUpahKerja'] + $item['fTotalAllTunjangan'];

                $item['fIuranJKK'] = $item['fJSBasic'] * (0.89/100);
                $item['fIuranJKM'] = $item['fJSBasic'] * (0.30/100);
                $item['fIuranJHTPerusahaan'] = $item['fJSBasic'] * (3.70/100);
                $item['fTotalIuranPerusahaan'] = $item['fIuranJKK']
                    + $item['fIuranJKM']
                    + $item['fIuranJHTPerusahaan'];
                $item['fIuranJHTKaryawan'] = $item['fJSBasic'] * (2.00/100);
                $item['fTotalIuran'] = $item['fTotalIuranPerusahaan'] + $item['fIuranJHTKaryawan'];

                $item['fPenghasilanBruto'] = $item['fGajiKotor'] + $item['fIuranJKK'] + $item['fIuranJKM'];

                $item['fBiayaJabatan'] = 500000;
                if (round($item['fPenghasilanBruto'] * (5/100)) < 500000) {
                    $item['fBiayaJabatan'] = $item['fPenghasilanBruto'] * (5/100);
                }

                $item['fPenghasilanNetto'] = $item['fPenghasilanBruto']
                    - $item['fIuranJHTKaryawan']
                    - $item['fBiayaJabatan'];

                $masaKerja = $item['fInDate']->diff($item['fNow']);
                $item['fFaktorX'] = ($masaKerja->y * 12)
                    + ($masaKerja->m)
                    + ($masaKerja->d > 0 ? 1 : 0);
                if ($item['fFaktorX'] > 12) {
                    $item['fFaktorX'] = 12;
                }

                $item['fPenghasilanSetahun'] = $item['fPenghasilanNetto'] * $item['fFaktorX'];

                $item['fPKP'] = 0;
                if (($item['fPenghasilanSetahun'] - $item['fPTKPValue']) > 0) {
                    $item['fPKP'] = $item['fPenghasilanSetahun'] - $item['fPTKPValue'];
                }

                $item['fTotalPajak'] = 0;
                if (! empty($item['fNPWP'])) {
                    if ($item['fPKP'] >= 0) {
                        $item['fTotalPajak'] += (($item['fPKP'] > 50000000) ? 50000000 : $item['fPKP']) * (5/100);

                        if (($item['fPKP'] - 50000000) >= 0) {
                            $item['fTotalPajak'] += ((($item['fPKP'] - 50000000) > 250000000) ? 250000000 : ($item['fPKP'] - 50000000)) * (15/100);

                            if (($item['fPKP'] - 250000000) >= 0) {
                                $item['fTotalPajak'] += ((($item['fPKP'] - 250000000) > 500000000) ? 500000000 : ($item['fPKP'] - 250000000)) * (35/100);

                                if (($item['fPKP'] - 500000000) >= 0) {
                                    $item['fTotalPajak'] += ($item['fPKP'] - 500000000) * (30/100);
                                }
                            }
                        }
                    }
                } else {
                    if ($item['fPKP'] >= 0) {
                        $item['fTotalPajak'] += (($item['fPKP'] > 50000000) ? 50000000 : $item['fPKP']) * (6/100);

                        if (($item['fPKP'] - 50000000) >= 0) {
                            $item['fTotalPajak'] += ((($item['fPKP'] - 50000000) > 250000000) ? 250000000 : ($item['fPKP'] - 50000000)) * (18/100);

                            if (($item['fPKP'] - 250000000) >= 0) {
                                $item['fTotalPajak'] += ((($item['fPKP'] - 250000000) > 500000000) ? 500000000 : ($item['fPKP'] - 250000000)) * (30/100);

                                if (($item['fPKP'] - 500000000) >= 0) {
                                    $item['fTotalPajak'] += ($item['fPKP'] - 500000000) * (36/100);
                                }
                            }
                        }
                    }
                }
                $item['fTotalPajak'] /= $item['fFaktorX'];

                $item['fPotonganPPH21'] = $item['fTotalPajak'];
                if (empty($item['fNPWP'])) {
                    $item['fPotonganPPH21'] = $item['fPotonganPPH21'] * (120/100);
                }

                $item['fDeduction'] = 0;
                $item['fTotalPotongan'] = $item['fIuranJHTKaryawan'] + $item['fPotonganPPH21'] + $item['fDeduction'];
                $item['fTotalDibayar'] = $item['fGajiKotor'] - $item['fTotalPotongan'];

                $i = count($data);
                $data[$i] = $item;
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (empty($params['year']) || empty($params['month'])) {
            if (empty($params['year'])) {
                $errorMessages['year'] = 'Harus dipilih';
            }

            if (empty($params['month'])) {
                $errorMessages['month'] = 'Harus dipilih';
            }
        }

        return $errorMessages;
    }

    protected function synchronizeSessionData($name, $data)
    {
        $sessionData = $this->getSessionData($name, $data);

        if (empty($data)) {
            return $sessionData;
        } else {
            return $this->addSessionData($name, $data);
        }
    }

    protected function addSessionData($name, $data)
    {
        $sessionNamespace = $this->getSessionNamespace();

        if (!isset($this->sessionContainer->{$sessionNamespace})) {
            $this->sessionContainer->{$sessionNamespace} = new \StdClass();
        }

        $this->sessionContainer->{$sessionNamespace}->{$name} = $data;

        return $this->sessionContainer->{$sessionNamespace}->{$name};
    }

    protected function getSessionData($name)
    {
        $sessionNamespace = $this->getSessionNamespace();

        if (! isset($this->sessionContainer->{$sessionNamespace})) {
            return null;
        } elseif (! isset($this->sessionContainer->{$sessionNamespace}->{$name})) {
            return null;
        } else {
            return $this->sessionContainer->{$sessionNamespace}->{$name};
        }
    }

    protected function clearSessionData()
    {
        $sessionNamespace = $this->getSessionNamespace();

        unset($this->sessionContainer->{$sessionNamespace});
    }

    protected function getSessionNamespace()
    {
        $module = $this->routeManager->getModule();
        $controller = $this->routeManager->getController();
        $action = $this->routeManager->getAction();

        $sessionNamespace = "{$module}_{$controller}_{$action}";

        return $sessionNamespace;
    }

    public function render(array $result = null)
    {
        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/report_gaji_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
