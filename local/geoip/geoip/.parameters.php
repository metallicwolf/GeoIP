<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
global $USER;

// get email templates
$emailTemplates = [];
$db_mes = CEventMessage::GetList($by = "EVENT_TYPE", $order = "asc", ['ACTIVE' => 'Y']);
while ($mes = $db_mes->Fetch()){
    $emailTemplates[$mes['ID']] = '['.$mes['ID'].'] '.$mes['EVENT_TYPE'];
}
unset($db_mes, $mes);

// get sites
$sites = [];
$db_sites = CSite::GetList($by = "NAME", $order = "asc", array("ACTIVE" => "Y"));
while ($site = $db_sites->Fetch()){
    $sites[$site['LID']] = '['.$site['LID'].'] '.$site['NAME'];
}
unset($db_sites, $site);

// get highloadblocks
$hlIblocks = [];
try {
    if (!Loader::IncludeModule('highloadblock')) {
        throw new Exception("Highloadblock module not installed");
    }
    $hlblock = HL\HighloadBlockTable::getList(['filter' => []]);
    while ($hl = $hlblock->fetch()) {
        $hlIblocks[$hl['ID']] = $hl['NAME'];
    }
    unset($hlblock, $hl);

} catch (Exception|LoaderException|ObjectPropertyException|ArgumentException|SystemException $e) {
    if ($USER->IsAdmin()) {
        ShowError($e->getMessage());
    }
}
$arComponentParameters = array(
    "GROUPS" => [
        "SEND_EVENT" => array("NAME" => GetMessage("GROUP_TITLE_SEND_EVENT")),
    ],
    "PARAMETERS" => [
        'HL_IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('P_TITLE_HIGHLOADBLOCK'),
            'TYPE' => 'LIST',
            'VALUES' => $hlIblocks,
            'REFRESH' => 'Y',
            "DEFAULT" => '',
            "ADDITIONAL_VALUES" => "Y",
        ],
        "EMAIL_ERRORS" => [
            "PARENT" => "SEND_EVENT",
            "NAME" => GetMessage('P_TITLE_EMAIL_ERRORS'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "EMAIL" => [
            "PARENT" => "SEND_EVENT",
            "NAME" => GetMessage('P_TITLE_EMAIL'),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "",
        ],
        "MESSAGE_ID" => [
            "PARENT" => "SEND_EVENT",
            "NAME" => GetMessage('P_TITLE_MESSAGE_ID'),
            "TYPE" => "LIST",
            'VALUES' => $emailTemplates,
            "MULTIPLE" => "N",
            "DEFAULT" => "9",
        ],
        "LID" => [
            "PARENT" => "SEND_EVENT",
            "NAME" => GetMessage('P_TITLE_LID'),
            "TYPE" => "LIST",
            'VALUES' => $sites,
            "MULTIPLE" => "N",
            "DEFAULT" => "s1",
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600
        ],
    ]
);
unset($hlIblocks, $emailTemplates, $sites);