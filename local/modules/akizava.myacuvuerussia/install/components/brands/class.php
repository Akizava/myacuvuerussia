<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Akizava\MyAcuvueRussia\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();  // Проверка на доступность компонента через Bitrix

Loc::loadMessages(__FILE__);  // Загружаем локализационные файлы для текущего компонента

class myAcuvueBrands extends \CBitrixComponent
{
    private $_request;

    /**
     * Подготовка параметров компонента
     *
     * @param $arParams Массив параметров компонента
     *
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
     * Вся логика должна быть разнесена по классам и методам
     */
    public function executeComponent()
    {
        global $APPLICATION;
        $this->_checkModules();  // Проверяем подключение необходимых модулей
        $this->_request = Application::getInstance()->getContext()->getRequest();  // Получаем объект запроса
        $baseAcuvue = new Base();  // Создаем объект для работы с базовыми функциями
        $typeRequest = $baseAcuvue->arOptions['type_request'] != 'Y';  // Проверяем тип запроса
        $this->arResult['consumer'] = $_SESSION['consumer'];  // Получаем данные о текущем потребителе из сессии
        $arAssoc = [];  // Массив для хранения идентификаторов брендов

        // Собираем бренды из сессии
        foreach ($_SESSION['consumer']['fittedBrands'] as $filttedBrand) {
            $arAssoc[] = $filttedBrand['brandId'];  // Добавляем идентификатор бренда в массив
        }

        // Получаем фильтр по брендам для потребителя и сохраняем его в сессии
        $_SESSION['consumer']['filterBrandsBitrix'] = $this->arResult['arFilter'] = $baseAcuvue->getFilter($arAssoc);
        $this->IncludeComponentTemplate();  // Подключаем шаблон компонента
    }

    /**
     * Проверка наличия модулей, необходимых для работы компонента
     *
     * @return bool
     * @throws Exception
     */
    private function _checkModules()
    {
        if (!Loader::includeModule('akizava.myacuvuerussia')) {  // Проверяем, что модуль подключен
            throw new \Exception('Не загружены модули, необходимые для работы компонента');
        }

        return true;  // Возвращаем true, если модули подключены
    }
}
