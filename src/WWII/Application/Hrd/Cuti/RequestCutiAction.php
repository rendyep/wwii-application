<?php

namespace WWII\Application\Hrd\Cuti;

class RequestCutiAction
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
    }

    public function dispatch($params)
    {
        switch (strtoupper($params['btx'])) {
            case 'PROSES':
                $result = $this->dispatchProses($params);
                break;
            case 'SIMPAN':
                $result = $this->dispatchSimpan($params);
                break;
            case 'BATAL':
                $session = $this->clearSessionData();
                $this->routeManager->redirect(array('action' => 'approvisasi_cuti'));
            case 'RESET':
            default:
                $session = $this->clearSessionData();
                break;
        }

        $this->render($result);
    }

    protected function dispatchProses($params)
    {
        $errorMessages = $this->validateData($params);

        if (empty($errorMessages)) {
            $data = $this->getDataKaryawan($params['fCode']);

            if (empty ($data)) {
                $errorMessages['global'][] = "Karyawan dengan NIK [{$params['fCode']}] tidak ditemukan!";
            } else {
                $now = new \DateTime();

                if (empty($params['tanggalAwal'])) {
                    $params['tanggalAwal'] = $now->format('m/d/Y');
                }

                if (empty($params['tanggalAkhir'])) {
                    $params['tanggalAkhir'] = $now->format('m/d/Y');
                }
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
        $errorMessages = $this->validateData($params);

        $data = $this->getDataKaryawan($params['fCode']);

        if (empty($errorMessages)) {
            $dateStart = new \DateTime($params['tanggalAwal']);
            $dateEnd = new \DateTime($params['tanggalAkhir']);

            $jumlahHari = $this->getJumlahHari($dateStart, $dateEnd);
            $data = $this->getDataCuti($params['fCode'], $dateStart);

            $sisa = $data['fSisaAktif'];
            if ($data['fSisaKumulatif'] > $sisa) {
                $sisa = $data['fSisaKumulatif'];
            }

            if ($sisa == 0) {
                $errorMessages['global'][] = "Karyawan tidak mempunyai sisa cuti!";
            } elseif ($sisa < $jumlahHari) {
                $errorMessages['global'][] = "Sisa cuti karyawan melebihi jumlah pengambilan cuti!";
            } else {
                while ($dateStart <= $dateEnd) {
                    if ($dateStart->format('w') != 0) {
                        $query = $this->databaseManager->prepare("
                            INSERT INTO
                                a_Personnel_CardRecord (
                                    fCode,
                                    fDateTime,
                                    fStatus,
                                    fApproved,
                                    fNote
                                )
                            VALUES (
                                '{$params['fCode']}',
                                '{$dateStart->format('Y-m-d')}',
                                'C',
                                0,
                                '{$params['fNote']}'
                            )
                        ");
                        $query->execute();
                    }

                    $dateStart->add(new \DateInterval('P1D'));
                }

                $this->flashMessenger->addMessage('Data berhasil disimpan!');
                $this->routeManager->redirect(array('action' => 'master_cuti'));
            }
        }

        return array(
            'errorMessages' => $errorMessages,
            'params' => $params,
            'data' => $data
        );
    }

    protected function getDataKaryawan($fCode)
    {
        $query = $this->databaseManager->prepare("
            SELECT
                t_PALM_PersonnelFileMst.fCode,
                t_PALM_PersonnelFileMst.fName,
                t_BMSM_DeptMst.fDeptName,
                t_PALM_PersonnelFileMst.fInDate
            FROM
                t_PALM_PersonnelFileMst
            LEFT JOIN
                t_BMSM_DeptMst ON t_BMSM_DeptMst.fDeptCode = t_PALM_PersonnelFileMst.fDeptCode
            WHERE
                t_PALM_PersonnelFileMst.fCode = '{$fCode}'
        ");
        $query->execute();

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    protected function getJumlahHari(\DateTime $dateStart, \DateTime $dateEnd)
    {
        $dateStart = clone($dateStart);

        $count = 0;
        while ($dateStart <= $dateEnd) {
            if ($dateStart->format('w') != 0) {
                $count++;
            }

            $dateStart->add(new \DateInterval('P1D'));
        }

        return $count;
    }

    protected function getDataCuti($fCode, \DateTime $selectedDate)
    {
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                    THEN
                        'ya'
                    ELSE
                        'tidak'
                END,
                fTanggalKadaluarsaAktif = (CASE
                    WHEN
                        DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                    THEN
                        CASE
                            WHEN
                                DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                        END
                    ELSE
                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                END),
                fLimitAktif = CASE
                    WHEN
                        DATEADD(YY, 1, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                        END)
                    THEN
                        1
                    ELSE
                        0
                END)),
                fTanggalKadaluarsaPasif = CASE
                    WHEN
                        DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                    THEN
                        CASE
                            WHEN
                                DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 3, t_PALM_PersonnelFileMst.fInDate)
                            ELSE
                                DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 9, t_PALM_PersonnelFileMst.fInDate)
                        END
                    ELSE
                        DATEADD(MM, DATEDIFF(MM, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 9, t_PALM_PersonnelFileMst.fInDate)
                END,
                fLimitPasif = CASE
                    WHEN
                        DATEADD(YY, 2, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                    THEN
                        12
                    ELSE
                        0
                END) + (CASE
                    WHEN
                        DATEADD(YY, 2, t_PALM_PersonnelFileMst.fInDate) <= (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') + 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
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
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 2, t_PALM_PersonnelFileMst.fInDate)
                        END)
                        AND a_Personnel_CardRecord.fDateTime < (CASE
                            WHEN
                                DATEPART(MM, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(MM, '{$selectedDate->format('Y-m-d')}')
                            THEN
                                CASE
                                    WHEN
                                        DATEPART(DD, t_PALM_PersonnelFileMst.fInDate) <= DATEPART(DD, '{$selectedDate->format('Y-m-d')}')
                                    THEN
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}'), t_PALM_PersonnelFileMst.fInDate)
                                    ELSE
                                        DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
                                END
                            ELSE
                                DATEADD(YY, DATEDIFF(YY, t_PALM_PersonnelFileMst.fInDate, '{$selectedDate->format('Y-m-d')}') - 1, t_PALM_PersonnelFileMst.fInDate)
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
                t_PALM_PersonnelFileMst.fCode = '{$fCode}'
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

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    protected function validateData($params)
    {
        $errorMessages = array();

        if (strtoupper($params['btx']) == 'PROSES') {
            if (empty($params['fCode'])) {
                $errorMessages['fCode'] = 'Harus diisi';
            }
        }

        if (strtoupper($params['btx']) == 'SIMPAN') {
            if (empty($params['tanggalAwal'])) {
                $errorMessages['tanggalAwal'] = 'harus diisi';
            } elseif (! empty($params['tanggalAkhir'])) {
                $dateStart = new \DateTime($params['tanggalAwal']);
                $dateEnd   = new \DateTime($params['tanggalAkhir']);

                if ($dateStart > $dateEnd) {
                    $errorMessages['tanggalAkhir'] = 'tidak boleh kurang dari tanggal awal';
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
        include('view/request_cuti.phtml');
        $this->templateManager->renderFooter();
    }
}
