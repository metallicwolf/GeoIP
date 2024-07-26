<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 */

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;

Loc::loadMessages(__FILE__);

class geoip extends \CBitrixComponent implements Controllerable
{
    protected string $apiServer = 'https://api.sypexgeo.net/json/';
    protected string $ip;
    protected array $params;
    protected DataManager|string|null $hlEntity;

    public function __construct($component = null)
    {
        parent::__construct($component);
    }

    public function onPrepareComponentParams($arParams): array
    {
        if (!isset($arParams['CACHE_TIME'])) {
            $arParams['CACHE_TIME'] = 3600;
        } else {
            $arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
        }
        $arParams['HL_IBLOCK_ID'] = intval($arParams['HL_IBLOCK_ID']);
        $this->params = $arParams;  # working with own parameters
        return $arParams;
    }

    /**
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'searchIP' => [
                '-prefilters' => [
                    \Bitrix\Main\Engine\ActionFilter\Authentication::class, # for non-authorized users
                ]
            ],
        ];
    }

    /**
     * Ajax request for IP data
     * @param $ip
     * @param $params
     * @return array success (bool), data (array), error (string)
     */

    public function searchIPAction($ip, $params): array
    {
        $this->ip = $ip;
        if (empty($this->ip) || !filter_var($this->ip, FILTER_VALIDATE_IP)) {
            return [
                'success' => false,
                'error' => GetMessage('ERROR_INCORRECT_IP'),
                'data' => null,
            ];
        }

        // return component parameters from js
        $this->params = $params;
        if (empty($this->params['HL_IBLOCK_ID'])) {
            return [
                'success' => false,
                'error' => GetMessage('ERROR_REQUEST'),
                'data' => null,
            ];
        }
        // check for the robot/bot
        $isBot = empty($_SERVER['HTTP_USER_AGENT']) || preg_match(
                "~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl|request|Guzzle|Java)~i",
                $_SERVER['HTTP_USER_AGENT']
            );

        if (!$isBot) {
            $hlResponse = null;
            try {
                $this->initHLEntity();  # initialization HL
                $hlResponse = $this->getFromHL();   # find in HL
            } catch (Exception|LoaderException|ObjectPropertyException|ArgumentException|SystemException $e) {
                $this->sendAlertEmail($e->getMessage());  # send admin email
            }
            if (!empty($hlResponse)) {  # search for ip in Highloadblock
                return [
                    'success' => true,
                    'data' => $hlResponse,
                    'error' => null
                ];
            } else {
                $apiResponse = $this->apiRequest(); # request to API
                if ($apiResponse['success']) {
                    try {
                        // the initialization of the entity was earlier
                        $this->addToHL($apiResponse['data']);
                    } catch (Exception|ObjectPropertyException|ArgumentException|SystemException $e) {
                        $this->sendAlertEmail($e->getMessage());  # send admin email
                    }
                    return [
                        'success' => true,
                        'data' => $apiResponse['data'],
                        'error' => null
                    ];
                }
                return [
                    'success' => false,
                    'data' => null,
                    'error' => $apiResponse['error']
                ];
            }
        }
        return [
            'success' => false,
            'data' => null,
            'error' => GetMessage('ERROR_ACCESS_DENIED')
        ];
    }

    /**
     * Get IP address geo data from Highloadblock table
     * @return array|null last sorted IP address data
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getFromHL(): array|null
    {
        $rsData = $this->hlEntity::getList(
            [
                'select' => [
                    'ID',
                    'UF_IP',
                    'UF_COUNTRY',
                    'UF_REGION',
                    'UF_CITY',
                    'UF_LAT',
                    'UF_LON',
                    'UF_DATE_CREATED'
                ],
                'order' => ['UF_DATE_CREATED' => 'ASC'],
                'filter' => ['UF_IP' => $this->ip],
            ]
        );
        $result = [];
        while ($HLData = $rsData->Fetch()) {
            $result[$HLData['UF_IP']] = $HLData;
        }

        if ($key = array_key_first($result)) {
            return [
                'ip' => $result[$key]['UF_IP'],
                'country' => ['name_ru' => $result[$key]['UF_COUNTRY']],
                'region' => ['name_ru' => $result[$key]['UF_REGION']],
                'city' => [
                    'name_ru' => $result[$key]['UF_CITY'],
                    'lat' => $result[$key]['UF_LAT'],
                    'lon' => $result[$key]['UF_LON'],
                ],
            ];
        } else {
            return null;
        }
    }

    /**
     * Add IP address data to Highloadblock
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws Exception
     */
    protected function addToHL($data): array
    {
        $result = $this->hlEntity::add(
            [
                'UF_IP' => $data['ip'],
                'UF_COUNTRY' => !empty($data['country']['name_ru']) ? $data['country']['name_ru'] : 'null',
                'UF_REGION' => !empty($data['region']['name_ru']) ? $data['region']['name_ru'] : 'null',
                'UF_CITY' => !empty($data['city']['name_ru']) ? $data['city']['name_ru'] : 'null',
                'UF_LAT' => !empty($data['city']['lat']) ? $data['city']['lat'] : 'null',
                'UF_LON' => !empty($data['city']['lon']) ? $data['city']['lon'] : 'null',
            ]
        );
        return [
            'success' => $result->isSuccess(),
            'error' => implode(', ', $result->getErrorMessages())
        ];
    }

