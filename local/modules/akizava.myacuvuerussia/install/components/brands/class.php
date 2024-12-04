<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Akizava\MyAcuvueRussia\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class myAcuvueBrands extends \CBitrixComponent
{
    private $_request;

    /**
     * Подготовка параметров компонента
     *
     * @param $arParams
     *
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
        $this->arResult['consumer'] = $_SESSION['consumer'];
        $arAssoc = [];
        foreach ($_SESSION['consumer']['fittedBrands'] as $filttedBrand) {
            $arAssoc[] = $filttedBrand['brandId'];
        }
        $_SESSION['consumer']['filterBrandsBitrix'] = $this->arResult['arFilter'] = $baseAcuvue->getFilter($arAssoc);
        $this->IncludeComponentTemplate();
    }

    /**
     * Проверка наличия модулей требуемых для работы компонента
     *
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
}