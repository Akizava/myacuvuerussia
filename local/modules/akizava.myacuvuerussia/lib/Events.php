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
        $backtrace = debug_backtrace();
        $workMode = false;
        $check = ($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
        $workType = false;
        if (
            strpos($check, "/bitrix/admin/sale_order_detail.php") !== false ||
            strpos($check, "/bitrix/admin/sale_order_view.php") !== false
        ) {
            $workMode = 'order';
            $workType = 'standard';
        } elseif (strpos($_SERVER['PHP_SELF'], "/bitrix/admin/sale_order_shipment_edit.php") !== false && self::canShipment()) {
            $workMode = 'shipment';
            $workType = 'standard';
        }
        if ($GLOBALS[$backtrace[0]['function']]) {
            if ($workMode && $workType) {
                $consumer = [];
                $filter = [];
                $orderAcuvue = [];
                $order = \Bitrix\Sale\Order::load($_REQUEST['ID']);
                $orderDate = $order->getDateInsert();
                $propertyCollection = $order->getPropertyCollection();
                $arPropertyCollection = $propertyCollection->getArray();
                foreach ($arPropertyCollection['properties'] as $props) {
                    if ($props['CODE'] == 'ACUVUE_CONSUMER_JSON') {
                        $consumer_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $consumer = json_decode($consumer_property->getValue(), true);
                    }
                    if ($props['CODE'] == 'ACUVUE_FILTER') {
                        $filter_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $filter = json_decode($filter_property->getValue(), true);
                    }
                    if ($props['CODE'] == 'ACUVUE_ORDER') {
                        $order_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                        $orderAcuvue = json_decode($order_property->getValue(), true);
                    }
                }
                $arBasketSendAcuvue = [];
                if ($consumer && $filter) {
                    $basket = $order->getBasket();
                    foreach ($basket as $key => $basketItem) {
                        $addItem = false;
                        $title = $basketItem->getField('NAME');
                        $id = $basketItem->getId();
                        foreach ($filter['%NAME'] as $name) {
                            if (stripos($title, $name) !== false) {
                                $addItem = true;
                            }
                        }

                        foreach ($filter['!%NAME'] as $name) {
                            if (stripos($title, $name) !== false) {
                                $addItem = false;
                            }
                        }
                        if ($addItem) {
                            $arBasketSendAcuvue[] = [
                                "ID" => $id,
                                "NAME" => $title,
                            ];
                        }
                    }
                    if ($arBasketSendAcuvue) {
                        CJSCore::Init(["jquery"]);
                        ?>
                        <style>
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
                            let MyAcuvue_existedInfo = {
                                load: function () {
                                    if ($('.adm-detail-toolbar').find('.adm-detail-toolbar-right').length) {
                                        $('.adm-detail-toolbar').find('.adm-detail-toolbar-right').prepend("<a href='javascript:void(0)' onclick='MyAcuvue_existedInfo.showWindow()' class='adm-btn' id='MyAcuvue_btn'>MyAcuvue</a>");
                                    }
                                },
                                wnd: false,
                                showWindow: function () {
                                    if (!MyAcuvue_existedInfo.wnd) {
                                        var html = $('#MyAcuvue_wndOrder').html();
                                        $('#MyAcuvue_wndOrder').html('');
                                        MyAcuvue_existedInfo.wnd = new BX.CDialog({
                                            title: "MyAcuvue",
                                            content: html,
                                            icon: 'head-block',
                                            resizable: true,
                                            draggable: true,
                                            height: '500',
                                            width: '550',
                                            buttons: []
                                        });
                                    }
                                    MyAcuvue_existedInfo.wnd.Show();
                                },
                            };
                            document.addEventListener('DOMContentLoaded', function () {
                                MyAcuvue_existedInfo.load();
                                getElementByText("ACUVUE").parentElement.style.display = "none";
                                if (window.location.hash === '#MyAcuvueOrder') {
                                    MyAcuvue_existedInfo.showWindow();
                                }
                            });

                            function getElementByText(text) {
                                const xpath = `//node()[normalize-space(text())='${text.trim()}']`;
                                return document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
                            }

                            function sendMyAcuvueOrder(event) {
                                event.preventDefault();
                                let target = event.target,
                                    formData = new FormData(target);
                                $.ajax({
                                    url: target.action,
                                    type: "POST",
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function (response) {
                                        let data = JSON.parse(response);
                                        target.insertAdjacentHTML('beforebegin',
                                            `<h3>${data.message}</h3><br>
                                             <a href="#MyAcuvueOrder" onclick="reloadWithMyAcuvueOrder()">Обновить страницу</a>`);
                                        target.remove();
                                    }
                                });
                            }

                            function reloadWithMyAcuvueOrder() {
                                if (window.location.hash !== '#MyAcuvueOrder') {
                                    window.location.href += "#MyAcuvueOrder";
                                }
                                location.reload();
                            }
                        </script>
                        <div style='display:none' id='MyAcuvue_wndOrder'>
                            <? if ($orderAcuvue['orderStatus'] == "FULFILLED"): ?>
                                <table>
                                    <? foreach ($orderAcuvue as $key => $value): ?>
                                        <tr>
                                            <td><?= $key ?></td>
                                            <td>
                                                <pre><? print_r($value) ?></pre>
                                            </td>
                                        </tr>
                                    <? endforeach; ?>
                                </table>
                            <? else: ?>
                                <form id="MyAcuvueOrder" action="/bitrix/admin/akizava.myacuvuerussia.order.php"
                                      onsubmit="sendMyAcuvueOrder(event);">
                                    <a href="#MyAcuvue_wndOrder" class="MyAcuvueAccordion"
                                       onclick="$(this).toggleClass('active');">
                                        <p>Памятка для данных materialCodes/lotNumbers</p>
                                        <img src="/local/modules/akizava.myacuvuerussia/assets/img/info.png">
                                    </a>
                                    <? if (!$orderAcuvue): ?>
                                        <h3>MaterialCodes для заказа №<?= $_REQUEST['ID'] ?></h3>
                                        <h3>Заполнять после получения заказа</h3>
                                        <input type="hidden" name="actionType" value="submitOrder">
                                        <input type="hidden" name="clientOrderId" value="<?= $_REQUEST['ID'] ?>">
                                        <input type="hidden" name="consumerToken"
                                               value="<?= $consumer['consumerToken'] ?>">
                                        <input type="hidden" name="mobile"
                                               value="<?= $consumer['consumer']['mobile'] ?>">
                                        <input type="hidden" name="orderDate"
                                               value="<?= $orderDate->format("Y-m-d") ?>">
                                        <input type="hidden" name="voucher" value="">
                                        <?
                                        foreach ($arBasketSendAcuvue as $item) {
                                            ?>
                                            <div class="col-md-12 MyAcuvueInput">
                                                <label for="materialCodes<?= $item['ID'] ?>">
                                                    <?= $item['NAME']; ?>
                                                </label>
                                                <input type="text" id="materialCodes<?= $item['ID'] ?>"
                                                       name="materialCodes[<?= $item['ID'] ?>]">
                                            </div>
                                            <?
                                        }
                                        ?>
                                        <div class="col-md-12 MyAcuvueSubmit">
                                            <input type="submit" value="Отправить materialCodes">
                                        </div>
                                    <? else: ?>
                                        <h3>lotNumbers для заказа №<?= $_REQUEST['ID'] ?> / Acuvue -
                                            №<?= $orderAcuvue['orderNumber'] ?></h3>
                                        <h3>Заполнять перед отдачей клиенту</h3>
                                        <input type="hidden" name="actionType" value="fulfillOrder">
                                        <input type="hidden" name="clientOrderId" value="<?= $_REQUEST['ID'] ?>">
                                        <input type="hidden" name="orderNumber"
                                               value="<?= $orderAcuvue['orderNumber'] ?>">
                                        <?
                                        foreach ($arBasketSendAcuvue as $item) {
                                            ?>
                                            <div class="col-md-12 MyAcuvueInput">
                                                <label for="lotNumbers<?= $item['ID'] ?>">
                                                    <?= $item['NAME']; ?>
                                                </label>
                                                <input type="text" id="lotNumbers<?= $item['ID'] ?>"
                                                       name="lotNumbers[<?= $item['ID'] ?>]">
                                            </div>
                                            <?
                                        }
                                        ?>
                                        <div class="col-md-12 MyAcuvueSubmit">
                                            <input type="submit" value="Отправить lotNumbers">
                                        </div>
                                    <? endif; ?>
                                    <a href="#MyAcuvue_wndOrder" class="MyAcuvueAccordion"
                                       onclick="$(this).toggleClass('active');">
                                        <p>Памятка для данных materialCodes/lotNumbers</p>
                                        <img src="/local/modules/akizava.myacuvuerussia/assets/img/info.png">
                                    </a>
                                </form>

                            <? endif; ?>
                        </div>
                        <?
                    }
                }
            } else {
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
        if (!self::controlProps())
            return;
        global $USER;
        $userId = $USER->GetID();
        if ($_SESSION['consumer']) {
            $arConsumerProps = [
                'consumer',
                'points',
                'consumerToken',
            ];
            $arConsumerValue = [];
            foreach ($arConsumerProps as $arConsumerProp) {
                $arConsumerValue[$arConsumerProp] = $_SESSION['consumer'][$arConsumerProp];
            }
            $arFilterBrandsBitrix = $_SESSION['consumer']['filterBrandsBitrix'];
            /** @var \Bitrix\Sale\Order $order * */
            $order = $event->getParameter("ENTITY");
            if ($order->getId())
                return;
            $propertyCollection = $order->getPropertyCollection();
            $arPropertyCollection = $propertyCollection->getArray();
            foreach ($arPropertyCollection['properties'] as $props) {
                if ($props['CODE'] == 'ACUVUE_CONSUMER_JSON') {
                    $consumer_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                    $consumer_property->setValue(json_encode($arConsumerValue, JSON_UNESCAPED_UNICODE));
                }
                if ($props['CODE'] == 'ACUVUE_FILTER') {
                    $filter_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                    $filter_property->setValue(json_encode($arFilterBrandsBitrix));
                }
            }
            $event->addResult(
                new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::SUCCESS, $order
                )
            );
        }
    }

    static function controlProps($mode = 1)
    {
        //1-add/update, 2-delete
        if (!CModule::IncludeModule("sale"))
            return false;
        $arProps = [
            [
                'CODE' => "ACUVUE_CONSUMER_JSON",
                'NAME' => 'MyAcuvue Consumer',
                'DESCR' => 'Json с данными от Acuvue по пользователю',
            ],
            [
                'CODE' => "ACUVUE_FILTER",
                'NAME' => 'MyAcuvue Filter',
                'DESCR' => 'Json Bitrix понятным фильтром',
            ],
            [
                'CODE' => "ACUVUE_ORDER",
                'NAME' => 'MyAcuvue Order',
                'DESCR' => 'Json Заказа из MyAcuvue',
            ],
        ];

        $return = true;
        foreach ($arProps as $prop) {
            $subReturn = self::handleProp($prop, $mode);
            if (!$subReturn)
                $return = $subReturn;
        }
        return $return;
    }

    protected static function handleProp($arProp, $mode)
    {
        $tmpGet = CSaleOrderProps::GetList(["SORT" => "ASC"], ["CODE" => $arProp['CODE']]);
        $existedProps = [];
        while ($tmpElement = $tmpGet->Fetch())
            $existedProps[$tmpElement['PERSON_TYPE_ID']] = $tmpElement['ID'];
        if ($mode == '1') {
            $return = true;

            $tmpGet = CSite::GetList($by = "sort", $order = "desc", ['ACTIVE' => 'Y']);
            $arLids = [];
            while ($tmpElement = $tmpGet->Fetch()) {
                $arLids[] = $tmpElement['LID'];
            }

            $tmpGet = CSalePersonType::GetList(["SORT" => "ASC"], []);
            $allPayers = [];
            while ($tmpElement = $tmpGet->Fetch()) {
                if (
                    $tmpElement['ACTIVE'] == 'Y' &&
                    in_array($tmpElement['LID'], $arLids)
                )
                    $allPayers[] = $tmpElement['ID'];
            }

            foreach ($allPayers as $payer) {
                $tmpGet = CSaleOrderPropsGroup::GetList(["SORT" => "ASC"], ["NAME" => "ACUVUE"], false, ['nTopCount' => '1']);
                $tmpVal = $tmpGet->Fetch();
                if (!$tmpVal) {
                    $tmpVal = CSaleOrderPropsGroup::Add([
                        "PERSON_TYPE_ID" => $payer,
                        "NAME" => "ACUVUE",
                        "SORT" => 500,
                    ]);
                }
                $arFields = [
                    "PERSON_TYPE_ID" => $payer,
                    "NAME" => $arProp['NAME'],
                    "TYPE" => "TEXTAREA",
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
                if (!array_key_exists($payer, $existedProps))
                    if (!CSaleOrderProps::Add($arFields))
                        $return = false;
            }
            return $return;
        }
        if ($mode == '2') {
            foreach ($existedProps as $existedPropId)
                if (!CSaleOrderProps::Delete($existedPropId))
                    echo "Error delete CNTDTARIF-prop id" . $existedPropId . "<br>";
        }
    }
}
