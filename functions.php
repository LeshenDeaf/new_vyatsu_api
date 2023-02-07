<?php

require_once $_SERVER['DOCUMENT_ROOT']
	. '/bitrix/modules/main/include/prolog_before.php';

\CModule::IncludeModule("iblock");

define('STOP_STATISTICS', true);

$GLOBALS['APPLICATION']->RestartBuffer();

spl_autoload_register(function ($class_name) {
    if (stripos($class_name, 'bitrix') !== false) {
        return;
    }

    require_once $_SERVER["DOCUMENT_ROOT"]
        . "/api_mobile/v2/.ht_classes/"
        . str_replace('\\', '/', $class_name) . '.php';
});

