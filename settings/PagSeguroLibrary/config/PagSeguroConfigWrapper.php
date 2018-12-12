<?php
/**
 * 2007-2014 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2007-2014 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

class PagSeguroConfigWrapper
{

    /**
     * production or sandbox
     */
    const PAGSEGURO_ENV = "sandbox";
    /**
     *
     */
    const PAGSEGURO_EMAIL = "clcruz@compreingressos.com";
    /**
     *
     */
    const PAGSEGURO_TOKEN_PRODUCTION = "29EDAF1C11BF4D899F30656F431BEC43";
    /**
     *
     */
    const PAGSEGURO_TOKEN_SANDBOX = "D4B69F4B081742C69B34568DEAD1C950";
    /**
     *
     */
    const PAGSEGURO_APP_ID_PRODUCTION = "compreingressoscom";
    /**
     *
     */
    const PAGSEGURO_APP_ID_SANDBOX = "app6844218481";
    /**
     *
     */
    const PAGSEGURO_APP_KEY_PRODUCTION = "25366A0B4343963EE440AFAF38E9583D";
    /**
     *
     */
    const PAGSEGURO_APP_KEY_SANDBOX = "0B9B26D1949409CEE4F39F8053511E4D";
    /**
     * UTF-8, ISO-8859-1
     */
    const PAGSEGURO_CHARSET = "UTF-8";
    /**
     *
     */
    const PAGSEGURO_LOG_ACTIVE = false;
    /**
     * Informe o path completo (relativo ao path da lib) para o arquivo, ex.: ../PagSeguroLibrary/logs.txt
     */
    const PAGSEGURO_LOG_LOCATION = "../settings/PagSeguroLibrary/temp/logs.txt";

    /**
     * @return array
     */
    public static function getConfig()
    {
        $PagSeguroConfig = array();

        $PagSeguroConfig = array_merge_recursive(
            self::getEnvironment(),
            self::getCredentials(),
            self::getApplicationEncoding(),
            self::getLogConfig()
        );

        return $PagSeguroConfig;
    }

    /**
     * @return mixed
     */
    private static function getEnvironment()
    {
        // $PagSeguroConfig['environment'] = getenv('PAGSEGURO_ENV') ?: self::PAGSEGURO_ENV;

        $PagSeguroConfig['environment'] = $_ENV['IS_TEST'] ? "sandbox" : "production";

        return $PagSeguroConfig;
    }

    /**
     * @return mixed
     */
    private static function getCredentials()
    {
        $PagSeguroConfig['credentials'] = array();
        $PagSeguroConfig['credentials']['email'] = getenv('PAGSEGURO_EMAIL')
            ?: self::PAGSEGURO_EMAIL;
        $PagSeguroConfig['credentials']['token']['production'] = getenv('PAGSEGURO_TOKEN_PRODUCTION')
            ?: self::PAGSEGURO_TOKEN_PRODUCTION;
        $PagSeguroConfig['credentials']['token']['sandbox'] = getenv('PAGSEGURO_TOKEN_SANDBOX')
            ?: self::PAGSEGURO_TOKEN_SANDBOX;
        $PagSeguroConfig['credentials']['appId']['production'] = getenv('PAGSEGURO_APP_ID_PRODUCTION')
            ?: self::PAGSEGURO_APP_ID_PRODUCTION;
        $PagSeguroConfig['credentials']['appId']['sandbox'] = getenv('PAGSEGURO_APP_ID_SANDBOX')
            ?: self::PAGSEGURO_APP_ID_SANDBOX;
        $PagSeguroConfig['credentials']['appKey']['production'] = getenv('PAGSEGURO_APP_KEY_PRODUCTION')
            ?: self::PAGSEGURO_APP_KEY_PRODUCTION;
        $PagSeguroConfig['credentials']['appKey']['sandbox'] = getenv('PAGSEGURO_APP_KEY_SANDBOX')
            ?: self::PAGSEGURO_APP_KEY_SANDBOX;

        return $PagSeguroConfig;
    }

    /**
     * @return mixed
     */
    private static function getApplicationEncoding()
    {
        $PagSeguroConfig['application'] = array();
        $PagSeguroConfig['application']['charset'] = ( getenv('PAGSEGURO_CHARSET')
            && ( getenv('PAGSEGURO_CHARSET') == "UTF-8" || getenv('PAGSEGURO_CHARSET') == "ISO-8859-1") )
            ?: self::PAGSEGURO_CHARSET;

        return $PagSeguroConfig;
    }

    /**
     * @return mixed
     */
    private static function getLogConfig()
    {
        $PagSeguroConfig['log'] = array();
        $PagSeguroConfig['log']['active'] = ( getenv('PAGSEGURO_LOG_ACTIVE')
            && getenv('PAGSEGURO_LOG_ACTIVE') == 'true' ) ?: self::PAGSEGURO_LOG_ACTIVE;
        $PagSeguroConfig['log']['fileLocation'] = getenv('PAGSEGURO_LOG_LOCATION')
            ?: self::PAGSEGURO_LOG_LOCATION;

        return $PagSeguroConfig;
    }
}
