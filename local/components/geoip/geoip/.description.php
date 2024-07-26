<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
    "NAME" => GetMessage('C_NAME'),
    "DESCRIPTION" => GetMessage('C_DESCRIPTION'),
    "PATH" => array(
        "ID" => "CUSTOM_UTILS",
    ),
    'SORT' => 10,
    "ICON" => "",
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'N',
);