<?php
// Задаем константу для имени модуля
const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';

// Подключаем необходимые файлы для работы с админской частью Bitrix
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

// Импортируем необходимые классы для работы с модулем Bitrix
use \Bitrix\Main\Loader,
    \Bitrix\Main\Application,
    \Akizava\MyAcuvueRussia\Base;

// Проверяем, загружены ли необходимые модули для работы с функциями
if (!Loader::includeModule('akizava.myacuvuerussia')) {
    throw new \Exception('Не загружены модули, необходимые для работы модуля');
}

// Получаем запрос от пользователя
$request = Application::getInstance()->getContext()->getRequest();

// Инициализируем переменную для результата с ошибкой
$result = ['success' => false, 'message' => 'Неправильно сформировалась форма отправки.<br> Обновите страницу и заново откройте окно.'];

// Создаем объект для работы с базовыми функциями модуля
$baseAcuvue = new Base();

// Обрабатываем различные типы запросов
switch ($request['actionType']) {
    case 'submitOrder': // Действие для отправки заказа
        // Формируем параметры для отправки заказа
        $arParams = [
            "clientOrderId" => $request['clientOrderId'],
            "consumerToken" => $request['consumerToken'],
            "materialCodes" => array_values($request['materialCodes']), // Преобразуем материал кода в массив
            "mobile" => $request['mobile'],
            "orderDate" => $request['orderDate'],
            "voucher" => $request['voucher'],
        ];
        // Выполняем отправку заказа
        $result = $baseAcuvue->submitOrder($arParams);
        break;
    case 'fulfillOrder': // Действие для выполнения заказа
        // Формируем параметры для выполнения заказа
        $arParams = [
            "lotNumbers" => array_values($request['lotNumbers']), // Преобразуем лоты в массив
            "clientOrderId" => $request['clientOrderId'],
            "orderNumber" => $request['orderNumber'],
        ];
        // Выполняем выполнение заказа
        $result = $baseAcuvue->fulfillOrder($arParams);
        break;
}

// Отправляем результат в формате JSON
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
