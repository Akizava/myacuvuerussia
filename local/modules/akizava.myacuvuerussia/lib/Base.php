<?php

namespace Akizava\MyAcuvueRussia;

use Bitrix\Main\Config\Option;

class Base
{
    // Название модуля и настройки по умолчанию
    public string $MODULE_NAME = 'akizava.myacuvuerussia';
    public array $arOptions = [
        'type_request' => '',  // Тип запроса (тест или продакшн)
        'url_test' => '',      // URL для тестового окружения
        'url_prod' => '',      // URL для продакшн окружения
        'login' => '',         // Логин для авторизации
        'password' => '',      // Пароль для авторизации
        'store_id' => '',      // ID магазина
    ];
    // Массив соответствий брендов
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

    // Конструктор класса
    public function __construct()
    {
        // Получаем настройки из конфигурации
        foreach ($this->arOptions as $keyOption => &$arOption) {
            $arOption = Option::get($this->MODULE_NAME, $keyOption);
        }
        // Устанавливаем URL в зависимости от типа запроса (продакшн или тест)
        $this->url = $this->arOptions['type_request'] ? $this->arOptions['url_prod'] : $this->arOptions['url_test'];
    }

    // Метод для получения OTP (одноразового пароля)
    public function getOtp($pnone): array
    {
        $arResult = [];
        $token = $this->getHash(); // Получаем токен
        if ($token) {
            $post = [
                'mobile' => $pnone, // Телефон для запроса
            ];
            // Формируем заголовок авторизации
            $authorization = "Authorization: Bearer " . $token;
            // Запрос на проверку мобильного телефона
            $responseCheck = $this->getCh($this->url, $post, 'POST', '/check-mobile', ['Content-Type: application/json', $authorization]);
            $jsonResponseCheck = json_decode($responseCheck, true);
            if ($jsonResponseCheck['isRegistered']) {
                // Если номер зарегистрирован, отправляем OTP
                $responseSend = $this->getCh($this->url, $post, 'POST', '/send-otp', ['Content-Type: application/json', $authorization]);
                $jsonResponseSend = json_decode($responseSend, true);
                if ($jsonResponseSend) {
                    $arResult = ['success' => true];
                } else {
                    $arResult = ['success' => false, 'message' => 'Номер не зарегистрирован в системе'];
                }
                // Если не продакшн, используем другой API для получения OTP
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

    // Метод для получения токена (или обновления)
    public function getHash()
    {
        $refresh = false;
        $str = '/token'; // Строка для запроса
        $action = 'GET'; // Тип запроса
        // Проверяем, есть ли токен в сессии
        if ($_SESSION['hash'] && !$_SESSION['hash']['fault']) {
            $existTime = time();
            $time = ceil($_SESSION['hash']['issued_at'] / 1000);
            $timeRefrechExpire = ceil(($_SESSION['hash']['issued_at'] + $_SESSION['hash']['refresh_token_expires_in']) / 1000);
            // Если время токена истекло, нужно обновить
            if ($time < $existTime) {
                $refresh = true;
            } else {
                // Если время обновления токена истекло, то обновляем токен
                if ($timeRefrechExpire < $existTime) {
                    $str = '/refresh';
                    $action = 'POST';
                    $refresh = true;
                }
            }
        } else {
            // Если токена нет, сразу обновляем
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
            // Выполняем запрос для получения нового токена
            $response = $this->getCh($this->url, $post, $action, $str, ['Content-Type: application/json']);
            $jsonResponse = json_decode($response, true);
            $_SESSION['hash'] = $jsonResponse;
        }
        return $_SESSION['hash']['access_token'];
    }

    // Метод для выполнения HTTP-запросов с помощью cURL
    private function getCh($url, $fields, $action, $str, $header, $debug = false)
    {
        $ch = curl_init($url . $str);
        if ($action == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1); // Если POST, устанавливаем соответствующий параметр
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Отключаем проверку SSL, если не POST
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); // Устанавливаем заголовки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Устанавливаем возврат ответа
        if ($fields) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE)); // Устанавливаем данные для POST запроса
        }
        if ($debug) {
            // Если отладка включена, выводим HTTP код ответа
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            d($http_code);
        }
        $response = curl_exec($ch); // Выполняем запрос
        curl_close($ch); // Закрываем cURL
        return $response;
    }

    // Метод для подтверждения OTP
    public function confirmOtp($otp, $phone): array
    {
        $arResult = [];
        $token = $this->getHash(); // Получаем токен
        if ($token) {
            $post = [
                'mobile' => $phone, // Телефон для подтверждения
                'oneTimePin' => $otp, // OTP
            ];
            $authorization = "Authorization: Bearer " . $token;
            // Отправляем запрос на подтверждение OTP
            $responseConfirm = $this->getCh($this->url, $post, 'POST', '/confirm-otp', ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['consumer']) {
                $arResult = ['success' => true];
                $_SESSION['consumer'] = $jsonResponseConfirm; // Сохраняем информацию о потребителе в сессии
            } else {
                $arResult = ['success' => false, 'message' => 'Номер не зарегистрирован в системе'];
            }
        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    // Метод для получения списка брендов
    public function getBrands()
    {
        $arResult = [];
        $token = $this->getHash(); // Получаем токен
        if ($token) {
            $post = [
                'consumerToken' => $_SESSION['consumer']['consumerToken'], // Токен потребителя
            ];
            $authorization = "Authorization: Bearer " . $token;
            // Выполняем запрос на получение брендов
            $responseCheck = $this->getCh($this->url, $post, 'GET', '/brands', ['Content-Type: application/json', $authorization], true);
            $jsonResponseCheck = json_decode($responseCheck, true);
            $arResult = ['success' => true, $jsonResponseCheck];
        } else {
            $arResult = ['success' => false, 'message' => 'Проблемы с авторизацией сервера'];
        }
        return $arResult;
    }

    // Метод для фильтрации брендов
    public function getFilter($arAssoc)
    {
        $arResult = [];
        foreach ($arAssoc as $item) {
            foreach ($this->brandsAssoc[$item] as $key => $elem) {
                $arResult[$key][] = $elem; // Добавляем элементы в результат
            }
        }
        // Добавляем элементы, которых нет в фильтре
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
     * Метод для отправки заказа
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
        $token = $this->getHash(); // Получаем токен
        if ($token) {
            $post = [
                "clientOrderId" => $arParams['clientOrderId'], // ID заказа
                "consumerToken" => $arParams['consumerToken'], // Токен потребителя
                "materialCodes" => $arParams['materialCodes'], // Материальные коды
                "mobile" => $arParams['mobile'], // Телефон клиента
                "orderDate" => $arParams['orderDate'], // Дата заказа
                "voucher" => $arParams['voucher'], // Купон
            ];
            $authorization = "Authorization: Bearer " . $token;
            $str = '/submit-order'; // Строка для запроса
            // Отправляем запрос на создание заказа
            $responseConfirm = $this->getCh($this->url, $post, 'POST', $str, ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['orderStatus'] == "NEW") {
                // Если заказ новый, сохраняем его в систему
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

    // Метод для сохранения заказа в Bitrix
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
                $order_property->setValue(json_encode($jsonResponseConfirm)); // Сохраняем данные о заказе
            }
        }
        $order->doFinalAction(true);
        return $order->save(); // Сохраняем заказ
    }

    /**
     * Метод для выполнения действия по выполнению заказа
     * @throws \Exception
     */
    public function fulfillOrder($arParams): array
    {
        $token = $this->getHash(); // Получаем токен
        if ($token) {
            $post = [
                "lotNumbers" => $arParams['lotNumbers'], // Лот-номера
            ];
            $authorization = "Authorization: Bearer " . $token;
            $str = '/fulfill-order/' . $arParams['orderNumber']; // Строка для выполнения заказа
            // Отправляем запрос для выполнения заказа
            $responseConfirm = $this->getCh($this->url, $post, 'POST', $str, ['Content-Type: application/json', $authorization]);
            $jsonResponseConfirm = json_decode($responseConfirm, true);
            if ($jsonResponseConfirm['orderStatus'] == "FULFILLED") {
                // Если заказ выполнен, сохраняем его
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
