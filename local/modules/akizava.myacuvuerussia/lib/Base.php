<?php

namespace Akizava\MyAcuvueRussia;

use Bitrix\Main\Config\Option;

class Base
{
    public string $MODULE_NAME = 'akizava.myacuvuerussia';
    public array $arOptions = [
        'type_request' => '',
        'url_test' => '',
        'url_prod' => '',
        'login' => '',
        'password' => '',
        'store_id' => '',
    ];
    public array $brandsAssoc = [
        "AOH1D" => ["%NAME" => "ACUVUE Oasys 1-Day"],
        "AOH" => ["%NAME" => "ACUVUE Oasys"],
        "AOHfA" => ["%NAME" => "ACUVUE Oasys for Astigmatism"],
        "AOH1DfA" => ["%NAME" => "ACUVUE Oasys 1 Day for Astigmatism"],
        "AOwT" => ["%NAME" => "ACUVUE Oasys with Transitions"],
        "1DAMfA" => ["%NAME" => "1-DAY ACUVUE Moist for Astigmatism"],
        "1DAM" => ["%NAME" => "1-DAY ACUVUE Moist"],
        "1DAMM" => ["%NAME" => "1-DAY ACUVUE Moist Multifocal"],
        "1DATE" => ["%NAME" => "1-Day ACUVUE TruEye"],
        "AOHM" => ["%NAME" => "OASYS MULTIFOCAL"],
        "ARL" => ["%NAME" => "RevitaLens"],
    ];
    private string $url;

    public function __construct()
    {
        foreach ($this->arOptions as $keyOption => &$arOption) {
            $arOption = Option::get($this->MODULE_NAME, $keyOption);
        }
        $this->url = $this->arOptions['type_request'] ? $this->arOptions['url_prod'] : $this->arOptions['url_test'];
    }

