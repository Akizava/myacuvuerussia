<?php
const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';

$classesMyAcuvueRussia = [
    'Akizava\MyAcuvueRussia\Base' => 'lib/Base.php',
    'Akizava\MyAcuvueRussia\Events' => 'lib/Events.php',
];
\Bitrix\Main\Loader::registerAutoLoadClasses(ADMIN_MODULE_NAME, $classesMyAcuvueRussia);


$eventManager = \Bitrix\Main\EventManager::getInstance();
$arDependences = [
    ["main", "OnEpilog", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "addHtml"],
    /**
     * Новое поле для админки
     */
    ["sale", "OnSaleOrderBeforeSaved", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "orderSaved"],
    /**
    Сохранить данные для отправки
     */
];
foreach ($arDependences as $regArray) {
    $GLOBALS[$regArray[4]] = true;
    $eventManager->addEventHandler(
        $regArray[0],
        $regArray[1],
        [$regArray[3], $regArray[4]]
    );
}
?>