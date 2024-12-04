<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
const ADMIN_MODULE_NAME = 'akizava.myacuvuerussia';
use Akizava\MyAcuvueRussia\Base;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
global $USER, $APPLICATION;
if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();
Loader::includeModule(ADMIN_MODULE_NAME);
$tabControl = new CAdminTabControl("tabControl", [
    [
        "DIV" => "edit1",
        "TAB" => "Основные",
        "TITLE" => "Основные настройки",
    ],
]);
$arFieldsPost = [
    'type_request' => 'checkbox',
    'url_test' => 'text',
    'url_prod' => 'text',
    'login' => 'text',
    'password' => 'text',
    'store_id' => 'text',
    'hash' => 'readonly',
];
if ((!empty($save) || !empty($restore) || !empty($request->getPost('create'))) && $request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {
        Option::delete(ADMIN_MODULE_NAME);
        CAdminMessage::showMessage([
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
    ) {
        foreach ($arFieldsPost as $field => $val) {
            $option = $request->getPost($field);
            Option::set(
                ADMIN_MODULE_NAME,
                $field,
                $option
            );
        }
        if ($request->getPost('create')) {
            $create = new Base();
            if ($create->getHash()) {
                CAdminMessage::showMessage([
                    "MESSAGE" => Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_CREATE_COMPLETE"),
                    "TYPE" => "OK",
                ]);
            } else {
                CAdminMessage::showMessage([
                    "MESSAGE" => Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_CREATE_ERROR"),
                    "TYPE" => "ERROR",
                ]);
            }
        }
    } else {
        CAdminMessage::showMessage("Введено неверное значение");
    }
}

$tabControl->begin();
?>

<form method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode(ADMIN_MODULE_NAME) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->beginNextTab();
    ?>
    <? foreach ($arFieldsPost as $field => $val): ?>
        <? $optionVal = Option::get(ADMIN_MODULE_NAME, $field, $akizava_myacuvuerussia_default_option[$val]); ?>
        <tr>
            <td width="40%">
                <label for="<?= $field ?>"><?= Loc::getMessage("AKIZAVA_MY_ACUVUE_RUSSIA_{$field}"); ?>:</label>
            </td>
            <td width="60%">
                <input type="<?= $val ?>" <?= $val == 'readonly' ? 'readonly' : '' ?> size="20"
                    <? if ($val == 'checkbox'): ?>
                        <?= $optionVal ? "checked" : "" ?>
                    <? else: ?>
                        value="<?= $optionVal ?>"
                    <? endif; ?>
                       name="<?= $field ?>">
            </td>
        </tr>
    <? endforeach; ?>
    <tr>
        <td width="40%">
            <input type="submit" name="create" value="Создать" title="Сохранить и создать" class="adm-btn-save">
        </td>
        <td width="60%">
        </td>
    </tr>
    <?php
    $tabControl->buttons();
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
    $tabControl->end();
    ?>
</form>