<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>  <!-- Проверка, чтобы файл был включен через Bitrix -->

<? if ($_REQUEST["ajaxMyAcuvue"] != "Y"): ?>  <!-- Если запрос не является AJAX, начинаем обертку контейнера для авторизации -->
<div id="myAcuvueAuth" class="myAcuvueAuthContainer">
    <? endif; ?>

    <div class="myAcuvueAuthForm">  <!-- Форма для авторизации -->
        <i class="close fa fa-close" onclick="closeAcuvuePopup();"></i>  <!-- Кнопка для закрытия попапа -->
        <h4>Авторизация в системе MyAcuvue</h4>
        <form method="post" onsubmit="ajaxFormAcuvue(this,event)" action="">  <!-- Форма отправки данных через AJAX -->
            <div class="lds-ripple">  <!-- Анимация загрузки -->
                <div></div>
                <div></div>
            </div>

            <!-- Вывод ошибок из массива $arResult['ERRORS'] -->
            <? foreach ($arResult['ERRORS'] as $ERROR): ?>
                <p class="error"><?= $ERROR ?></p>
            <? endforeach; ?>

            <!-- Вывод сообщений из массива $arResult['MESSAGES'] -->
            <? foreach ($arResult['MESSAGES'] as $MESSAGE): ?>
                <p class="message"><?= $MESSAGE ?></p>
            <? endforeach; ?>

            <? if ($arResult['SHOW_FORM']): ?>  <!-- Если форма должна отображаться -->
                <?php
                echo bitrix_sessid_post();  // Генерация скрытого поля сессии для защиты от CSRF
                ?>
                <!-- Перебор полей для авторизации -->
                <? foreach ($arResult['FIELDS'] as $FIELD): ?>
                    <div class="myAcuvueAuthInput">
                        <input <? foreach ($FIELD as $key => $val): ?>  <!-- Генерация полей с аттрибутами -->
                            <?= $key ?>="<?= $val ?>"
                        <? endforeach; ?>
                        >
                    </div>
                <? endforeach; ?>

                <!-- Кнопка отправки формы -->
                <div class="myAcuvueAuthInput">
                    <input class="btn-default" type="submit">
                </div>
            <? endif; ?>
        </form>
    </div>

    <? if ($_REQUEST["ajaxMyAcuvue"] != "Y"): ?>  <!-- Закрываем контейнер только если запрос не AJAX -->
</div>

    <script>
        <? if (!$_SESSION['consumer']): ?>  <!-- Если нет данных о потребителе в сессии -->
        if (location.hash == '#acuvueAuth') {  <!-- Если URL содержит хеш #acuvueAuth -->
          document.getElementById('myAcuvueAuth').classList.add('show');  <!-- Показываем контейнер авторизации -->
        }
        <? endif; ?>
    </script>

<? endif; ?>
