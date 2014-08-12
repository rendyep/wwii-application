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

                $employeeDetail = $this->databaseManager->prepare("
                    SELECT
                        t_PALM_PersonnelFileMst.fCode,
                        t_PALM_PersonnelFileMst.fName,
                        t_BMSM_DeptMst.fDeptName,
                        t_PALM_PersonnelFileMst.fIfContract,
                        t_PALM_PersonnelFileMst.fCSDate,
                        t_PALM_PersonnelFileMst.fCEDate,
                        fStatus = CASE
                            WHEN
                                t_PALM_PersonnelFileMst.fIfContract = 1
                            THEN
                                'kontrak'
                            ELSE
                                'tetap'
                        END,
                        fJumlahHariKerja = SUM(CASE
                            WHEN
                                a_Personnel_CardRecord.fStatus = 'P'
                            THEN
                                1
                            WHEN
                                a_Personnel_CardRecord.fStatus = 'H'
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
                        END)
                    FROM
                        t_PALM_PersonnelFileMst
                    LEFT JOIN
                        a_Personnel_CardRecord ON a_Personnel_CardRecord.fCode = t_PALM_PersonnelFileMst.fCode
                    LEFT JOIN
                        t_BMSM_DeptMst ON T_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
                    WHERE
                        t_PALM_PersonnelFileMst.fDFlag = 0
                        AND a_Personnel_CardRecord.fDateTime >= '{$selectedDate->format('Y-m-d')}'
                        AND a_Personnel_CardRecord.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                    GROUP BY
                        t_PALM_PersonnelFileMst.fCode,
                        t_PALM_PersonnelFileMst.fName,
                        t_BMSM_DeptMst.fDeptName,
                        t_PALM_PersonnelFileMst.fIfContract,
                        t_PALM_PersonnelFileMst.fCSDate,
                        t_PALM_PersonnelFileMst.fCEDate
                    ORDER BY
                        t_PALM_PersonnelFileMst.fCode ASC
                ");
                $employeeDetail->execute();

                $data = array();
                while ($item = $employeeDetail->fetch(\PDO::FETCH_ASSOC)) {
                    $i = count($data);
                    $data[$i] = $item;

                    $tanggalPeriodeAwal = null;
                    $tanggalPeriodeAkhir = null;

                    //~if (! empty($item['fCSDate'])) {
                        //~$tanggalPeriodeAwal = new \DateTime($item['fCSDate']);
                    //~}
//~
                    //~if (! empty($item['fCEDate'])) {
                        //~$tanggalPeriodeAkhir = new \DateTime($item['fCEDate']);
                    //~}
//~
                    //~if ($tanggalPeriodeAkhir->format('d') < $selectedDate->format('t')) {
                        //~$
                    //~}

                    $cardRecordList = $this->databaseManager->prepare("
                        SELECT
                            a_Personnel_CardRecord.*
                        FROM
                            a_Personnel_CardRecord
                        WHERE
                            a_Personnel_CardRecord.fCode = '{$item['fCode']}'
                            AND a_Personnel_CardRecord.fDateTime >= '{$selectedDate->format('Y-m-d')}'
                            AND a_Personnel_CardRecord.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                        ORDER BY
                            a_Personnel_CardRecord.fDateTime ASC
                    ");
                    $cardRecordList->execute();

                    $now = new \DateTime();
                    $total = clone($now);
                    while ($cardRecord = $cardRecordList->fetch(\PDO::FETCH_ASSOC)) {
                        if ($cardRecord['fDateTimeUserIn'] !== null && $cardRecord['fDateTimeUserOut'] !== null) {
                            $userIn = new \DateTime($cardRecord['fDateTimeUserIn']);
                            $userOut = new \DateTime($cardRecord['fDateTimeUserOut']);
                            $total->add($userOut->diff($userIn));
                        }
                    }
                    $total = $total->diff($now);
                    $total = array(
                        'y' => $total->y,
                        'm' => $total->m,
                        'd' => $total->d,
                        'h' => $total->h,
                        'i' => $total->i,
                        's' => $total->s
                    );
                    $total['m'] += $total['y'] * 12;
                    $total['d'] += $total['m'] * 30;
                    $total['h'] += $total['d'] * 24;

                    $data[$i]['fTotalJamKerja'] = ($total['h'] < 10 ? '0' . $total['h'] : $total['h'])
                        . ':' . ($total['i'] < 10 ? '0' . $total['i'] : $total['i'])
                        . ':' . ($total['s'] < 10 ? '0' . $total['s'] : $total['s']);
                }

                if ($data == null) {
                    $this->flashMessenger->addMessage('Data pada tanggal ' . $selectedDate->format('d-m-Y') . ' kosong!');
                }

                $this->addSessionData('data', $data);
                $this->addSessionData('date', $selectedDate);
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
        $data = $this->getSessionData('data');
        $date = $this->getSessionData('date');

        $query = $this->databaseManager->prepare("
            INSERT INTO
                a_Personnel_PayrollMst (
                    fDateTime
                ) OUTPUT INSERTED.fId
            VALUES (
                '{$date->format('Y-m-d')}'
            )
        ");
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);
        $id = $result['fId'];

        foreach ($data as $item) {
            $query = $this->databaseManager->prepare("
                INSERT INTO
                    a_Personnel_PayrollItem (
                        fPayrollMstId,
                        fCode,
                        fNPWP,
                        fJamsostek,
                        fBankAccountName,
                        fBankAccountNo,
                        fTanggalPeriodeAwal,
                        fTanggalPeriodeAkhir,
                        fStatus,
                        fMarital,
                        fPay,
                        fBasicWage,
                        fTunjanganTetap,
                        fTunjanganSkill,
                        fTunjanganInsentif,
                        fTunjanganPajak,
                        fOverTimeOption,
                        fOverTimeRate,
                        fOverTimeMealRate,
                        fIncentiveRate,
                        fBonusIncentive,
                        fFixDeduct,
                        fUnionRetribution,
                        fTotalJamKerja,
                        fJamKerja,
                        fJamLembur,
                        fJumlahKehadiran,
                        fJumlahIjin,
                        fJumlahCuti,
                        fJumlahSakit,
                        fJamKerjaNormal,
                        fJamKerjaLembur,
                        fKoreksi,
                        fRemarks,
                        fDeduction
                    )
                VALUES (
                    {$id},
                    '{$item['fCode']}',
                    '-',
                    '-',
                    '-',
                    '-',
                    '{$date->format('Y-m-01')}',
                    '{$date->format('Y-m-t')}',
                    'C',
                    '-',
                    '-',
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    '00:00:00',
                    '{$item['fTotalJamKerja']}',
                    '00:00:00',
                    {$item['fJumlahHariKerja']},
                    {$item['fJumlahAbsen']},
                    {$item['fJumlahCuti']},
                    {$item['fJumlahSakit']},
                    0,
                    0,
                    0,
                    '-',
                    0
                )
            ");
            $query->execute();
        }

        $this->flashMessenger->addMessage('Data berhasil disimpan.');
        $this->routeManager->redirect(array('action' => 'report_monthly_payroll'));

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
        } else {
            $selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");
            $maxDate = new \DateTime('last day of this month');

            if ($selectedDate > $maxDate) {
                $errorMessages['global'][] = 'Tanggal yang dipilih melebihi batas akhir (bulan ini)';
            } else {
                $query = $this->databaseManager->prepare("
                    SELECT
                        COUNT(fId) as jumlah
                    FROM
                        a_Personnel_PayrollMst
                    WHERE
                        a_Personnel_PayrollMst.fDateTime >= '{$selectedDate->format('Y-m-01')}'
                        AND a_Personnel_PayrollMst.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                ");
                $query->execute();
                $masterPayroll = $query->fetch(\PDO::FETCH_ASSOC);

                if ($masterPayroll['jumlah'] > 0) {
                    $errorMessages['global'][] = 'Data payroll pada tanggal ' . $selectedDate->format('d-m-Y') . ' sudah ada!';
                }
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
