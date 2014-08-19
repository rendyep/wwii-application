<?php

namespace WWII\Application\Hrd\Cuti;

class MasterCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $departmentHelper;

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
        $this->departmentHelper = new \WWII\Common\Helper\Collection\MsSQL\Department(
            $this->serviceManager,
            $this->entityManager
        );
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

        $result['departmentList'] = $this->departmentHelper->getDepartmentList();

        $this->render($result);
    }

    public function dispatchFilter($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $now = new \DateTime();

            $query = $this->databaseManager->prepare("
                SELECT
                    t_PALM_PersonnelFileMst.fCode,
                    t_PALM_PersonnelFileMst.fName,
                    t_BMSM_DeptMst.fDeptName,
                    t_PALM_PersonnelFileMst.fInDate,
                    fHakCuti = CASE
                        WHEN
                            DATEADD(YY, 1, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            'ya'
                        ELSE
                            'tidak'
                    END,
                    fTanggalKadaluarsaAktif = (CASE
                        WHEN
                            DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                        THEN
                            CASE
                                WHEN
                                    DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                THEN
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                            END
                        ELSE
                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                    END),
                    fLimitAktif = CASE
                        WHEN
                            DATEADD(YY, 1, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END,
                    fApprovedAktif = SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END),
                    fPendingAktif = SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND (
                                a_Personnel_CardRecord.fApproved = 0
                                OR a_Personnel_CardRecord.fApproved IS NULL
                            )
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END),
                    fSisaAktif = (CASE
                        WHEN
                            DATEADD(YY, 1, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END) - (SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END)),
                    fTanggalKadaluarsaPasif = CASE
                        WHEN
                            DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                        THEN
                            CASE
                                WHEN
                                    DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                THEN
                                    DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 3, t_PALM_PersonnelFileMst.fInDate)
                                ELSE
                                    DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 9, t_PALM_PersonnelFileMst.fInDate)
                            END
                        ELSE
                            DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 9, t_PALM_PersonnelFileMst.fInDate)
                    END,
                    fLimitPasif = CASE
                        WHEN
                            DATEADD(YY, 2, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END,
                    fApprovedPasif = SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END),
                    fPendingPasif = SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND (
                                a_Personnel_CardRecord.fApproved = 0
                                OR a_Personnel_CardRecord.fApproved IS NULL
                            )
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END),
                    fSisaPasif = (CASE
                        WHEN
                            DATEADD(YY, 2, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END) - (SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END)),
                    fSisaKumulatif = (CASE
                        WHEN
                            DATEADD(YY, 1, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END) + (CASE
                        WHEN
                            DATEADD(YY, 2, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            12
                        ELSE
                            0
                    END) - (SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END)) - (SUM(CASE
                        WHEN
                            a_Personnel_CardRecord.fStatus = 'C'
                            AND a_Personnel_CardRecord.fApproved = 1
                            AND a_Personnel_CardRecord.fDateTime >= (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                            END)
                            AND a_Personnel_CardRecord.fDateTime < (CASE
                                WHEN
                                    DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$now->format('Y-m-d')}')
                                THEN
                                    CASE
                                        WHEN
                                            DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$now->format('Y-m-d')}')
                                        THEN
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                        ELSE
                                            DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    END
                                ELSE
                                    DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                            END)
                        THEN
                            1
                        ELSE
                            0
                    END))
                FROM
                    t_PALM_PersonnelFileMst
                    LEFT JOIN
                        a_Personnel_CardRecord ON a_Personnel_CardRecord.fCode = t_PALM_PersonnelFileMst.fCode
                    LEFT JOIN
                        t_BMSM_DeptMst ON t_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
                WHERE
                    t_PALM_PersonnelFileMst.fDFlag = 0"
                . (Empty($params['departemen']) ? ' ' : " AND t_BMSM_DeptMst.fDeptName = '{$params['departemen']}' ") .
                "
                GROUP BY
                    t_PALM_PersonnelFileMst.fCode,
                    t_PALM_PersonnelFileMst.fName,
                    t_PALM_PersonnelFileMst.fInDate,
                    t_BMSM_DeptMst.fDeptName
                ORDER BY
                    t_PALM_PersonnelFileMst.fCode ASC
            ");
            $query->execute();

            $data = $query->fetchAll(\PDO::FETCH_ASSOC);
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

        //~if (empty($params['fCode'])) {
            //~if (empty($params['fCode'])) {
                //~$errorMessages['fCode'] = 'Harus diisi';
            //~}
        //~}

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
        include('view/master_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
