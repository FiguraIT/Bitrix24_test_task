<?php

namespace Line\Bitrix;

/**
 * Битрикс24 REST API
 *
 * @author  1С-Битрикс https://github.com/bitrix-tools
 * @version 1.36
 *
 * @author  ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @version 2.2.1
 */
class CRest
{
    const BATCH_COUNT    = 50;     // Кол-во элементов при Batch запросе (50 максимум)
    const TYPE_TRANSPORT = 'json'; // JSON или XML

    private static $clientId;     // ID приложения
    private static $clientSecret; // Ключ приложения
    private static $ignoreSSL;    // Выключить валидациюю SSL через curl
    private static $bitrixURL;    // Ссылка на портал Битрикс24
    private static $blockLog;     // Выключить стандартные логи
    private static $logDir;       // Директория файлов лога
    private static $webhook;      // Webhook URL
    private static $root;         // Корневая директория приложения

    /**
     * Получаем данные из settings.php
     */
    public static function getSettingsConstants()
    {
        static::$root = dirname(__DIR__, 3);

        // Получаем константы настроек
        require_once static::$root.'/settings.php';

        static::$clientId     = (defined('C_REST_CLIENT_ID')) ? C_REST_CLIENT_ID : false;
        static::$clientSecret = (defined('C_REST_CLIENT_SECRET')) ? C_REST_CLIENT_SECRET : false;
        static::$bitrixURL    = (defined('SETTINGS_HOST')) ? SETTINGS_HOST : false;
        static::$ignoreSSL    = (defined('C_REST_IGNORE_SSL')) ? C_REST_IGNORE_SSL : true;
        static::$webhook      = (defined('C_REST_WEB_HOOK_URL') && !empty(C_REST_WEB_HOOK_URL))
            ? C_REST_WEB_HOOK_URL
            : false;

        // Настройки логирования
        static::$blockLog = (defined('C_REST_BLOCK_LOG')) ? C_REST_BLOCK_LOG : false;
        static::$logDir   = (defined('C_REST_LOGS_DIR')) ? C_REST_LOGS_DIR : static::$root.'/logs/';
    }

    /**
     * Вызов при установке, только для REST,
     * для вебхука не используется
     *
     * @return bool[]
     */
    public static function installApp()
    {
        $result = [
            'rest_only' => true,
            'install'   => false,
        ];
        if ($_REQUEST['event'] == 'ONAPPINSTALL' && !empty($_REQUEST['auth'])) {
            $result['install'] = static::setAppSettings($_REQUEST['auth'], true);
        } else if ($_REQUEST['PLACEMENT'] == 'DEFAULT') {
            $result['rest_only'] = false;
            $result['install']   = static::setAppSettings(
                [
                    'access_token'      => htmlspecialchars($_REQUEST['AUTH_ID']),
                    'expires_in'        => htmlspecialchars($_REQUEST['AUTH_EXPIRES']),
                    'application_token' => htmlspecialchars($_REQUEST['APP_SID']),
                    'refresh_token'     => htmlspecialchars($_REQUEST['REFRESH_ID']),
                    'domain'            => htmlspecialchars($_REQUEST['DOMAIN']),
                    'client_endpoint'   => 'https://'.htmlspecialchars($_REQUEST['DOMAIN']).'/rest/',
                ],
                true
            );
        }

        static::setLog(
            [
                'request' => $_REQUEST,
                'result'  => $result,
            ],
            'installApp'
        );
        return $result;
    }

