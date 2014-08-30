<?php

namespace WWII\Application\Hrd\Payroll;

class GenerateMonthlyPayrollAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
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
            case 'PROSES':
                $result = $this->dispatchGenerate($params);
                break;
            case 'SIMPAN':
                $result = $this->dispatchSimpan($params);
                break;
            case 'RESET':
            default:
                $this->clearSessionData();
                break;
        }

        $this->render($result);
    }

    public function dispatchGenerate($params)
    {
        if (! empty($params)) {
            $errorMessages = $this->validateData($params);

            if (empty($errorMessages)) {
                $selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");

                $data = $this->getEmployeeDetail($params['company'], $selectedDate);

                if ($data == null) {
                    $this->flashMessenger->addMessage('Data pada tanggal ' . $selectedDate->format('d-m-Y')
                        . ' kosong!');
                }

                $this->addSessionData('params', $params);
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function dispatchSimpan($params)
    {
        $params = $this->getSessionData('params');
        $selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");
        $data = $this->getEmployeeDetail($params['company'], $selectedDate);

        $query = $this->databaseManager->prepare("
            SELECT
                fId
            FROM
                a_Personnel_PayrollMst
            WHERE
                fDateTime = '{$selectedDate->format('Y-m-d')}'
        ");
        $query->execute();
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            $query = $this->databaseManager->prepare("
                INSERT INTO
                    a_Personnel_PayrollMst (
                        fDateTime
                    ) OUTPUT INSERTED.fId
                VALUES (
                    '{$selectedDate->format('Y-m-d')}'
                )
            ");
            $query->execute();
            $result = $query->fetch(\PDO::FETCH_ASSOC);
        }

        $id = $result['fId'];
        foreach ($data as $item) {
            $item['fPeriodeAwal'] = new \DateTime($item['fPeriodeAwal']);
            $item['fPeriodeAwal'] = $item['fPeriodeAwal']->format('Y-m-d');

            $item['fPeriodeAkhir'] = new \DateTime($item['fPeriodeAkhir']);
            $item['fPeriodeAkhir'] = $item['fPeriodeAkhir']->format('Y-m-d');

            $query = $this->databaseManager->prepare("
                SELECT
                    fId
                FROM
                    a_Personnel_PayrollItem
                WHERE
                    fCode = '{$item['fCode']}'
                    AND fStatus = '{$item['fStatus']}'
                    AND fPayrollMstId = {$id}
            ");
            $query->execute();
            $result = $query->fetch(\PDO::FETCH_ASSOC);

            if (empty($result)) {
                $query = $this->databaseManager->prepare("
                    INSERT INTO
                        a_Personnel_PayrollItem (
                            fPayrollMstId,
                            fCode,
                            fDeptCode,
                            fPTKPCode,
                            fStatus,
                            fTanggalPeriodeAwal,
                            fTanggalPeriodeAkhir,
                            fBasicWage,
                            fTunjanganTetap,
                            fTunjanganSkill,
                            fTunjanganInsentif,
                            fIsTunjanganPajak,
                            fJamKerjaTerjadwal,
                            fJamKerjaUser,
                            fJumlahKehadiran,
                            fJumlahIjin,
                            fJumlahCuti,
                            fJumlahSakit,
                            fJumlahHariLibur,
                            fJSBasic
                        )
                    VALUES (
                        {$id},
                        '{$item['fCode']}',
                        '{$item['fDeptCode']}',
                        '{$item['fPTKPCode']}',
                        '{$item['fStatus']}',
                        '{$item['fPeriodeAwal']}',
                        '{$item['fPeriodeAkhir']}',
                        '{$item['fBasicWage']}',
                        '{$item['fTunjanganTetap']}',
                        '{$item['fTunjanganSkill']}',
                        '{$item['fTunjanganInsentif']}',
                        '{$item['fIsTunjanganPajak']}',
                        '{$item['fJumlahJamKerjaTerjadwal']}',
                        '{$item['fJumlahJamKerjaUser']}',
                        '{$item['fJumlahKehadiran']}',
                        '{$item['fJumlahAbsen']}',
                        '{$item['fJumlahCuti']}',
                        '{$item['fJumlahSakit']}',
                        '{$item['fJumlahHariLibur']}',
                        '{$item['fJSBasic']}'
                    )
                ");
                $query->execute();
            } else {
                $query = $this->databaseManager->prepare("
                    UPDATE
                        a_Personnel_PayrollItem
                    SET
                        fDeptCode = '{$item['fDeptCode']}',
                        fPTKPCode = '{$item['fPTKPCode']}',
                        fStatus = '{$item['fStatus']}',
                        fTanggalPeriodeAwal = '{$item['fPeriodeAwal']}',
                        fTanggalPeriodeAkhir = '{$item['fPeriodeAkhir']}',
                        fBasicWage = '{$item['fBasicWage']}',
                        fTunjanganTetap = '{$item['fTunjanganTetap']}',
                        fTunjanganSkill = '{$item['fTunjanganSkill']}',
                        fTunjanganInsentif = '{$item['fTunjanganInsentif']}',
                        fIsTunjanganPajak = '{$item['fIsTunjanganPajak']}',
                        fJamKerjaTerjadwal = '{$item['fJumlahJamKerjaTerjadwal']}',
                        fJamKerjaUser = '{$item['fJumlahJamKerjaUser']}',
                        fJumlahKehadiran = '{$item['fJumlahKehadiran']}',
                        fJumlahIjin = '{$item['fJumlahAbsen']}',
                        fJumlahCuti = '{$item['fJumlahCuti']}',
                        fJumlahSakit = '{$item['fJumlahSakit']}',
                        fJumlahHariLibur = '{$item['fJumlahHariLibur']}',
                        fJSBasic = '{$item['fJSBasic']}'
                    WHERE
                        fCode = '{$item['fCode']}'
                        AND fPayrollMstId = {$id}
                ");
                $query->execute();
            }
        }

        $this->clearSessionData();
        $this->flashMessenger->addMessage('Data berhasil disimpan.');
        $this->routeManager->redirect(array('action' => 'report_monthly_payroll'));

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function getEmployeeDetail($company, \DateTime $selectedDate)
    {
        $optionalFilter = '';

        switch (strtoupper($company)) {
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
                break;
        }

        $employeeDetail = $this->databaseManager->prepare("
            SELECT
                t_PALM_PersonnelFileMst.fCode,
                t_PALM_PersonnelFileMst.fName,
                t_BMSM_DeptMst.fDeptCode,
                t_BMSM_DeptMst.fDeptName,
                t_PALM_PersonnelFileMst.fPTKPCode,
                t_PALM_PersonnelFileMst.fInDate,
                t_PALM_PersonnelFileMst.fCEDate,
                fStatus = CASE
                    WHEN
                        t_PALM_PersonnelFileMst.fIfContract = 1
                    THEN
                        CASE
                            WHEN
                                t_PALM_PersonnelFileMst.fCEDate > '{$selectedDate->format('Y-m-01')}'
                                AND t_PALM_PersonnelFileMst.fCEDate <= '{$selectedDate->format('Y-m-t')}'
                            THEN
                                'renewal'
                            ELSE
                                'active'
                        END
                    ELSE
                        'active'
                END,
                fBasicWage = CONVERT(INT, t_PLSD_BaseSalary.fBaseSalary),
                fTunjanganTetap = CONVERT(INT, t_PLSD_BaseSalary.fPostSalary),
                fTunjanganSkill = CONVERT(INT, t_PLSD_BaseSalary.fSkillSalary),
                fTunjanganInsentif = CONVERT(INT, t_PLSD_BaseSalary.fWage2),
                fIsTunjanganPajak = 0,
                fJSBasic = 1700000
            FROM
                t_PALM_PersonnelFileMst
            LEFT JOIN
                a_Personnel_CardRecord ON a_Personnel_CardRecord.fCode = t_PALM_PersonnelFileMst.fCode
            LEFT JOIN
                t_BMSM_DeptMst ON T_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
            LEFT JOIN
                t_PLSD_BaseSalary ON t_PLSD_BaseSalary.fCode = t_PALM_PersonnelFileMst.fCode
            WHERE
                t_PALM_PersonnelFileMst.fDFlag = 0
                AND t_PALM_PersonnelFileMst.fInDate <= '{$selectedDate->format('Y-m-t')}'
                AND a_Personnel_CardRecord.fDateTime >= '{$selectedDate->format('Y-m-01')}'
                AND a_Personnel_CardRecord.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                {$optionalFilter}
            GROUP BY
                t_PALM_PersonnelFileMst.fCode,
                t_PALM_PersonnelFileMst.fName,
                t_BMSM_DeptMst.fDeptCode,
                t_BMSM_DeptMst.fDeptName,
                t_PALM_PersonnelFileMst.fPTKPCode,
                t_PALM_PersonnelFileMst.fInDate,
                t_PALM_PersonnelFileMst.fIfContract,
                t_PALM_PersonnelFileMst.fCEDate,
                t_PLSD_BaseSalary.fBaseSalary,
                t_PLSD_BaseSalary.fPostSalary,
                t_PLSD_BaseSalary.fSkillSalary,
                t_PLSD_BaseSalary.fWage2
            ORDER BY
                t_PALM_PersonnelFileMst.fCode ASC
        ");
        $employeeDetail->execute();

        $data = array();
        while ($item = $employeeDetail->fetch(\PDO::FETCH_ASSOC)) {
            if (strtoupper($item['fStatus']) == 'RENEWAL') {
                $item2 = $item;
                $i = count($data);

                $tanggalPeriodeAwal = new \DateTime($selectedDate->format('Y-m-01'));
                $tanggalPeriodeAkhir = new \DateTime($item['fCEDate']);
                $tanggalPeriodeAkhir->sub(new \DateInterval('P1D'));

                $item2['fStatus'] = 'active';
                $item2['fPeriodeAwal'] = $tanggalPeriodeAwal->format('d-M-Y');
                $item2['fPeriodeAkhir'] = $tanggalPeriodeAkhir->format('d-M-Y');

                $detailJamKerja = $this->getDetailJamKerja(
                    $item2['fCode'],
                    $tanggalPeriodeAwal,
                    $tanggalPeriodeAkhir
                );

                $item2['fGajiKotor'] = $detailJamKerja['fBasicWage'] + $itdetailJamKerjaem['fTunjanganTetap']
                    + $detailJamKerja['fTunjanganSkill'] + $detailJamKerja['fTunjanganInsentif'];

                $jumlahHariKerjaTerhitung = $detailJamKerja['fJumlahKehadiran'] + $detailJamKerja['fJumlahCuti']
                    + $detailJamKerja['fJumlahSakit'] + $detailJamKerja['fJumlahHariLibur']
                    + $detailJamKerja['fJumlahAbsen'];
                if ($jumlahHariKerjaTerhitung >= $selectedDate->format('t')) {
                    if ($selectedDate->format('n') == 2) {
                        $jumlahHariKerjaTerhitung = $selectedDate->format('t');
                    } else {
                        $jumlahHariKerjaTerhitung = 30;
                    }
                } else {
                    $item2['fGajiKotor'] = ($jumlahHariKerjaTerhitung / 30) * $item2['fGajiKotor'];
                }
                $item['fGajiBersih'] = $item2['fGajiKotor'] * (
                    ($jumlahHariKerjaTerhitung - $item2['fJumlahAbsen']) / $jumlahHariKerjaTerhitung
                );

                $i = count($data);
                $data[$i] = array_merge($item2, $detailJamKerja);
            }

            if (strtoupper($item['fStatus']) == 'RENEWAL') {
                $tanggalPeriodeAwal = new \DateTime($item['fCEDate']);
                $tanggalPeriodeAkhir = new \DateTime($tanggalPeriodeAwal->format('Y-m-t'));
            } else {
                $tanggalPeriodeAwal = new \DateTime($selectedDate->format('Y-m-01'));
                $tanggalPeriodeAkhir = new \DateTime($tanggalPeriodeAwal->format('Y-m-t'));
            }

            $item['fPeriodeAwal'] = $tanggalPeriodeAwal->format('d-M-Y');
            $item['fPeriodeAkhir'] = $tanggalPeriodeAkhir->format('d-M-Y');

            $detailJamKerja = $this->getDetailJamKerja(
                $item['fCode'],
                $tanggalPeriodeAwal,
                $tanggalPeriodeAkhir
            );

            $item['fGajiKotor'] = $detailJamKerja['fBasicWage'] + $itdetailJamKerjaem['fTunjanganTetap']
                + $detailJamKerja['fTunjanganSkill'] + $detailJamKerja['fTunjanganInsentif'];

            $jumlahHariKerjaTerhitung = $detailJamKerja['fJumlahKehadiran'] + $detailJamKerja['fJumlahCuti']
                + $detailJamKerja['fJumlahSakit'] + $detailJamKerja['fJumlahHariLibur']
                + $detailJamKerja['fJumlahAbsen'];
            if ($jumlahHariKerjaTerhitung >= $selectedDate->format('t')) {
                if ($selectedDate->format('n') == 2) {
                    $jumlahHariKerjaTerhitung = $selectedDate->format('t');
                } else {
                    $jumlahHariKerjaTerhitung = 30;
                }
            } else {
                $item2['fGajiKotor'] = ($jumlahHariKerjaTerhitung / 30) * $item['fGajiKotor'];
            }
            $item['fGajiBersih'] = $item['fGajiKotor'] * (
                ($jumlahHariKerjaTerhitung - $item['fJumlahAbsen']) / $jumlahHariKerjaTerhitung
            );

            $i = count($data);
            $data[$i] = array_merge($item, $detailJamKerja);
        }

        return $data;
    }

    protected function getDetailJamKerja($nik, \DateTime $tanggalPeriodeAwal, \DateTime $tanggalPeriodeAkhir)
    {
        $query = $this->databaseManager->prepare("
            SELECT
                fJumlahKehadiran = SUM(CASE
                    WHEN
                        a_Personnel_CardRecord.fStatus = 'P'
                    THEN
                        1
                    ELSE
                        0
                END),
                fJumlahSakit = SUM(CASE
                    WHEN
                        a_Personnel_CardRecord.fStatus = 'S'
                    THEN
                        1
                    ELSE
                        0
                END),
                fJumlahAbsen = SUM(CASE
                    WHEN
                        a_Personnel_CardRecord.fStatus = 'A'
                    THEN
                        1
                    ELSE
                        0
                END),
                fJumlahCuti = SUM(CASE
                    WHEN
                        a_Personnel_CardRecord.fStatus = 'C'
                    THEN
                        1
                    ELSE
                        0
                END),
                fJumlahHariLibur = SUM(CASE
                    WHEN
                        a_Personnel_CardRecord.fStatus = 'H'
                    THEN
                        1
                    ELSE
                        0
                END),
                fJumlahJamKerjaTerjadwal = CONVERT(
                    VARCHAR(3),
                    (
                        DATEPART(
                            DD,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeScheduledIn,
                                    a_Personnel_CardRecord.fDateTimeScheduledOut
                                )),
                                0
                            )
                        ) * 24
                    ) + (
                        DATEPART(
                            HH,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeScheduledIn,
                                    a_Personnel_CardRecord.fDateTimeScheduledOut
                                )),
                                0
                            )
                        )
                    )
                ) + ':' + (CASE
                    WHEN
                        DATEPART(
                            MI,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeScheduledIn,
                                    a_Personnel_CardRecord.fDateTimeScheduledOut
                                )),
                                0
                            )
                        ) > 9
                    THEN
                        CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                MI,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeScheduledIn,
                                        a_Personnel_CardRecord.fDateTimeScheduledOut
                                    )),
                                    0
                                )
                            )
                        )
                    ELSE
                        '0' + CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                MI,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeScheduledIn,
                                        a_Personnel_CardRecord.fDateTimeScheduledOut
                                    )),
                                    0
                                )
                            )
                        )
                END) + ':' + (CASE
                    WHEN
                        DATEPART(
                            SS,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeScheduledIn,
                                    a_Personnel_CardRecord.fDateTimeScheduledOut
                                )),
                                0
                            )
                        ) > 9
                    THEN
                        CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                SS,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeScheduledIn,
                                        a_Personnel_CardRecord.fDateTimeScheduledOut
                                    )),
                                    0
                                )
                            )
                        )
                    ELSE
                        '0' + CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                SS,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeScheduledIn,
                                        a_Personnel_CardRecord.fDateTimeScheduledOut
                                    )),
                                    0
                                )
                            )
                        )
                END),
                fJumlahJamKerjaUser = CONVERT(
                    VARCHAR(3),
                    (
                        DATEPART(
                            DD,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeUserIn,
                                    a_Personnel_CardRecord.fDateTimeUserOut
                                )),
                                0
                            )
                        ) * 24
                    ) + (
                        DATEPART(
                            HH,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeUserIn,
                                    a_Personnel_CardRecord.fDateTimeUserOut
                                )),
                                0
                            )
                        )
                    )
                ) + ':' + (CASE
                    WHEN
                        DATEPART(
                            MI,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeUserIn,
                                    a_Personnel_CardRecord.fDateTimeUserOut
                                )),
                                0
                            )
                        ) > 9
                    THEN
                        CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                MI,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeUserIn,
                                        a_Personnel_CardRecord.fDateTimeUserOut
                                    )),
                                    0
                                )
                            )
                        )
                    ELSE
                        '0' + CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                MI,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeUserIn,
                                        a_Personnel_CardRecord.fDateTimeUserOut
                                    )),
                                    0
                                )
                            )
                        )
                END) + ':' + (CASE
                    WHEN
                        DATEPART(
                            SS,
                            DATEADD(
                                SS,
                                SUM(DATEDIFF(
                                    SS,
                                    a_Personnel_CardRecord.fDateTimeUserIn,
                                    a_Personnel_CardRecord.fDateTimeUserOut
                                )),
                                0
                            )
                        ) > 9
                    THEN
                        CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                SS,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeUserIn,
                                        a_Personnel_CardRecord.fDateTimeUserOut
                                    )),
                                    0
                                )
                            )
                        )
                    ELSE
                        '0' + CONVERT(
                            VARCHAR(2),
                            DATEPART(
                                SS,
                                DATEADD(
                                    SS,
                                    SUM(DATEDIFF(
                                        SS,
                                        a_Personnel_CardRecord.fDateTimeUserIn,
                                        a_Personnel_CardRecord.fDateTimeUserOut
                                    )),
                                    0
                                )
                            )
                        )
                END)
            FROM
                a_Personnel_CardRecord
            WHERE
                a_Personnel_CardRecord.fCode = '{$nik}'
                AND a_Personnel_CardRecord.fDateTime >= '{$tanggalPeriodeAwal->format('Y-m-d')}'
                AND a_Personnel_CardRecord.fDateTime <= '{$tanggalPeriodeAkhir->format('Y-m-d')}'
        ");
        $query->execute();
        $detailJamKerja = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($detailJamKerja['fJumlahJamKerjaUser'])) {
            $detailJamKerja['fJumlahJamKerjaUser'] = '00:00:00';
        }

        return $detailJamKerja;
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
        } else {
            $selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");
            $maxDate = new \DateTime('last day of this month');

            if ($selectedDate > $maxDate) {
                $errorMessages['global'][] = 'Tanggal yang dipilih melebihi batas akhir (bulan ini)';
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

        if (! isset($this->sessionContainer->{$sessionNamespace})) {
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

    protected function render(array $result = null)
    {
        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/generate_monthly_payroll.phtml');
        $this->templateManager->renderFooter();
    }
}
