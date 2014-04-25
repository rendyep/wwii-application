<?php

$config = array(
    "module" => array(
        "Index" => array(
            "namespace" => "\\WWII\\Application\\Index",
            "controller" => array(
                "Index" => array(
                    "class" => "\\Index\\IndexController",
                ),
            ),
        ),
        "Error" => array(
            "namespace" => "\\WWII\\Application\\Error",
            "controller" => array(
                "Index" => array(
                    "class" => "\\Error\\ErrorController",
                ),
            ),
        ),
        "Erp" => array(
            "namespace" => "\\WWII\\Application\\Erp",
            "controller" => array(
                "Finding" => array(
                    "class" => "\\Finding\\FindingController",
                ),
                "ItInventory" => array(
                    "class" => "\\ItInventory\\ItInventoryController",
                ),
                "QualityControl" => array(
                    "class" => "\\QualityControl\\QualityControlController",
                ),
            ),
        ),
        "Hrd" => array(
            "namespace" => "\\WWII\\Application\\Hrd",
            "controller" => array(
                "Pelamar" => array(
                    "class" => "\\Pelamar\\PelamarController",
                ),
                "Karyawan" => array(
                    "class" => "\\Karyawan\\KaryawanController",
                ),
                "Cuti" => array(
                    "class" => "\\Cuti\\CutiController",
                ),
            ),
        ),
    ),
);


if (file_exists(__DIR__ . '/config.sensitive.php')) {
    $config = array_merge($config, include(__DIR__ . '/config.sensitive.php'));
}

return $config;
