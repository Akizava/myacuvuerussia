<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();  // Проверка на доступность компонента через Bitrix

const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';  // Определяем имя модуля

use Akizava\MyAcuvueRussia\Base;  // Подключаем класс Base для работы с функционалом модуля
use Bitrix\Main\Application;  // Подключаем класс Application для работы с запросами
use Bitrix\Main\Config\Option;  // Подключаем класс для работы с настройками
use Bitrix\Main\Loader;  // Подключаем класс для работы с модулями
use Bitrix\Main\Localization\Loc;  // Подключаем локализацию для вывода сообщений

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");  // Подключаем языковые файлы для модуля
Loc::loadMessages(__FILE__);  // Загружаем локализованные сообщения для текущего файла

global $USER, $APPLICATION;
if (!$USER->isAdmin()) {  // Проверка, является ли текущий пользователь администратором
    $APPLICATION->authForm('Nope');  // Если не администратор, перенаправляем на страницу авторизации
}

$app = Application::getInstance();  // Получаем объект приложения
$context = $app->getContext();  // Получаем контекст приложения
$request = $context->getRequest();  // Получаем объект запроса

Loader::includeModule(ADMIN_MODULE_NAME);  // Подключаем основной модуль

// Определяем вкладки для административной панели
$tabControl = new CAdminTabControl("tabControl", [
    [
        "DIV" => "edit1",
        "TAB" => "Основные",  // Название вкладки
        "TITLE" => "Основные настройки",  // Описание вкладки
    ],
]);

$arFieldsPost = [  // Определяем поля, которые будут отображаться в форме
    'type_request' => 'checkbox',
    'url_test' => 'text',
    'url_prod' => 'text',
    'login' => 'text',
    'password' => 'text',
    'store_id' => 'text',
    'hash' => 'readonly',
];

// Обработка отправки формы
if ((!empty($save) || !empty($restore) || !empty($request->getPost('create'))) && $request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {  // Если нажата кнопка "Восстановить"
        Option::delete(ADMIN_MODULE_NAME);  // Удаляем настройки
        CAdminMessage::showMessage([  // Показываем сообщение об успешном восстановлении
            "MESSAGE" => "Восстановлены настройки по умолчанию",
            "TYPE" => "OK",
        ]);
    } elseif (
        $request->getPost('type_request') ||
        $request->getPost('url_test') ||
        $request->getPost('url_prod') ||
        $request->getPost('login') ||
        $request->getPost('password') ||
        $request->getPost('hash')
    ) {  // Если форма была отправлена с данными
        foreach ($arFieldsPost as $field => $val) {  // Проходим по всем полям формы
            $option = $request->getPost($field);  // Получаем значение поля
            Option::set(
                ADMIN_MODULE_NAME,
                $field,
                $option  // Сохраняем значение в настройках модуля
            );
        }
        if ($request->getPost('create')) {  // Если нажата кнопка "Создать"
            $create = new Base();  // Создаем объект Base
            if ($create->getHash()) {  // Если хэш был получен
                CAdminMessage::showMessage([  // Показываем сообщение об успешном создании
                    "MESSAGE" => Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_CREATE_COMPLETE"),
                    "TYPE" => "OK",
                ]);
            } else {  // Если возникла ошибка при создании
                CAdminMessage::showMessage([  // Показываем сообщение об ошибке
                    "MESSAGE" => Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_CREATE_ERROR"),
                    "TYPE" => "ERROR",
                ]);
            }
        }
    } else {
        CAdminMessage::showMessage("Введено неверное значение");  // Показываем сообщение о неверном значении
    }
}

// Начинаем вывод вкладок и формы
$tabControl->begin();
?>

<form method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode(ADMIN_MODULE_NAME) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?php
    echo bitrix_sessid_post();  // Выводим скрытое поле сессии для защиты от CSRF атак
    $tabControl->beginNextTab();  // Переходим к следующей вкладке
    ?>
    <? foreach ($arFieldsPost as $field => $val): ?>  <!-- Проходим по всем полям для отображения их в форме -->
        <? $optionVal = Option::get(ADMIN_MODULE_NAME, $field, $akizava_myacuvuerussia_default_option[$val]); ?>  <!-- Получаем значение настройки из базы -->
        <tr>
            <td width="40%">
                <label for="<?= $field ?>"><?= Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_{$field}"); ?>:</label>  <!-- Выводим метку для поля -->
            </td>
            <td width="60%">
                <input type="<?= $val ?>" <?= $val == 'readonly' ? 'readonly' : '' ?> size="20"
                <? if ($val == 'checkbox'): ?>  <!-- Если это чекбокс -->
                    <?= $optionVal ? "checked" : "" ?>  <!-- Отмечаем чекбокс, если опция установлена -->
                <? else: ?>
                    value="<?= $optionVal ?>"  <!-- Для других типов полей выводим значение -->
                <? endif; ?>
                name="<?= $field ?>">
            </td>
        </tr>
    <? endforeach; ?>
    <tr>
        <td width="40%">
            <input type="submit" name="create" value="Создать" title="Сохранить и создать" class="adm-btn-save">  <!-- Кнопка для создания -->
        </td>
        <td width="60%">
        </td>
    </tr>
    <?php
    $tabControl->buttons();  // Выводим кнопки для сохранения и восстановления
    ?>
    <input type="submit"
           name="save"
           value="<?= Loc::getMessage("MAIN_SAVE") ?>"
           title="<?= Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
           class="adm-btn-save"
    >
    <input type="submit"
           name="restore"
           title="<?= Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?= Loc::getMessage("MAIN_RESTORE_DEFAULTS") ?>"
    >
    <?php
    $tabControl->end();  // Заканчиваем форму
    ?>
</form>
