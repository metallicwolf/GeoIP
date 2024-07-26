<?

/**
 * @var array $arParams
 * @var CBitrixComponent $component
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div id="geo">
    <div class="wrapper">
        <div class="container">
            <div class="block">
                <div class="row">
                    <form id="geoform" method="post">
                        <div>
                            <input class="ip" type="text" name="ip" id="ip" value="104.21.15.212" placeholder="введите IP адрес, например 172.217.22.14">
                        </div>
                        <div>
                            <button id="request" type="submit">Проверить</button>
                        </div>
                    </form>
                </div>
                <div id="alert"></div>
                <div id="ipdata" class="row">
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// прокидываем параметры в js, т.к. при ajax запросе компонент заново не инициализируется и параметры не получить
?>
<script>
    var geo = new GeoApp(<?=\Bitrix\Main\Web\Json::encode($arParams, false, true)?>);
</script>