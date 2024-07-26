<?

define('BX_PULL_SKIP_INIT', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("GeoIP");
?><?
$APPLICATION->IncludeComponent(
    "geoip:geoip",
    "",
    array(
        "CACHE_TIME" => "3600",
        "CACHE_TYPE" => "A",
        "EMAIL" => "test@test.ru",
        "EMAIL_ERRORS" => "Y",
        "HL_IBLOCK_ID" => "7",
        "LID" => "s1",
        "MESSAGE_ID" => "2"
    )
); ?><?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>