<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Akizava\MyAcuvueRussia\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();  // Проверка на доступность компонента через Bitrix

Loc::loadMessages(__FILE__);  // Загружаем локализационные файлы для текущего компонента

class myAcuvueAuth extends \CBitrixComponent
{
    private $_request;

    /**
     * Проверка наличия модулей, необходимых для работы компонента
     * @return bool
     * @throws Exception
     */
    private function _checkModules()
    {
        if (!Loader::includeModule('akizava.myacuvuerussia')) {  // Проверка, что модуль подключен
            throw new \Exception('Не загружены модули, необходимые для работы компонента');
        }

        return true;
    }

    /**
     * Обертка над глобальной переменной APPLICATION
     * @return CAllMain|CMain
     */
    private function _app()
    {
        global $APPLICATION;
        return $APPLICATION;  // Возвращаем глобальный объект APPLICATION
    }

    /**
     * Обертка над глобальной переменной USER
     * @return CAllUser|CUser
     */
    private function _user()
    {
        global $USER;
        return $USER;  // Возвращаем глобальный объект USER
    }

    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams)
    {
        $this->_request = Application::getInstance()->getContext()->getRequest();  // Получаем объект запроса
        // Логика обработки и дополнения параметров компонента значениями по умолчанию
        $arParams['request'] = $this->_request;
        return $arParams;
    }

    /**
     * Точка входа в компонент
     * Должна содержать только последовательность вызовов вспомогательных функций и минимум логики
     */
    public function executeComponent()
    {
        global $APPLICATION;
        $this->_checkModules();  // Проверяем подключение необходимых модулей
        $this->_request = Application::getInstance()->getContext()->getRequest();  // Получаем объект запроса
        $baseAcuvue = new Base();  // Создаем объект для работы с базовыми функциями
        $typeRequest = $baseAcuvue->arOptions['type_request'] != 'Y';  // Проверяем тип запроса
        $this->arResult['SHOW_FORM'] = true;  // Устанавливаем флаг на отображение формы

        // Обработка AJAX-запросов
        if ($this->_request["ajaxMyAcuvue"] && ($this->_request["ajaxMyAcuvue"] == "Y")) {
            if (check_bitrix_sessid()) {  // Проверяем корректность сессии
                switch ($this->_request["action"]) {
                    case 'getOtp':  // Действие для получения OTP
                        if ($this->_request["phone"]) {  // Проверяем, что телефон передан
                            $arSendOtp = $baseAcuvue->getOtp($this->_request["phone"]);  // Отправляем запрос на получение OTP
                            if ($arSendOtp['success']) {  // Если запрос успешен
                                // Формируем поля для отображения в форме
                                $this->arResult['FIELDS'] = [
                                    [
                                        'type' => 'hidden',
                                        'name' => 'ajaxMyAcuvue',
                                        'value' => 'Y',
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'phoneOtp',
                                        'value' => $this->_request["phone"],
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'action',
                                        'value' => 'sendOtp'
                                    ],
                                    [
                                        'type' => 'text',
                                        'name' => 'oneTimePin',
                                        'placeholder' => 'Код из СМС',
                                        'value' => $arSendOtp['oneTimePin'],
                                        'readonly' => $typeRequest
                                    ]
                                ];
                                if ($typeRequest) {  // Если тип запроса отличается
                                    $this->arResult['FIELDS'][] = [
                                        'type' => 'text',
                                        'readonly' => 'readonly',
                                        'style' => 'width: 100%;',
                                        'value' => 'Код из СМС подставлен автоматически'
                                    ];
                                }
                            } else {
                                $this->arResult['ERRORS'][] = $arSendOtp['message'];  // Добавляем ошибку, если не удалось получить OTP
                                $this->arResult['SHOW_FORM'] = false;  // Скрываем форму
                            }
                        } else {
                            $this->arResult['ERRORS'][] = 'Не был передан телефон!';  // Ошибка, если телефон не передан
                        }
                        break;
                    case 'sendOtp':  // Действие для отправки OTP
                        if ($this->_request["oneTimePin"]) {  // Проверяем, что код передан
                            $arSendOtp = $baseAcuvue->confirmOtp($this->_request["oneTimePin"], $this->_request["phoneOtp"]);  // Подтверждаем OTP
                            if ($arSendOtp['success']) {  // Если подтверждение успешно
                                $this->arResult['MESSAGES'][] = 'Вы успешно авторизованы!';  // Сообщаем об успешной авторизации
                                $this->arResult['SHOW_FORM'] = false;  // Скрываем форму
                            } else {
                                $this->arResult['ERRORS'][] = 'Ошибка кода!';  // Ошибка, если код неверный
                                $this->arResult['ERRORS'][] = $this->_request['phoneOtp'];
                            }
                        } else {
                            $this->arResult['ERRORS'][] = 'Не был передан код!';  // Ошибка, если код не передан
                        }
                        break;
                    default:
                        break;
                }
            } else {
                $this->arResult['ERRORS'][] = 'Не верная сессия. Попробуйте обновить страницу';  // Ошибка, если сессия не валидна
                $this->arResult['SHOW_FORM'] = false;  // Скрываем форму
            }
            $APPLICATION->RestartBuffer();  // Перезапускаем буфер
            $this->IncludeComponentTemplate();  // Подключаем шаблон компонента
            die();  // Завершаем выполнение компонента
        } else {
            // Инициализация полей формы для первого отображения
            $this->arResult['FIELDS'] = [
                [
                    'type' => 'hidden',
                    'name' => 'ajaxMyAcuvue',
                    'value' => 'Y'
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => 'getOtp'
                ],
                [
                    'type' => 'tel',
                    'name' => 'phone',
                    'placeholder' => 'Номер телефона для СМС'
                ]
            ];
            $this->IncludeComponentTemplate();  // Подключаем шаблон компонента
        }
    }
}