    public function getOtp($pnone): array
    {
        $arResult = [];
        $token = $this->getHash();
        if ($token) {
            $post = [
                'mobile' => $pnone,
            ];
            $authorization = "Authorization: Bearer " . $token;
            $responseCheck = $this->getCh($this->url, $post, 'POST', '/check-mobile', ['Content-Type: application/json', $authorization]);
            $jsonResponseCheck = json_decode($responseCheck, true);
            if ($jsonResponseCheck['isRegistered']) {
                $responseSend = $this->getCh($this->url, $post, 'POST', '/send-otp', ['Content-Type: application/json', $authorization]);
                $jsonResponseSend = json_decode($responseSend, true);
                if ($jsonResponseSend) {
                    $arResult = ['success' => true];
                } else {
                    $arResult = ['success' => false, 'message' => 'Номер не зарегистрирован в системе'];
                }
                if ($this->arOptions['type_request'] != 'Y') {
                    $authorization = "Authorization: Basic ". $token;
                    $responseGet = $this->getCh('https://stage.myacuvuepro.ru/onlineshop-pre-prod/consumer/get-otp/' . $pnone, [], 'GET', '', ['Content-Type: application/x-www-form-urlencoded', 'Cookie: JJCFGEOCC=ge', $authorization], true);
                    $jsonResponseGet = json_decode($responseGet, true);
                    if ($jsonResponseGet) {
                        $arResult['oneTimePin'] = $jsonResponseGet['oneTimePin'];
                    }
                }
            } else {
                $arResult = ['success' => false, 'message' => 'Номер не зарегистрирован в системе'];
            }
        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    public function getHash()
    {
        $refresh = false;
        $str = '/token';
        $action = 'GET';
        if ($_SESSION['hash'] && !$_SESSION['hash']['fault']) {
            $existTime = time();
            $time = ceil($_SESSION['hash']['issued_at'] / 1000);
            $timeRefrechExpire = ceil(($_SESSION['hash']['issued_at'] + $_SESSION['hash']['refresh_token_expires_in']) / 1000);
            if ($time < $existTime) {
                $refresh = true;
            } else {
                if ($timeRefrechExpire < $existTime) {
                    $str = '/refresh';
                    $action = 'POST';
                    $refresh = true;
                }
            }
        } else {
            $str = '/refresh';
            $action = 'POST';
            $refresh = true;
        }
        if ($refresh) {
            $post = [
                'client_id' => $this->arOptions['login'],
                'client_secret' => $this->arOptions['password'],
                'storeCode' => $this->arOptions['store_id'],
            ];
            $response = $this->getCh($this->url, $post, $action, $str, ['Content-Type: application/json']);
            $jsonResponse = json_decode($response, true);
            $_SESSION['hash'] = $jsonResponse;
        }
        return $_SESSION['hash']['access_token'];
    }

    private function getCh($url, $fields, $action, $str, $header, $debug = false)
    {
        $ch = curl_init($url . $str);
        if ($action == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($fields) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
        }
        if ($debug) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            d($http_code);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function confirmOtp($otp, $phone): array
    {
        $arResult = [];
        $token = $this->getHash();
        if ($token) {
            $post = [
                'mobile' => $phone,
                'oneTimePin' => $otp,
            ];
            $authorization = "Authorization: Bearer " . $token;
            $responseConfirm = $this->getCh($this->url, $post, 'POST', '/confirm-otp', ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['consumer']) {
                $arResult = ['success' => true];
                $_SESSION['consumer'] = $jsonResponseConfirm;
            } else {
                $arResult = ['success' => false, 'message' => 'Номер не зарегистрирован в системе'];
            }

        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    public function getBrands()
    {
        $arResult = [];
        $token = $this->getHash();
        if ($token) {
            $post = [
                'consumerToken' => $_SESSION['consumer']['consumerToken'],
            ];
            $authorization = "Authorization: Bearer " . $token;
            $responseCheck = $this->getCh($this->url, $post, 'GET', '/brands', ['Content-Type: application/json', $authorization], true);
            $jsonResponseCheck = json_decode($responseCheck, true);
            $arResult = ['success' => true, $jsonResponseCheck];
        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    public function getFilter($arAssoc)
    {
        $arResult = [];
        foreach ($arAssoc as $item) {
            foreach ($this->brandsAssoc[$item] as $key => $elem) {
                $arResult[$key][] = $elem;
            }
        }
        foreach ($this->brandsAssoc as $brandId => $arBrand) {
            if (!in_array($brandId, $arAssoc)) {
                foreach ($arBrand as $key => $elem) {
                    $arResult['!' . $key][] = $elem;
                }
            }
        }
        return $arResult;
    }

    /**
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function submitOrder($arParams): array
    {
        $token = $this->getHash();
        if ($token) {
            $post = [
                "clientOrderId" => $arParams['clientOrderId'],
                "consumerToken" => $arParams['consumerToken'],
                "materialCodes" => $arParams['materialCodes'],
                "mobile" => $arParams['mobile'],
                "orderDate" => $arParams['orderDate'],
                "voucher" => $arParams['voucher'],
            ];
            $authorization = "Authorization: Bearer " . $token;
            $str = '/submit-order';
            $responseConfirm = $this->getCh($this->url, $post, 'POST', $str, ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['orderStatus'] == "NEW") {
                $result = $this->setOrderJson($arParams, $jsonResponseConfirm);
                if ($result->getId()) {
                    $arResult = ['success' => true, 'message' => 'Успешная отправка заказа'];
                } else {
                    $arResult = ['success' => false, 'message' => 'Ошибка сохранения заказа'];
                }
            } else {
                $arResult = ['success' => false, 'message' => 'Заказ уже существует, обратитесь в Техподдержку', $jsonResponseConfirm];
            }

        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    public function setOrderJson($arParams, $jsonResponseConfirm): \Bitrix\Sale\Result
    {
        if (!\Bitrix\Main\Loader::includeModule('sale')
        ) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }
        $order = \Bitrix\Sale\Order::load($arParams['clientOrderId']);
        $propertyCollection = $order->getPropertyCollection();
        $arPropertyCollection = $propertyCollection->getArray();
        foreach ($arPropertyCollection['properties'] as $props) {
            if ($props['CODE'] == 'ACUVUE_ORDER') {
                $order_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                $order_property->setValue(json_encode($jsonResponseConfirm));
            }
        }
        $order->doFinalAction(true);
        return $order->save();
    }

    /**
     * @throws \Exception
     */
    public function fulfillOrder($arParams): array
    {
        $token = $this->getHash();
        if ($token) {
            $post = [
                "lotNumbers" => $arParams['lotNumbers'],
            ];
            $authorization = "Authorization: Bearer " . $token;
            $str = '/fulfill-order/' . $arParams['orderNumber'];
            $responseConfirm = $this->getCh($this->url, $post, 'POST', $str, ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['orderStatus'] == "FULFILLED") {
                $result = $this->setOrderJson($arParams, $jsonResponseConfirm);
                if ($result->getId()) {
                    $arResult = ['success' => true, 'message' => 'Успешная отправка заказа'];
                } else {
                    $arResult = ['success' => false, 'message' => 'Ошибка сохранения заказа'];
                }
            } else {
                $arResult = ['success' => false, 'message' => $jsonResponseConfirm['message'] . ' Обратитесь в Техподдержку'];
            }
        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }
}