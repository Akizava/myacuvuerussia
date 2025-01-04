<?php

namespace Akizava\MyAcuvueRussia;

use CJSCore,
    CModule,
    CSaleOrderProps,
    CSaleOrderPropsGroup,
    CSalePersonType,
    CSite,
    Bitrix\Sale;


class Events
{

    public function addHtml()
    {
        $backtrace = debug_backtrace();  // Получаем стек вызовов
        $workMode = false;  // Режим работы (заказ или отправка)
        $check = ($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];  // Проверяем текущий путь
        $workType = false;  // Тип работы (стандартный или другой)

        // Если это страницы с деталями заказа или просмотра заказа
        if (
            strpos($check, "/bitrix/admin/sale_order_detail.php") !== false ||
            strpos($check, "/bitrix/admin/sale_order_view.php") !== false
        ) {
            $workMode = 'order';  // Устанавливаем режим работы как заказ
            $workType = 'standard';  // Стандартный тип
        } elseif (strpos(
                $_SERVER['PHP_SELF'],
                "/bitrix/admin/sale_order_shipment_edit.php"
            ) !== false && self::canShipment()) {
            $workMode = 'shipment';  // Режим работы как отправка
            $workType = 'standard';  // Стандартный тип
        }

        // Если глобальная переменная для текущей функции существует
        if ($GLOBALS[$backtrace[0]['function']]) {
            // Если установлен режим работы и тип
            if ($workMode && $workType) {
                $consumer = [];
                $filter = [];
                $orderAcuvue = [];
                // Загружаем заказ по ID
                $order = \Bitrix\Sale\Order::load($_REQUEST['ID']);
                $orderDate = $order->getDateInsert();  // Получаем дату создания заказа
                $propertyCollection = $order->getPropertyCollection();  // Получаем коллекцию свойств заказа
                $arPropertyCollection = $propertyCollection->getArray();  // Преобразуем коллекцию в массив

                // Перебираем свойства заказа
                foreach ($arPropertyCollection['properties'] as $props) {
                    if ($props['CODE'] == 'ACUVUE_CONSUMER_JSON') {
                        // Если свойство соответствует 'ACUVUE_CONSUMER_JSON', сохраняем потребителя
                        $consumer_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $consumer = json_decode($consumer_property->getValue(), true);
                    }
                    if ($props['CODE'] == 'ACUVUE_FILTER') {
                        // Если свойство соответствует 'ACUVUE_FILTER', сохраняем фильтры
                        $filter_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $filter = json_decode($filter_property->getValue(), true);
                    }
                    if ($props['CODE'] == 'ACUVUE_ORDER') {
                        // Если свойство соответствует 'ACUVUE_ORDER', сохраняем заказ
                        $order_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $orderAcuvue = json_decode($order_property->getValue(), true);
                    }
                }

                // Массив для товаров, которые будут отправлены в Acuvue
                $arBasketSendAcuvue = [];
                // Если есть данные о потребителе и фильтры
                if ($consumer && $filter) {
                    $basket = $order->getBasket();  // Получаем корзину из заказа
                    // Перебираем товары в корзине
                    foreach ($basket as $key => $basketItem) {
                        $addItem = false;  // Флаг для добавления товара
                        $title = $basketItem->getField('NAME');  // Получаем название товара
                        $id = $basketItem->getId();  // Получаем ID товара

                        // Проверяем товары по фильтрам
                        foreach ($filter['%NAME'] as $name) {
                            if (stripos($title, $name) !== false) {
                                $addItem = true;  // Добавляем товар, если имя совпадает
                            }
                        }

                        // Если товар соответствует фильтрам, добавляем его в массив
                        foreach ($filter['!%NAME'] as $name) {
                            if (stripos($title, $name) !== false) {
                                $addItem = false;  // Не добавляем товар, если имя совпадает с исключением
                            }
                        }

                        // Если товар подходит по фильтрам, добавляем его в массив
                        if ($addItem) {
                            $arBasketSendAcuvue[] = [
                                "ID" => $id,
                                "NAME" => $title,
                            ];
                        }
                    }

                    // Если есть товары для отправки в Acuvue, выводим форму и стили
                    if ($arBasketSendAcuvue) {
                        CJSCore::Init(["jquery"]);  // Инициализируем jQuery
                        ?>
                        <style>
                            /* Стили для отображения формы MyAcuvue */
                            .MyAcuvueInput {
                                width: 100%;
                                display: flex;
                                flex-wrap: wrap;
                            }
                            .MyAcuvueInput input {
                                width: 100%;
                                margin: 10px !important;
                            }
                            .MyAcuvueInput label {
                                width: 100%;
                                padding: 10px;
                            }
                            .MyAcuvueSubmit {
                                width: 100%;
                                padding: 10px;
                            }
                            .MyAcuvueAccordion img {
                                display: none;
                            }
                            .MyAcuvueAccordion.active img {
                                display: block;
                            }
                        </style>
                        <script>
                          // Скрипт для отображения и обработки окна MyAcuvue
                          let MyAcuvue_existedInfo = {
                            load: function() {
                              if ($('.adm-detail-toolbar').find('.adm-detail-toolbar-right').length) {
                                $('.adm-detail-toolbar').find('.adm-detail-toolbar-right').prepend(
                                    '<a href=\'javascript:void(0)\' onclick=\'MyAcuvue_existedInfo.showWindow()\' class=\'adm-btn\' id=\'MyAcuvue_btn\'>MyAcuvue</a>');
                              }
                            },
                            wnd: false,
                            showWindow: function() {
                              if (!MyAcuvue_existedInfo.wnd) {
                                var html = $('#MyAcuvue_wndOrder').html();
                                $('#MyAcuvue_wndOrder').html('');
                                MyAcuvue_existedInfo.wnd = new BX.CDialog({
                                  title: 'MyAcuvue',
                                  content: html,
                                  icon: 'head-block',
                                  resizable: true,
                                  draggable: true,
                                  height: '500',
                                  width: '550',
                                  buttons: [],
                                });
                              }
                              MyAcuvue_existedInfo.wnd.Show();
                            },
                          };
                          document.addEventListener('DOMContentLoaded', function() {
                            MyAcuvue_existedInfo.load();
                            getElementByText('ACUVUE').parentElement.style.display = 'none';
                            if (window.location.hash === '#MyAcuvueOrder') {
                              MyAcuvue_existedInfo.showWindow();
                            }
                          });

                          // Функция для нахождения элемента по тексту
                          function getElementByText(text) {
                            const xpath = `//node()[normalize-space(text())='${text.trim()}']`;
                            return document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
                          }

                          // Функция для отправки формы с материалами
                          function sendMyAcuvueOrder(event) {
                            event.preventDefault();
                            let target = event.target,
                                formData = new FormData(target);
                            $.ajax({
                              url: target.action,
                              type: 'POST',
                              data: formData,
                              processData: false,
                              contentType: false,
                              success: function(response) {
                                let data = JSON.parse(response);
                                target.insertAdjacentHTML('beforebegin',
                                    `<h3>${data.message}</h3><br>
                                         <a href="#MyAcuvueOrder" onclick="reloadWithMyAcuvueOrder()">Обновить страницу</a>`);
                                target.remove();
                              },
                            });
                          }

                          // Функция для перезагрузки страницы с обновленным хешем
                          function reloadWithMyAcuvueOrder() {
                            if (window.location.hash !== '#MyAcuvueOrder') {
                              window.location.href += '#MyAcuvueOrder';
                            }
                            location.reload();
                          }
                        </script>
                        <div style='display:none' id='MyAcuvue_wndOrder'>
                            <?php
                            // Проверяем, если заказ выполнен, выводим таблицу с данными
                            if ($orderAcuvue['orderStatus'] == "FULFILLED"): ?>
                                <table>
                                    <?php
                                    foreach ($orderAcuvue as $key => $value): ?>
                                        <tr>
                                            <td><?= $key ?></td>
                                            <td>
                                                <pre><?php print_r($value) ?></pre>
                                            </td>
                                        </tr>
                                    <?php
                                    endforeach; ?>
                                </table>
                            <?php
                            else: ?>
                                <form id="MyAcuvueOrder" action="/bitrix/admin/akizava.myacuvuerussia.order.php" onsubmit="sendMyAcuvueOrder(event);">
                                    <a href="#MyAcuvue_wndOrder" class="MyAcuvueAccordion" onclick="$(this).toggleClass('active');">
                                        <p>Памятка для данных materialCodes/lotNumbers</p>
                                        <img src="/local/modules/akizava.myacuvuerussia/assets/img/info.png">
                                    </a>
                                    <?php
                                    // Если данные заказа еще не заполнены
                                    if (!$orderAcuvue): ?>
                                        <h3>MaterialCodes для заказа №<?= $_REQUEST['ID'] ?></h3>
                                        <h3>Заполнять после получения заказа</h3>
                                        <input type="hidden" name="actionType" value="submitOrder">
                                        <input type="hidden" name="clientOrderId" value="<?= $_REQUEST['ID'] ?>">
                                        <input type="hidden" name="consumerToken" value="<?= $consumer['consumerToken'] ?>">
                                        <input type="hidden" name="mobile" value="<?= $consumer['consumer']['mobile'] ?>">
                                        <input type="hidden" name="orderDate" value="<?= $orderDate->format("Y-m-d") ?>">
                                        <input type="hidden" name="voucher" value="">
                                        <?php
                                        foreach ($arBasketSendAcuvue as $item) {
                                            ?>
                                            <div class="col-md-12 MyAcuvueInput">
                                                <label for="materialCodes<?= $item['ID'] ?>">
                                                    <?= $item['NAME']; ?>
                                                </label>
                                                <input type="text" id="materialCodes<?= $item['ID'] ?>" name="materialCodes[<?= $item['ID'] ?>]">
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <div class="col-md-12 MyAcuvueSubmit">
                                            <input type="submit" value="Отправить materialCodes">
                                        </div>
                                    <?php
                                    else: ?>
                                        <h3>lotNumbers для заказа №<?= $_REQUEST['ID'] ?> / Acuvue - №<?= $orderAcuvue['orderNumber'] ?></h3>
                                        <h3>Заполнять перед отдачей клиенту</h3>
                                        <input type="hidden" name="actionType" value="fulfillOrder">
                                        <input type="hidden" name="clientOrderId" value="<?= $_REQUEST['ID'] ?>">
                                        <input type="hidden" name="orderNumber" value="<?= $orderAcuvue['orderNumber'] ?>">
                                        <?php
                                        foreach ($arBasketSendAcuvue as $item) {
                                            ?>
                                            <div class="col-md-12 MyAcuvueInput">
                                                <label for="lotNumbers<?= $item['ID'] ?>">
                                                    <?= $item['NAME']; ?>
                                                </label>
                                                <input type="text" id="lotNumbers<?= $item['ID'] ?>" name="lotNumbers[<?= $item['ID'] ?>]">
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <div class="col-md-12 MyAcuvueSubmit">
                                            <input type="submit" value="Отправить lotNumbers">
                                        </div>
                                    <?php
                                    endif; ?>
                                    <a href="#MyAcuvue_wndOrder" class="MyAcuvueAccordion" onclick="$(this).toggleClass('active');">
                                        <p>Памятка для данных materialCodes/lotNumbers</p>
                                        <img src="/local/modules/akizava.myacuvuerussia/assets/img/info.png">
                                    </a>
                                </form>
                            <?php
                            endif; ?>
                        </div>
                        <?php
                    }
                }
            } else {
                // Если не в админке, показываем компонент авторизации
                if (!defined("ADMIN_SECTION") && ADMIN_SECTION !== true) {
                    global $APPLICATION;
                    $APPLICATION->IncludeComponent(
                        "akizava.myacuvuerussia:authorization",
                        "",
                        [
                            'AJAX_MODE' => 'Y',
                        ],
                        false,
                        ['HIDE_ICONS' => 'Y']
                    );
                    ?>
                    <style>
                        .bx_catalog_item_stickers li.sticker_acuvue {
                            background: #17a6b6;
                            box-shadow: 0px 6px 13px 0px rgba(23, 166, 182, 0.39);
                        }
                    </style>
                    <script>
                        let bx_catalog_item_title = $('.bx_catalog_item_title'),
                            basket_item_info_name_link = $('.basket-item-info-name-link'),
                            bx_soa_item_title = $('#bx-soa-basket .bx-soa-item-title'),
                            arFilterName = <?=json_encode($_SESSION['consumer']['filterBrandsBitrix'])?>;
                        if (bx_catalog_item_title.length) {
                            for (let i = 0; i < bx_catalog_item_title.length; i++) {
                                let title = bx_catalog_item_title[i].innerText;

                                if (checkName(title)) {
                                    bx_catalog_item_title[i].nextElementSibling.insertAdjacentHTML("afterBegin", "<li class='sticker_acuvue'>Подобрано MyAcuvue</li>");
                                }
                            }
                        }
                        if (basket_item_info_name_link.length) {
                            let strAdd = [];
                            for (let i = 0; i < basket_item_info_name_link.length; i++) {
                                let title = basket_item_info_name_link[i].innerText;
                                if (checkName(title)) {
                                    strAdd.push(title);
                                }
                            }
                            if (strAdd.length) {
                                document.getElementById('basket-root').insertAdjacentHTML("afterBegin", '<div class="col-md-12 basket-items-list-wrapper basket-items-list-header">В корзине есть товары, по которым будут начислены очки лояльности MyAcuvue:<br>' + strAdd.join(' , ') + '</div>');
                            }
                        }
                        if (bx_soa_item_title.length) {
                            let strAdd = [];
                            for (let i = 0; i < bx_soa_item_title.length; i++) {
                                let title = bx_soa_item_title[i].innerText;
                                if (checkName(title)) {
                                    strAdd.push(title);
                                }
                            }
                            if (strAdd.length) {
                                document.getElementById('bx-soa-order-form').insertAdjacentHTML("beforebegin", '<div class="col-md-12 bx-soa-cart-total">В корзине есть товары, по которым будут начислены очки лояльности MyAcuvue:<br>' + strAdd.join(' , ') + '</div>');
                            }
                        }

                        function checkName(title) {
                            let result = false;
                            arFilterName['%NAME'].forEach(function (name) {
                                if (title.indexOf(name) >= 0) {
                                    result = true;
                                }
                            });
                            arFilterName['!%NAME'].forEach(function (name) {
                                if (title.indexOf(name) >= 0) {
                                    result = false;
                                }
                            });
                            return result;
                        }
                    </script>
                    <?php
                }
            }
            $GLOBALS[$backtrace[0]['function']] = false;
        }
    }


    public function orderSaved(\Bitrix\Main\Event $event)
    {
        // Проверяем, успешно ли прошла настройка свойств (добавление/обновление)
        if (!self::controlProps()) {
            return;
        }

        global $USER;
        $userId = $USER->GetID();  // Получаем ID текущего пользователя

        // Если в сессии есть данные о потребителе
        if ($_SESSION['consumer']) {
            // Определяем свойства, которые нужно сохранить
            $arConsumerProps = [
                'consumer',  // Данные о потребителе
                'points',    // Очки потребителя
                'consumerToken',  // Токен потребителя
            ];

            // Массив для хранения значений свойств
            $arConsumerValue = [];
            foreach ($arConsumerProps as $arConsumerProp) {
                // Копируем значения свойств из сессии в массив
                $arConsumerValue[$arConsumerProp] = $_SESSION['consumer'][$arConsumerProp];
            }

            // Фильтры брендов из сессии
            $arFilterBrandsBitrix = $_SESSION['consumer']['filterBrandsBitrix'];

            /** @var \Bitrix\Sale\Order $order */
            $order = $event->getParameter("ENTITY");  // Получаем заказ из события

            // Если заказ уже существует (идентификатор заказа задан), выходим из функции
            if ($order->getId()) {
                return;
            }

            // Получаем коллекцию свойств заказа
            $propertyCollection = $order->getPropertyCollection();
            $arPropertyCollection = $propertyCollection->getArray();  // Получаем все свойства заказа

            // Перебираем свойства и сохраняем необходимые данные
            foreach ($arPropertyCollection['properties'] as $props) {
                // Если свойство с кодом 'ACUVUE_CONSUMER_JSON', сохраняем данные о потребителе
                if ($props['CODE'] == 'ACUVUE_CONSUMER_JSON') {
                    $consumer_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                }

                // Если свойство с кодом 'ACUVUE_FILTER', сохраняем фильтр брендов
                if ($props['CODE'] == 'ACUVUE_FILTER') {
                    $filter_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                }
            }
            if ($consumer_property) {
                $consumer_property->setValue(
                    json_encode($arConsumerValue, JSON_UNESCAPED_UNICODE)
                );  // Преобразуем массив в JSON
            }
            if ($filter_property) {
                $filter_property->setValue(json_encode($arFilterBrandsBitrix));  // Преобразуем массив фильтров в JSON
            }
            // Добавляем результат в событие
            $event->addResult(
                new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::SUCCESS, $order  // Указываем, что операция успешна, и возвращаем заказ
                )
            );
        }
    }

    static function controlProps($mode = 1)
    {
        // 1 - добавление/обновление, 2 - удаление
        if (!CModule::IncludeModule("sale"))  // Проверяем, подключен ли модуль "sale"
        {
            return false;
        }

        // Определяем свойства для обработки
        $arProps = [
            [
                'CODE' => "ACUVUE_CONSUMER_JSON",
                'NAME' => 'MyAcuvue Consumer',
                'DESCR' => 'Json с данными от Acuvue по пользователю',  // Json с данными пользователя из Acuvue
            ],
            [
                'CODE' => "ACUVUE_FILTER",
                'NAME' => 'MyAcuvue Filter',
                'DESCR' => 'Json Bitrix понятным фильтром',  // Json с фильтром для Bitrix
            ],
            [
                'CODE' => "ACUVUE_ORDER",
                'NAME' => 'MyAcuvue Order',
                'DESCR' => 'Json Заказа из MyAcuvue',  // Json с заказом из MyAcuvue
            ],
        ];

        $return = true;  // Изначально предполагаем, что операция успешна
        // Проходим по каждому свойству и обрабатываем его
        foreach ($arProps as $prop) {
            $subReturn = self::handleProp($prop, $mode);  // Обрабатываем каждое свойство
            if (!$subReturn)  // Если обработка не удалась для какого-либо свойства, устанавливаем результат в false
            {
                $return = $subReturn;
            }
        }
        return $return;
    }

    protected static function handleProp($arProp, $mode)
    {
        // Получаем существующие свойства с заданным кодом
        $tmpGet = CSaleOrderProps::GetList(["SORT" => "ASC"], ["CODE" => $arProp['CODE']]);
        $existedProps = [];
        while ($tmpElement = $tmpGet->Fetch())  // Извлекаем все существующие свойства
        {
            $existedProps[$tmpElement['PERSON_TYPE_ID']] = $tmpElement['ID'];
        }

        // Если режим 1 (добавление/обновление)
        if ($mode == '1') {
            $return = true;

            // Получаем список активных сайтов
            $tmpGet = CSite::GetList($by = "sort", $order = "desc", ['ACTIVE' => 'Y']);
            $arLids = [];
            while ($tmpElement = $tmpGet->Fetch()) {
                $arLids[] = $tmpElement['LID'];  // Сохраняем ID активных сайтов
            }

            // Получаем список активных типов плательщиков для активных сайтов
            $tmpGet = CSalePersonType::GetList(["SORT" => "ASC"], []);
            $allPayers = [];
            while ($tmpElement = $tmpGet->Fetch()) {
                if (
                    $tmpElement['ACTIVE'] == 'Y' &&  // Проверяем, что тип плательщика активен
                    in_array($tmpElement['LID'], $arLids)  // Проверяем, что тип плательщика относится к активному сайту
                ) {
                    $allPayers[] = $tmpElement['ID'];
                }  // Добавляем ID активных типов плательщиков в список
            }

            // Проходим по всем типам плательщиков и добавляем свойства заказа для каждого
            foreach ($allPayers as $payer) {
                $tmpGet = CSaleOrderPropsGroup::GetList(
                    ["SORT" => "ASC"],
                    ["NAME" => "ACUVUE"],
                    false,
                    ['nTopCount' => '1']
                );
                $tmpVal = $tmpGet->Fetch();
                if (!$tmpVal) {
                    // Если группа свойств не существует, создаем ее
                    $tmpVal = CSaleOrderPropsGroup::Add([
                        "PERSON_TYPE_ID" => $payer,
                        "NAME" => "ACUVUE",
                        "SORT" => 500,
                    ]);
                }

                // Определяем поля для нового свойства заказа
                $arFields = [
                    "PERSON_TYPE_ID" => $payer,
                    "NAME" => $arProp['NAME'],
                    "TYPE" => "TEXTAREA",  // Тип свойства - TEXTAREA
                    "REQUIED" => "N",
                    "DEFAULT_VALUE" => "",
                    "SORT" => 100,
                    "CODE" => $arProp['CODE'],
                    "USER_PROPS" => "N",
                    "IS_LOCATION" => "N",
                    "IS_LOCATION4TAX" => "N",
                    "PROPS_GROUP_ID" => $tmpVal['ID'],
                    "SIZE1" => 10,
                    "SIZE2" => 1,
                    "DESCRIPTION" => $arProp['DESCR'],
                    "IS_EMAIL" => "N",
                    "IS_PROFILE_NAME" => "N",
                    "IS_PAYER" => "N",
                    "IS_FILTERED" => "N",
                    "IS_ZIP" => "N",
                    "UTIL" => "Y",
                    "MULTILINE" => "Y",
                ];

                // Добавляем свойство, если оно еще не существует
                if (!array_key_exists($payer, $existedProps)) {
                    if (!CSaleOrderProps::Add(
                        $arFields
                    ))  // Если добавление не удалось, устанавливаем результат в false
                    {
                        $return = false;
                    }
                }
            }
            return $return;
        }

        // Если режим 2 (удаление)
        if ($mode == '2') {
            foreach ($existedProps as $existedPropId) {
                if (!CSaleOrderProps::Delete($existedPropId))  // Удаляем существующие свойства
                {
                    echo "Ошибка при удалении свойства CNTDTARIF с id" . $existedPropId . "<br>";
                }
            }  // Выводим сообщение об ошибке, если удаление не удалось
        }
    }

}
