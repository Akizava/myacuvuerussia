<?php

if (!check_bitrix_sessid()) {
    return;
}?>

<form action="<?echo $APPLICATION->GetCurPage()?>">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
    <input type="hidden" name="id" value="akizava.myacuvuerussia">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <input type="submit" name="del-module" value="Удалить модуль">
</form>