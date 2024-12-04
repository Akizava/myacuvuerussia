<?php

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


Loc::loadMessages(__FILE__);

class akizava_myacuvuerussia extends CModule
{
    public static array $arDependences = [
        ["main", "OnEpilog", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "addHtml"],
        /**
         * Новое поле для админки
         */
        ["sale", "OnSaleOrderBeforeSaved", "akizava.myacuvuerussia", "\Akizava\MyAcuvueRussia\Events", "orderSaved"],
        /**
         * Сохранить данные для отправки
         */
    ];
    public $MODULE_ID = "akizava.myacuvuerussia";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = [];
        include(dirname(__FILE__) . "/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_PARTNER_URI");
        $this->MODULE_SORT = 1;
    }

    /**
     * Основной метод по установке всего необходимого
     *
     * TODO low: Отдельные методы для агентов, событий и прочего
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        RegisterModule($this->MODULE_ID);
        include('/local/modules/akizava.myacuvuerussia/default_option.php');
        foreach ($akizava_myacuvuerussia_default_option as $key => $value) {
            Option::set(
                $this->MODULE_ID,
                $key,
                $value
            );
        }

        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallDB();
        \Akizava\MyAcuvueRussia\Events::controlProps();
        $APPLICATION->IncludeAdminFile(
            "Установка модуля",
            $DOCUMENT_ROOT . "/local/modules/akizava.myacuvuerussia/install/step.php"
        );
    }

    /**
     * Устанавливает все необходимые компоненты, шаблоны, виджеты и админские скрипты
     * TODO medium: Установка должна происходить в ядро, а не в local. local уже служит для модификации
     *
     * @return bool
     */
    function InstallFiles(): bool
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/admin",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin",
            true,
            true
        );
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/components",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components/{$this->MODULE_ID}/",
            true,
            true
        );
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/acuvue",
            $_SERVER["DOCUMENT_ROOT"] . "/acuvue",
            true,
            true
        );
        return true;
    }

    /**
     * Метод устанавливает необходимые таблицы и сущности
     *
     * @return false|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function InstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            //Их нет, но возможно потребуется
            return true;
        }
    }

    function InstallEvents(): bool
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::$arDependences as $regArray) {
            $eventManager->registerEventHandler($regArray[0], $regArray[1], $regArray[2], $regArray[3], $regArray[4]);
        }
        return true;
    }

    /**
     * Основной метод по удалению всего необходимого
     *
     * @return void
     * @throws \Bitrix\Main\SystemException
     */
    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        Loader::includeModule($this->MODULE_ID);

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request["step"] < 2) {
            $APPLICATION->IncludeAdminFile(
                "Деинсталляция модуля",
                $DOCUMENT_ROOT . "/local/modules/akizava.myacuvuerussia/install/unstep.php"
            );
        } elseif ($request["step"] == 2) {
            \Akizava\MyAcuvueRussia\Events::controlProps(2);
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->UnInstallDB();
            unRegisterModule($this->MODULE_ID);
        }
    }

    /**
     * Удаляет все установленные компоненты, шаблоны, виджеты и админские скрипты
     * TODO low: Удаление должно быть из ядра
     *
     * @return bool
     */
    function UnInstallFiles()
    {
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/admin",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin"
        );
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/components",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components/{$this->MODULE_ID}/"
        );
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/akizava.myacuvuerussia/install/acuvue",
            $_SERVER["DOCUMENT_ROOT"] . "/acuvue",
        );
        return true;
    }

    function UnInstallEvents(): bool
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::$arDependences as $regArray) {
            $eventManager->unRegisterEventHandler($regArray[0], $regArray[1], $regArray[2], $regArray[3], $regArray[4]);
        }
        return true;
    }

    /**
     * Метод удаляет необходимые таблицы и сущности
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function UninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            //Их нет, но возможно потребуется
            return true;
        }
    }
}

