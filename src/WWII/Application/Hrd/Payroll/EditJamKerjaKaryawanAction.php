<?php

namespace WWII\Application\Hrd\Payroll;

class EditJamKerjaKaryawanAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $errorMessages = array();

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
        $key = $this->routeManager->getKey();
        if (empty($key)) {
            $this->routeManager->redirect(array('action' => 'report_payroll'));
        }

        $key = explode(';', $key);
        if (count($key) != 2) {
            $this->routeManager->redirect(array('action' => 'report_payroll'));
        }

        foreach ($key as $param) {
            $param = explode(':', $param);
            $params[$param[0]] = $param[1];
        }

        switch (strtoupper($params['btx'])) {
            case 'PROSES':
                $result = $this->dispatchProses($params);
                break;
            case 'SIMPAN':
                $result = $this->dispatchSimpan($params);
                break;
            default:
                $this->clearSessionData();
                $result = $this->dispatchPrepare($params);
                break;
        }

        $this->render($result);
    }

    public function dispatchPrepare($params)
    {
        if (! empty($params)) {
            $selectedDate = new \DateTime($params['date']);

            $employeeDetail = $this->databaseManager->prepare("
                SELECT
                    t_PALM_PersonnelFileMst.fCode,
                    t_PALM_PersonnelFileMst.fName,
                    t_BMSM_DeptMst.fDeptName,
                    t_PALM_PersonnelFileMst.fIfContract,
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
                    a_Personnel_CardRecord.fCode = '{$params['fCode']}'
                    AND t_PALM_PersonnelFileMst.fDFlag = 0
                    AND a_Personnel_CardRecord.fDateTime >= '{$selectedDate->format('Y-m-d')}'
                    AND a_Personnel_CardRecord.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                GROUP BY
                    t_PALM_PersonnelFileMst.fCode,
                    t_PALM_PersonnelFileMst.fName,
                    t_PALM_PersonnelFileMst.fIfContract,
                    t_BMSM_DeptMst.fDeptName
                ORDER BY
                    t_PALM_PersonnelFileMst.fCode ASC
            ");
            $employeeDetail->execute();

            $data = array();
            while ($item = $employeeDetail->fetch(\PDO::FETCH_ASSOC)) {
                $data = $item;
            }

            $cardRecordList = $this->databaseManager->prepare("
                SELECT
                    a_Personnel_CardRecord.*
                FROM
                    a_Personnel_CardRecord
                WHERE
                    a_Personnel_CardRecord.fCode = '{$params['fCode']}'
                    AND a_Personnel_CardRecord.fDateTime >= '{$selectedDate->format('Y-m-d')}'
                    AND a_Personnel_CardRecord.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                ORDER BY
                    a_Personnel_CardRecord.fDateTime ASC
            ");
            $cardRecordList->execute();

            $data['cardRecord'] = array();
            $now = new \DateTime();
            $total = clone($now);
            while ($item = $cardRecordList->fetch(\PDO::FETCH_ASSOC)) {
                if (! empty($item['fDateTime'])) {
                    $item['fDateTime'] = new \DateTime($item['fDateTime']);
                }

                if (! empty($item['fDateTimeScheduledIn'])) {
                    $item['fDateTimeScheduledIn'] = new \DateTime($item['fDateTimeScheduledIn']);
                }

                if (! empty($item['fDateTimeScheduledOut'])) {
                    $item['fDateTimeScheduledOut'] = new \DateTime($item['fDateTimeScheduledOut']);
                }

                if (! empty($item['fDateTimeUserIn'])) {
                    $item['fDateTimeUserIn'] = new \DateTime($item['fDateTimeUserIn']);
                }

                if (! empty($item['fDateTimeUserOut'])) {
                    $item['fDateTimeUserOut'] = new \DateTime($item['fDateTimeUserOut']);
                }

                if (! empty($item['fDateTimeUserIn']) && ! empty($item['fDateTimeUserOut'])) {
                    $total->add($item['fDateTimeUserOut']->diff($item['fDateTimeUserIn']));
                }

                $data['cardRecord'][$item['fId']] = $item;
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

            $data['fTotalJamKerja'] = ($total['h'] < 10 ? '0' . $total['h'] : $total['h'])
                . ':' . ($total['i'] < 10 ? '0' . $total['i'] : $total['i'])
                . ':' . ($total['s'] < 10 ? '0' . $total['s'] : $total['s']);

            $this->addSessionData('data', $data);
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    public function dispatchProses($params)
    {
        $data = $this->getSessionData('data');

        $tanggal = $data['cardRecord'][$params['id']]['fDateTime'];
        $jadwalMasuk = $data['cardRecord'][$params['id']]['fDateTimeScheduledIn'];
        $jadwalKeluar = $data['cardRecord'][$params['id']]['fDateTimeScheduledOut'];
        $userMasuk = $data['cardRecord'][$params['id']]['fDateTimeUserIn'];
        $userKeluar = $data['cardRecord'][$params['id']]['fDateTimeUserOut'];

        if (! empty($params['jadwalMasuk'])) {
            $jadwalMasuk = new \DateTime($tanggal->format('Y-m-d ' . $params['jadwalMasuk']));
        }
        if (! empty($params['jadwalKeluar'])) {
            $jadwalKeluar = new \DateTime($tanggal->format('Y-m-d ' . $params['jadwalKeluar']));

            if ($jadwalKeluar < $jadwalMasuk) {
                $jadwalKeluar->add(new \DateInterval('P1D'));
            }
        }
        if (! empty($params['userMasuk'])) {
            $userMasuk = new \DateTime($tanggal->format('Y-m-d ' . $params['userMasuk']));
        }
        if (! empty($params['userKeluar'])) {
            $userKeluar = new \DateTime($tanggal->format('Y-m-d ' . $params['userKeluar']));

            if ($userKeluar < $userMasuk) {
                $userKeluar->add(new \DateInterval('P1D'));
            }
        }

        $data['cardRecord'][$params['id']]['fDateTimeScheduledIn'] = $jadwalMasuk;
        $data['cardRecord'][$params['id']]['fDateTimeScheduledOut'] = $jadwalKeluar;
        $data['cardRecord'][$params['id']]['fDateTimeUserIn'] = $userMasuk;
        $data['cardRecord'][$params['id']]['fDateTimeUserOut'] = $userKeluar;
        $data['cardRecord'][$params['id']]['fStatus'] = $params['status'];
        $data['cardRecord'][$params['id']]['fNote'] = $params['catatan'];

        $this->addSessionData('data', $data);

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    public function dispatchSimpan($params)
    {
        $data = $this->getSessionData('data');

        foreach ($data['cardRecord'] as $key => $cardRecord) {
            $query = $this->databaseManager->prepare("
                UPDATE
                    a_Personnel_CardRecord
                SET
                    fDateTimeScheduledIn = "
                        . ($cardRecord['fDateTimeScheduledIn'] === null ? "NULL" : "'{$cardRecord['fDateTimeScheduledIn']->format('Y-m-d H:i:s')}'") . ","
                    . " fDateTimeScheduledOut = "
                    . ($cardRecord['fDateTimeScheduledOut'] === null ? "NULL" : "'{$cardRecord['fDateTimeScheduledOut']->format('Y-m-d H:i:s')}'") . ","
                    . " fDateTimeUserIn = "
                    . ($cardRecord['fDateTimeUserIn'] === null ? "NULL" : "'{$cardRecord['fDateTimeUserIn']->format('Y-m-d H:i:s')}'") . ","
                    . " fDateTimeUserOut = "
                    . ($cardRecord['fDateTimeUserOut'] === null ? "NULL" : "'{$cardRecord['fDateTimeUserOut']->format('Y-m-d H:i:s')}'") . ","
                    . " fStatus = '{$cardRecord['fStatus']}',"
                    . " fNote = '{$cardRecord['fNote']}'
                WHERE
                    fId = {$key}
            ");
            $query->execute();
        }

        $this->flashMessenger->addMessage('Data berhasil disimpan.');
        $this->routeManager->redirect(array('action' => 'generate_monthly_payroll'));

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        //~if (empty($params['year']) || empty($params['month'])) {
            //~if (empty($params['year'])) {
                //~$errorMessages['year'] = 'Harus dipilih';
            //~}
//~
            //~if (empty($params['month'])) {
                //~$errorMessages['month'] = 'Harus dipilih';
            //~}
        //~} else {
            //~$selectedDate = new \DateTime("{$params['year']}-{$params['month']}-01");
            //~$maxDate = new \DateTime('last day of this month');
//~
            //~if ($selectedDate > $maxDate) {
                //~$errorMessages['global'][] = 'Tanggal yang dipilih melebihi batas akhir (bulan ini)';
            //~}
        //~}

        return $errorMessages;
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

    protected function render(array $result = null)
    {
        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/edit_jam_kerja_karyawan.phtml');
        $this->templateManager->renderFooter();
    }
}