    /**
     * Битрикс24 REST API запрос
     *
     * @param array $arParams
     *          $arParams = [
     *          'method' => '',
     *          'params' => []
     *          ];
     *
     * @return array|bool|mixed|string|string[]
     */
    protected static function callCurl(array $arParams)
    {
        if (!function_exists('curl_init')) {
            return [
                'error'             => 'error_php_lib_curl',
                'error_information' => 'need install curl lib',
            ];
        }

        $arSettings = static::getAppSettings();

        if ($arSettings !== false) {
            if (isset($arParams['this_auth']) && $arParams['this_auth'] == 'Y') {
                $url = 'https://oauth.bitrix.info/oauth/token/';
            } else {
                $url = $arSettings["client_endpoint"].$arParams['method'].'.'.static::TYPE_TRANSPORT;

                // Проверка, для авторизации пользователя как текущего
                if (!empty($arParams['params']['auth'])) {

                } else if (empty($arSettings['is_web_hook']) || $arSettings['is_web_hook'] != 'Y') {
                    $arParams['params']['auth'] = $arSettings['access_token'];
                }

            }
            $sPostFields = http_build_query($arParams['params']);

            try {
                $obCurl = curl_init();

                curl_setopt($obCurl, CURLOPT_URL, $url);
                curl_setopt($obCurl, CURLOPT_RETURNTRANSFER, true);

                if ($sPostFields) {
                    curl_setopt($obCurl, CURLOPT_POST, true);
                    curl_setopt($obCurl, CURLOPT_POSTFIELDS, $sPostFields);
                }

                curl_setopt(
                    $obCurl, CURLOPT_FOLLOWLOCATION, (isset($arParams['followlocation']))
                    ? $arParams['followlocation'] : 1
                );

                if (static::$ignoreSSL) {
                    curl_setopt($obCurl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($obCurl, CURLOPT_SSL_VERIFYHOST, false);
                }

                $out  = curl_exec($obCurl);
                $info = curl_getinfo($obCurl);
                if (curl_errno($obCurl)) {
                    $info['curl_error'] = curl_error($obCurl);
                }

                // Авторизация поддерживает только JSON
                if (isset($arParams['this_auth']) && $arParams['this_auth'] != 'Y' && static::TYPE_TRANSPORT === 'xml') {
                    $result = $out;
                } else {
                    $result = json_decode($out, true);
                }
                curl_close($obCurl);

                if (!empty($result['error'])) {
                    if ($result['error'] === 'expired_token' && empty($arParams['this_auth'])) {
                        $result = static::GetNewAuth($arParams);
                    } else {
                        $arErrorInform = [
                            'expired_token'          => 'expired token, cant get new auth? Check access oauth server.',
                            'invalid_token'          => 'invalid token, need reinstall application',
                            'invalid_grant'          => 'invalid grant, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
                            'invalid_client'         => 'invalid client, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
                            'QUERY_LIMIT_EXCEEDED'   => 'Too many requests, maximum 2 query by second',
                            'ERROR_METHOD_NOT_FOUND' => 'Method not found! You can see the permissions of the application: CRest::call(\'scope\')',
                            'NO_AUTH_FOUND'          => 'Some setup error b24, check in table "b_module_to_module" event "OnRestCheckAuth"',
                        ];
                        if (!empty($arErrorInform[$result['error']])) {
                            $result['error_information'] = $arErrorInform[$result['error']];
                        }
                    }
                }
                if (!empty($info['curl_error'])) {
                    $result['error']             = 'curl_error';
                    $result['error_information'] = $info['curl_error'];
                }

                static::setLog(
                    [
                        'url'    => $url,
                        'info'   => $info,
                        'params' => $arParams,
                        'result' => $result,
                    ],
                    'callCurl'
                );

                return $result;
            } catch (Exception $e) {
                return [
                    'error'             => 'exception',
                    'error_information' => $e->getMessage(),
                ];
            }
        }

        return [
            'error'             => 'no_install_app',
            'error_information' => 'error install app, pls install local application ',
        ];
    }

    /**
     * Генерируем запрос для callCurl()
     *
     * @param       $method string
     * @param array $params
     *
     * @return array|mixed|string[]
     */
    public static function call($method, $params = [])
    {
        $arPost = [
            'method' => $method,
            'params' => $params,
        ];

        return static::callCurl($arPost);
    }

    /**
     * Batch запрос к API (несколько запросов в одном)
     *
     * $arData = [
     *      'find_contact' => [
     *          'method' => 'crm.duplicate.findbycomm',
     *          'params' => [ "entity_type" => "CONTACT",  "type" => "EMAIL", "values" => array("info@bitrix24.com") ]
     *      ],
     *      'get_contact' => [
     *          'method' => 'crm.contact.get',
     *          'params' => [ "id" => '$result[find_contact][CONTACT][0]' ]
     *      ],
     *      'get_company' => [
     *          'method' => 'crm.company.get',
     *          'params' => [ "id" => '$result[get_contact][COMPANY_ID]', "select" => ["*"],]
     *      ]
     * ];
     *
     * @param array $arData
     * @param int   $halt 0 или 1 останавливает батч при ошибке
     *
     * @return array|mixed|string[]
     */
    public static function callBatch(array $arData, $halt = 0)
    {
        $arResult   = [];
        $arDataRest = [];
        $i          = 0;
        foreach ($arData as $key => $data) {
            if (!empty($data['method'])) {
                $i++;
                if (static::BATCH_COUNT > $i) {
                    $arDataRest['cmd'][$key] = $data['method'];
                    if (!empty($data['params'])) {
                        $arDataRest['cmd'][$key] .= '?'.http_build_query($data['params']);
                    }
                }
            }
        }
        if (!empty($arDataRest)) {
            $arDataRest['halt'] = $halt;
            $arResult           = static::call('batch', $arDataRest);
        }

        return $arResult;
    }

    /**
     * Получаем новую авторизацию и заново отправляем запрос
     *
     * @param array $arParams Повтор запроса, если вернулась ошибка авторизации
     *
     * @return array|bool|mixed|string|string[]
     */
    private static function GetNewAuth(array $arParams)
    {
        $result     = [];
        $arSettings = static::getAppSettings();
        if ($arSettings !== false) {
            $arParamsAuth = [
                'this_auth' => 'Y',
                'params'    =>
                    [
                        'client_id'     => $arSettings['C_REST_CLIENT_ID'],
                        'grant_type'    => 'refresh_token',
                        'client_secret' => $arSettings['C_REST_CLIENT_SECRET'],
                        'refresh_token' => $arSettings["refresh_token"],
                    ],
            ];

            $newData = static::callCurl($arParamsAuth);
            if (isset($newData['C_REST_CLIENT_ID'])) {
                unset($newData['C_REST_CLIENT_ID']);
            }
            if (isset($newData['C_REST_CLIENT_SECRET'])) {
                unset($newData['C_REST_CLIENT_SECRET']);
            }
            if (isset($newData['error'])) {
                unset($newData['error']);
            }
            if (static::setAppSettings($newData)) {
                $arParams['this_auth'] = 'N';
                $result                = static::callCurl($arParams);
            }
        }

        return $result;
    }

    /**
     * Проверяем и сохраняем настройки приложения
     *
     * @param array $arSettings Настройки приложения
     * @param bool  $isInstall  TRUE если производится установка приложения
     *
     * @return bool
     */
    private static function setAppSettings(array $arSettings, $isInstall = false)
    {
        $oldData = static::getAppSettings();
        if (!$isInstall && !empty($oldData) && is_array($oldData)) {
            $arSettings = array_merge($oldData, $arSettings);
        }
        return static::setSettingData($arSettings);
    }

    /**
     * Возвращаем настройки приложения
     *
     * @return array|bool
     */
    private static function getAppSettings()
    {
        static::getSettingsConstants();

        if (static::$webhook) {
            $arData     = [
                'client_endpoint' => static::$webhook,
                'is_web_hook'     => 'Y',
            ];
            $isCurrData = true;
        } else {
            $arData     = static::getSettingData();
            $isCurrData = false;
            if (
                !empty($arData['access_token']) &&
                !empty($arData['domain']) &&
                !empty($arData['refresh_token']) &&
                !empty($arData['application_token']) &&
                !empty($arData['client_endpoint'])
            ) {
                $isCurrData = true;
            }
        }
        return ($isCurrData) ? $arData : false;
    }

    /**
     * Получаем настройки приложения из settings.json
     *
     * @return array Настройки для getAppSettings()
     */
    protected static function getSettingData()
    {
        static::getSettingsConstants();

        $return = json_decode(file_get_contents(static::$root.'/settings.json'), true);
        if (static::$clientId) {
            $return['C_REST_CLIENT_ID'] = static::$clientId;
        }
        if (static::$clientSecret) {
            $return['C_REST_CLIENT_SECRET'] = static::$clientSecret;
        }

        return $return;
    }

    /**
     * Сохраняем настройки в settings.json
     *
     * @param array $arSettings Настройки приложения
     *
     * @return bool
     */
    protected static function setSettingData(array $arSettings)
    {
        static::getSettingsConstants();

        return (boolean)file_put_contents(static::$root.'/settings.json', json_encode($arSettings));
    }

    /**
     * Логирование
     *
     * @param array  $arData Массив логируемых данных
     * @param string $type   Дополнительные данные для названия файла лога
     *
     * @return bool|false|int
     */
    public static function setLog(array $arData, $type = '')
    {
        static::getSettingsConstants();

        $return = false;
        if (!static::$blockLog) {
            $path = static::$logDir;
            $path .= date("Y-m-d/H").'/';
            @mkdir($path, 0775, true);
            $path   .= time().'_'.$type.'_'.rand(1, 9999999).'log';
            $return = file_put_contents($path.'.json', json_encode($arData));
        }

        return $return;
    }

}
