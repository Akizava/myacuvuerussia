<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Akizava\MyAcuvueRussia\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class myAcuvueAuth extends \CBitrixComponent
{
    private $_request;

    /**
     * Проверка наличия модулей требуемых для работы компонента
     * @return bool
     * @throws Exception
     */
    private function _checkModules()
    {
        if (!Loader::includeModule('akizava.myacuvuerussia')
        ) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }

        return true;
    }

    /**
     * Обертка над глобальной переменной
     * @return CAllMain|CMain
     */
    private function _app()
    {
        global $APPLICATION;
        return $APPLICATION;
    }

    /**
     * Обертка над глобальной переменной
     * @return CAllUser|CUser
     */
    private function _user()
    {
        global $USER;
        return $USER;
    }

    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams)
    {
        $this->_request = Application::getInstance()->getContext()->getRequest();
        // тут пишем логику обработки параметров, дополнение параметрами по умолчанию
        // и прочие нужные вещи
        $arParams['request'] = $this->_request;
        return $arParams;
    }

    /**
     * Точка входа в компонент
     * Должна содержать только последовательность вызовов вспомогательых ф-ий и минимум логики
     * всю логику стараемся разносить по классам и методам
     */
    public function executeComponent()
    {
        global $APPLICATION;
        $this->_checkModules();
        $this->_request = Application::getInstance()->getContext()->getRequest();
        $baseAcuvue = new Base();
        $typeRequest = $baseAcuvue->arOptions['type_request'] != 'Y';
        $this->arResult['SHOW_FORM'] = true;
        if ($this->_request["ajaxMyAcuvue"] && ($this->_request["ajaxMyAcuvue"] == "Y")) {
            if (check_bitrix_sessid()) {
                switch ($this->_request["action"]) {
                    case 'getOtp':
                        if ($this->_request["phone"]) {
                            $arSendOtp = $baseAcuvue->getOtp($this->_request["phone"]);
                            if ($arSendOtp['success']) {
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
                                if ($typeRequest) {
                                    $this->arResult['FIELDS'][] = [
                                        'type' => 'text',
                                        'readonly' => 'readonly',
                                        'style' => 'width: 100%;',
                                        'value' => 'Код из СМС подставлен автоматически'
                                    ];
                                }
                            } else {
                                $this->arResult['ERRORS'][] = $arSendOtp['message'];
                                $this->arResult['SHOW_FORM'] = false;
                            }
                        } else {
                            $this->arResult['ERRORS'][] = 'Не был передан телефон!';
                        }
                        break;
                    case 'sendOtp':
                        if ($this->_request["oneTimePin"]) {
                            $arSendOtp = $baseAcuvue->confirmOtp($this->_request["oneTimePin"], $this->_request["phoneOtp"]);
                            if ($arSendOtp['success']) {
                                $this->arResult['MESSAGES'][] = 'Вы успешно авторизованы!';
                                $this->arResult['SHOW_FORM'] = false;
                            } else {
                                $this->arResult['ERRORS'][] = 'Ошибка кода!';
                                $this->arResult['ERRORS'][] = $this->_request['phoneOtp'];
                            }
                        } else {
                            $this->arResult['ERRORS'][] = 'Не был передан код!';
                        }
                        break;
                    default:
                        break;
                }
            } else {
                $this->arResult['ERRORS'][] = 'Не верная сессия. Попробуйте обновить страницу';
                $this->arResult['SHOW_FORM'] = false;
            }
            $APPLICATION->RestartBuffer();
            $this->IncludeComponentTemplate();
            die();
        } else {
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
            $this->IncludeComponentTemplate();
        }
    }
}