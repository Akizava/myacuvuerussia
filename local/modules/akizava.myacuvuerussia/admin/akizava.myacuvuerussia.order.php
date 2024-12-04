<?
const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use \Bitrix\Main\Loader,
    \Bitrix\Main\Application,
    \Akizava\MyAcuvueRussia\Base;

if (!Loader::includeModule('akizava.myacuvuerussia')
) {
    throw new \Exception('Не загружены модули необходимые для работы модуля');
}
$request = Application::getInstance()->getContext()->getRequest();
$result = ['success' => false, 'message' => 'Неправильно сформировалась форма отправки.<br> Обновите страницу и заново откройте окно.'];
$baseAcuvue = new Base();
switch ($request['actionType']) {
    case 'submitOrder':
        $arParams = [
            "clientOrderId" => $request['clientOrderId'],
            "consumerToken" => $request['consumerToken'],
            "materialCodes" => array_values($request['materialCodes']),
            "mobile" => $request['mobile'],
            "orderDate" => $request['orderDate'],
            "voucher" => $request['voucher'],
        ];
        $result = $baseAcuvue->submitOrder($arParams);
        break;
    case 'fulfillOrder':
        $arParams = [
            "lotNumbers" => array_values($request['lotNumbers']),
            "clientOrderId" => $request['clientOrderId'],
            "orderNumber" => $request['orderNumber'],
        ];
        $result = $baseAcuvue->fulfillOrder($arParams);
        break;
}
echo json_encode($result, JSON_UNESCAPED_UNICODE);
