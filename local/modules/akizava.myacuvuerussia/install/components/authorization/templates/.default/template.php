<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? if ($_REQUEST["ajaxMyAcuvue"] != "Y"): ?>
    <div id="myAcuvueAuth" class="myAcuvueAuthContainer">
        <? endif; ?>
        <div class="myAcuvueAuthForm">
            <i class="close fa fa-close" onclick="closeAcuvuePopup();"></i>
            <h4>Авторизация в системе MyAcuvue</h4>
            <form method="post" onsubmit="ajaxFormAcuvue(this,event)" action="">
                <div class="lds-ripple">
                    <div></div>
                    <div></div>
                </div>
                <? foreach ($arResult['ERRORS'] as $ERROR): ?>
                    <p class="error"><?= $ERROR ?></p>
                <? endforeach; ?>
                <? foreach ($arResult['MESSAGES'] as $MESSAGE): ?>
                    <p class="message"><?= $MESSAGE ?></p>
                <? endforeach; ?>
                <? if ($arResult['SHOW_FORM']): ?>
                    <?php
                    echo bitrix_sessid_post();
                    ?>
                    <? foreach ($arResult['FIELDS'] as $FIELD): ?>
                        <div class="myAcuvueAuthInput">
                            <input <? foreach ($FIELD as $key => $val): ?>
                                <?= $key ?>="<?= $val ?>"
                            <? endforeach; ?>
                            >
                        </div>
                    <? endforeach; ?>
                    <div class="myAcuvueAuthInput">
                        <input class="btn-default" type="submit">
                    </div>
                <? endif; ?>
            </form>
        </div>
        <? if ($_REQUEST["ajaxMyAcuvue"] != "Y"): ?>
    </div>

    <script>
        <? if (!$_SESSION['consumer']): ?>
        if (location.hash == '#acuvueAuth') {
            document.getElementById('myAcuvueAuth').classList.add('show');
        }
        <? endif; ?>
    </script>

<? endif; ?>