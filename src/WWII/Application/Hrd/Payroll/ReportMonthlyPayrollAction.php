<?php

namespace WWII\Application\Hrd\Payroll;

class ReportMonthlyPayrollAction
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

            $query = $this->databaseManager->prepare("
                SELECT
                    *
                FROM
                    a_Personnel_PayrollMst
                WHERE
                    fDateTime = '{$selectedDate->format('Y-m-d')}'
            ");
            $query->execute();

            $data = $query->fetch(\PDO::FETCH_ASSOC);

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
                    t_BMSM_DeptMst.fDeptName,
                    a_Personnel_PayrollItem.fStatus,
                    a_Personnel_PayrollItem.fTanggalPeriodeAwal,
                    a_Personnel_PayrollItem.fTanggalPeriodeAkhir,
                    a_Personnel_PayrollItem.fBasicWage,
                    a_Personnel_PayrollItem.fTunjanganTetap,
                    a_Personnel_PayrollItem.fTunjanganSkill,
                    a_Personnel_PayrollItem.fTunjanganInsentif,
                    a_Personnel_PayrollItem.fJumlahKehadiran,
                    a_Personnel_PayrollItem.fJumlahIjin,
                    a_Personnel_PayrollItem.fJumlahCuti,
                    a_Personnel_PayrollItem.fJumlahSakit,
                    a_Personnel_PayrollItem.fJumlahHariLibur,
                    a_Personnel_PayrollItem.fJamKerjaUser
                FROM
                    a_Personnel_PayrollItem
                LEFT JOIN
                    a_Personnel_PayrollMst ON a_Personnel_PayrollMst.fId = a_Personnel_PayrollItem.fPayrollMstId
                LEFT JOIN
                    t_PALM_PersonnelFileMst ON t_PALM_PersonnelFileMst.fCode = a_Personnel_PayrollItem.fCode
                LEFT JOIN
                    t_BMSM_DeptMst ON t_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
                WHERE
                    a_Personnel_PayrollMst.fDateTime >= '{$selectedDate->format('Y-m-01')}'
                    AND a_Personnel_PayrollMst.fDateTime <= '{$selectedDate->format('Y-m-t')}'
                    {$optionalFilter}
                ORDER BY
                    a_Personnel_PayrollItem.fCode ASC
            ");
            $query->execute();

            $periode = new \DateTime($item['fDateTime']);

            $data['items'] = array();
            while($item = $query->fetch(\PDO::FETCH_ASSOC)) {
                $item['fTanggalPeriodeAwal'] = new \DateTime($item['fTanggalPeriodeAwal']);
                $item['fTanggalPeriodeAkhir'] = new \DateTime($item['fTanggalPeriodeAkhir']);

                $i = count($data['items']);
                $data['items'][$i] = $item;
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
        include('view/report_monthly_payroll.phtml');
        $this->templateManager->renderFooter();
    }
}