    protected function apiRequest(): array
    {
        $options = [
            "waitResponse" => true,
            "socketTimeout" => 30,
            "streamTimeout" => 60,
            "version" => HttpClient::HTTP_1_0,
            "compress" => false,
        ];
        $httpClient = new HttpClient($options);

        if ($httpClient->query('HTTP_GET', $this->apiServer . $this->ip)) {
            // check for reponse format
            if ($httpClient->getContentType() == 'application/json'
                && $responseData = json_decode($httpClient->getResult(), true)) {
                // check required data
                if (empty($responseData['country'])) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => GetMessage('INFO_RESPONSE_EMPTY')
                    ];
                }
                // success response with data
                return [
                    'success' => true,
                    'data' => $responseData,
                    'error' => null
                ];
            }
        }
        // base error
        return [
            'success' => false,
            'data' => null,
            'error' => GetMessage('ERROR_REQUEST')
        ];
    }

    /**
     * Initializing Highloadblock entity
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws LoaderException
     * @throws Exception
     */
    private function initHLEntity(): void
    {
        if (!Loader::includeModule("highloadblock")) {
            throw new Exception(Loc::getMessage('ERROR_MODULE_LOAD', ["#NAME#" => "highloadblock"]));
        }
        if (empty($this->params['HL_IBLOCK_ID'])) {
            throw new Exception(GetMessage('ERROR_PARAM_HL_ID'));
        }
        $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById($this->params['HL_IBLOCK_ID'])->fetch();
        $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $this->hlEntity = $entity->getDataClass();
        if (empty($this->hlEntity)) {
            throw new Exception(GetMessage('ERROR_ENTITY_HL'));
        }
    }

    /**
     * Check component parameters
     * @throws Exception
     */
    protected function checkParams(): void
    {
        if (empty($this->arParams['HL_IBLOCK_ID'])) {
            throw new Exception(GetMessage('ERROR_PARAM_HL_ID'));
        }
        if ($this->params['EMAIL_ERRORS'] == 'Y' && empty(trim($this->params['EMAIL']))) {
            throw new Exception(GetMessage('ERROR_PARAM_EMAIL'));
        }
    }

    private function sendAlertEmail($exception): void
    {
        if (empty(trim($this->params['EMAIL'])) || empty(trim($exception))) {
            return;
        }

        $eventName = CEventMessage::GetByID($this->params['MESSAGE_ID'])->Fetch();
        if (empty($eventName['EVENT_NAME'])){
            return;
        }
        $arFields = [
            "EVENT_NAME" => $eventName['EVENT_NAME'],
            'MESSAGE_ID' => $this->params['MESSAGE_ID'],
            "LID" => $this->params['LID'],
            "C_FIELDS" => [
                "EMAIL" => $this->params['EMAIL'],
                "AUDIT_TYPE_ID" => GetMessage('ERROR_EMAIL_AUDIT_TYPE'),
                "ITEM_ID" => $this->getName(),
                "ADDITIONAL_TEXT" => $exception,
            ]
        ];
        Event::send($arFields);
        unset($eventName, $arFields);
    }

    public function executeComponent(): void
    {
        try {
            $this->initHLEntity();
            $this->checkParams();
        } catch (Exception $e) {
            global $USER;
            if ($USER->IsAdmin()) {
                ShowError($e->getMessage());
            }
            $this->sendAlertEmail($e->getMessage());
        }
        $this->includeComponentTemplate();
    }
}