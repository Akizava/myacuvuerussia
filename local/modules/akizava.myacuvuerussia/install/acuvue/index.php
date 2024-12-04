<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "MyAcuvue");
$APPLICATION->SetPageProperty("title", "MyAcuvue");
$APPLICATION->SetTitle("MyAcuvue");
if ($_GET['logout_acuvue'] == 'Y') {
    unset($_SESSION['consumer']);
    LocalRedirect('/acuvue/');
}
?>
    <div class="container">
        <div class="row">
            <? if ($_SESSION['consumer']): ?>
                <div class="col-md-12">
                    <a href="?logout_acuvue=Y" id="btn-logout" class="fa fa-sign-out" title="Выйти"></a>
                </div>
            <? endif; ?>
            <div class="col-md-4">
                <? if ($_SESSION['consumer']): ?>
                    <p class="h3">Баланс</p>
                    <ul>
                        <li>
                            Баланс: <?= $_SESSION['consumer']['points']['balance'] ?>
                        </li>
                        <li>
                            Неподтвержденный баланс: <?= $_SESSION['consumer']['points']['eligibleBalance'] ?>
                        </li>
                        <li>
                            Очки лояльности: <?= $_SESSION['consumer']['points']['myAcuvuePoints'] ?>
                        </li>
                    </ul>
                <? endif; ?>
            </div>
            <div class="col-md-8">
                <? if (!$_SESSION['consumer']): ?>
                    <p>
                        Вы не авторизованы.<br>
                        <a data-target="acuvueAuth" href="#acuvueAuth">Авторизоваться</a>
                    </p>
                <? else: ?>
                    <p class="h3">Личные данные</p>
                    <ul>
                        <li>
                            Логин: <?= $_SESSION['consumer']['consumer']['username'] ?>
                        </li>
                        <li>
                            Номер: <?= $_SESSION['consumer']['consumer']['mobile'] ?>
                        </li>
                        <li>
                            Email: <?= $_SESSION['consumer']['consumer']['email'] ?>
                        </li>
                        <li>
                            Имя: <?= $_SESSION['consumer']['consumer']['firstName'] ?>
                        </li>
                        <li>
                            Фамилия: <?= $_SESSION['consumer']['consumer']['lastName'] ?>
                        </li>
                    </ul>
                <? endif; ?>
            </div>
            <? if ($_SESSION['consumer']): ?>
                <div class="col-md-12">
                    <p class="h1" style="text-align: center; margin-bottom: 5rem;">Подходящие товары</p>
                    <? $APPLICATION->IncludeComponent(
                        "akizava.myacuvuerussia:brands",
                        "",
                        [],
                        false,
                        ['HIDE_ICONS' => 'Y']
                    ); ?>
                </div>
            <? endif; ?>
        </div>
    </div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>