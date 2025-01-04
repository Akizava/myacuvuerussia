<?php
const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';  // Определяем имя модуля

// Регистрация классов для автозагрузки
$classesMyAcuvueRussia = [
    'Akizava\MyAcuvueRussia\Base' => 'lib/Base.php',  // Класс Base и его путь
    'Akizava\MyAcuvueRussia\Events' => 'lib/Events.php',  // Класс Events и его путь
];

// Регистрируем классы для автозагрузки
\Bitrix\Main\Loader::registerAutoLoadClasses(ADMIN_MODULE_NAME, $classesMyAcuvueRussia);

// Получаем объект менеджера событий
$eventManager = \Bitrix\Main\EventManager::getInstance();

// Определяем массив зависимостей событий
$arDependences = [
    // Добавляем обработчик для события OnEpilog
    ["main", "OnEpilog", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "addHtml"],  // Событие, которое будет добавлять HTML в конец страницы
    /**
     * Новое поле для админки
     */
    ["sale", "OnSaleOrderBeforeSaved", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "orderSaved"],  // Событие обработки данных заказа перед его сохранением
    /**
    Сохранить данные для отправки
     */
];

// Проходим по массиву зависимостей и добавляем обработчики
foreach ($arDependences as $regArray) {
    $GLOBALS[$regArray[4]] = true;  // Устанавливаем глобальную переменную для каждого обработчика
    // Регистрируем обработчик события
    $eventManager->addEventHandler(
        $regArray[0],  // Модуль, который генерирует событие
        $regArray[1],  // Событие
        [$regArray[3], $regArray[4]]  // Класс и метод для обработки события
    );
}
?>
