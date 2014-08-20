<?php

namespace WWII\Application\Hrd\Cuti;

class DetailMasterCutiAction
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $routeManager;

    protected $templateManager;

    protected $sessionContainer;

    protected $flashMessenger;

    protected $errorMessages = array();

    public function __construct(\WWII\Service\ServiceManagerInterface $serviceManager, \Doctrine\ORM\EntityManager $entityManager)
    {
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
        $key = $this->routeManager->getKey();
        if (empty($key)) {
            $this->routeManager->redirect(array('action' => 'master_cuti'));
        }

        switch (strtoupper($params['btx'])) {
            case 'KEMBALI' :
                $this->routeManager->redirect(array('action' => 'master_cuti'));
                break;
            default:
                $result = $this->dispatchFilter($params);
        }

        $this->render($result);
    }

    protected function dispatchFilter($params)
    {
        $key = $this->routeManager->getKey();
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
                t_PALM_PersonnelFileMst.fCode = '{$key}'
                AND t_PALM_PersonnelFileMst.fDFlag = 0
            GROUP BY
                t_PALM_PersonnelFileMst.fCode,
                t_PALM_PersonnelFileMst.fName,
                t_PALM_PersonnelFileMst.fInDate,
                t_BMSM_DeptMst.fDeptName
            ORDER BY
                t_PALM_PersonnelFileMst.fCode ASC
        ");
        $query->execute();
        $data = $query->fetch(\PDO::FETCH_ASSOC);

        $query = $this->databaseManager->prepare("
            WITH groupCardRecord AS (
                SELECT
                    a_Personnel_CardRecord.fCode,
                    a_Personnel_CardRecord.fDateTime,
                    a_Personnel_CardRecord.fStatus,
                    a_Personnel_CardRecord.fApproved,
                    a_Personnel_CardRecord.fNote,
                    fPeriode = CASE
                        WHEN
                            a_Personnel_CardRecord.fDateTime >= (CASE
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
                            'AKTIF'
                        WHEN
                            a_Personnel_CardRecord.fDateTime >= (CASE
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
                            'PASIF'
                    END,
                    groupId = DATEADD(
                        DD,
                        -1 * DENSE_RANK() OVER(
                            PARTITION BY
                                a_Personnel_CardRecord.fCode,
                                a_Personnel_CardRecord.fStatus,
                                a_Personnel_CardRecord.fApproved
                            ORDER BY a_Personnel_CardRecord.fDateTime
                        ),
                        a_Personnel_CardRecord.fDateTime
                    )
                FROM
                    a_Personnel_CardRecord
                    LEFT JOIN t_PALM_PersonnelFileMst ON t_PALM_PersonnelFileMst.fCode = a_Personnel_CardRecord.fCode
                WHERE
                    a_Personnel_CardRecord.fCode = '{$key}'
                    AND a_Personnel_CardRecord.fStatus = 'C'
                    AND a_Personnel_CardRecord.fDateTime >= DATEADD(
                        YY,
                        DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') - 1,
                        t_PALM_PersonnelFileMst.fInDate
                    )
                    AND a_Personnel_CardRecord.fDateTime < DATEADD(
                        YY,
                        DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$now->format('Y-m-d')}') + 1,
                        t_PALM_PersonnelFileMst.fInDate
                    )
            )
            SELECT
                groupCardRecord.fCode,
                fDateStart = MIN(groupCardRecord.fDateTime),
                fDateEnd = MAX(groupCardRecord.fDateTime),
                fDays = DATEDIFF(DD, MIN(groupCardRecord.fDateTime), MAX(groupCardRecord.fDateTime)) + 1,
                groupCardRecord.fStatus,
                groupCardRecord.fApproved,
                groupCardRecord.fPeriode,
                fNote = CAST(groupCardRecord.fNote AS VARCHAR(MAX))
            FROM
                groupCardRecord
            GROUP BY
                groupCardRecord.fCode,
                groupCardRecord.fStatus,
                groupCardRecord.fApproved,
                groupCardRecord.fPeriode,
                CAST(groupCardRecord.fNote AS VARCHAR(MAX)),
                groupCardRecord.groupId
            ORDER BY
                groupCardRecord.fCode ASC,
                groupCardRecord.fDateStart ASC
        ");
        $query->execute();
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $item) {
            if (strtoupper($item['fPeriode']) == 'AKTIF') {
                $data['items']['aktif'][] = $item;
            } else {
                $data['items']['pasif'][] = $item;
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    public function render(array $result = null)
    {
        if (! empty($result)) {
            extract($result);
        }

        $this->templateManager->renderHeader();
        include('view/detail_master_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
