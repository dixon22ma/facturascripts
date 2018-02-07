<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Base;

use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of Installer
 *
 * @author carlos
 */
class Installer
{

    public function getAvailableLanguages(&$i18n)
    {
        $languages = [];
        $dir = FS_FOLDER . '/Core/Translation';
        foreach (scandir($dir, SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName !== '.' && $fileName !== '..' && !is_dir($fileName) && substr($fileName, -5) === '.json') {
                $key = substr($fileName, 0, -5);
                $languages[$key] = $i18n->trans('languages-' . substr($fileName, 0, -5));
            }
        }

        return $languages;
    }

    /**
     * Timezones list with GMT offset
     *
     * @return array
     *
     * @link http://stackoverflow.com/a/9328760
     */
    public function getTimezoneList()
    {
        $zonesArray = [];
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zonesArray[$key]['zone'] = $zone;
            $zonesArray[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }

        return $zonesArray;
    }

    /**
     * Returns the user language to show the proper installation language in the selector.
     * When the JSON file doesn't exist, returns en_EN
     *
     * @return string
     */
    public function getUserLanguage()
    {
        $dataLanguage = explode(';', filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
        $userLanguage = str_replace('-', '_', explode(',', $dataLanguage[0])[0]);
        $translationExists = file_exists(FS_FOLDER . '/Core/Translation/' . $userLanguage . '.json');

        return ($translationExists) ? $userLanguage : 'en_EN';
    }

    public function install(Array &$errors, Translator $i18n, Request $request)
    {
        $done = false;
        $dbData = [
            'host' => $request->request->get('db_host'),
            'port' => $request->request->get('db_port'),
            'user' => $request->request->get('db_user'),
            'pass' => $request->request->get('db_pass'),
            'name' => $request->request->get('db_name'),
            'socket' => $request->request->get('mysql_socket', '')
        ];

        switch ($request->request->get('db_type')) {
            case 'mysql':
                if (class_exists('mysqli')) {
                    $done = $this->testMysql($errors, $dbData);
                } else {
                    $errors[] = $i18n->trans('mysqli-not-found');
                }
                break;

            case 'postgresql':
                if (function_exists('pg_connect')) {
                    $done = $this->testPostgreSql($errors, $dbData);
                } else {
                    $errors[] = $i18n->trans('postgresql-not-found');
                }
                break;
        }

        if ($done) {
            $done = $this->saveInstall($request);
        } else {
            $errors[] = $i18n->trans('cant-connect-db');
        }

        return $done;
    }

    /**
     * Return a random string
     *
     * @param int $length
     *
     * @return bool|string
     */
    public function randomString($length = 20)
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    private function saveInstall(Request $request)
    {
        $file = fopen(FS_FOLDER . '/config.php', 'wb');
        if ($file) {
            fwrite($file, "<?php\n");
            fwrite($file, "define('FS_COOKIES_EXPIRE', 604800);\n");
            fwrite($file, "define('FS_DEBUG', true);\n");
            fwrite($file, "define('FS_LANG', '" . $request->request->get('fs_lang') . "');\n");
            fwrite($file, "define('FS_TIMEZONE', '" . $request->request->get('fs_timezone') . "');\n");
            fwrite($file, "define('FS_DB_TYPE', '" . $request->request->get('db_type') . "');\n");
            fwrite($file, "define('FS_DB_HOST', '" . $request->request->get('db_host') . "');\n");
            fwrite($file, "define('FS_DB_PORT', '" . $request->request->get('db_port') . "');\n");
            fwrite($file, "define('FS_DB_NAME', '" . $request->request->get('db_name') . "');\n");
            fwrite($file, "define('FS_DB_USER', '" . $request->request->get('db_user') . "');\n");
            fwrite($file, "define('FS_DB_PASS', '" . $request->request->get('db_pass') . "');\n");
            fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
            fwrite($file, "define('FS_DB_INTEGER', 'INTEGER');\n");
            fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");
            fwrite($file, "define('FS_CACHE_HOST', '" . $request->request->get('memcache_host') . "');\n");
            fwrite($file, "define('FS_CACHE_PORT', '" . $request->request->get('memcache_port') . "');\n");
            fwrite($file, "define('FS_CACHE_PREFIX', '" . $request->request->get('memcache_prefix') . "');\n");
            fwrite($file, "define('FS_MYDOCS', '');\n");
            if ($request->request->get('db_type') === 'MYSQL' && $request->request->get('mysql_socket') !== '') {
                fwrite($file, "\nini_set('mysqli.default_socket', '" . $request->request->get('mysql_socket') . "');\n");
            }
            fwrite($file, "\n");
            fclose($file);

            return true;
        }

        return false;
    }

    private function testMysql(&$errors, $dbData)
    {
        if ($dbData['socket'] !== '') {
            ini_set('mysqli.default_socket', $dbData['socket']);
        }

        // Omit the DB name because it will be checked on a later stage
        $connection = new \mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int) $dbData['port']);
        if ($connection->connect_error) {
            $errors[] = (string) $connection->connect_error;
        } else {
            // Check that the DB exists, if it doesn't, we create a new one
            $dbSelected = \mysqli_select_db($connection, $dbData['name']);
            if ($dbSelected) {
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE `' . $dbData['name'] . '`;';
            if ($connection->query($sqlCrearBD)) {
                return true;
            }

            $errors[] = (string) $connection->connect_error;
        }

        return false;
    }

    private function testPostgreSql(&$errors, $dbData)
    {
        $connectionStr = 'host=' . $dbData['host'] . ' port=' . $dbData['port'];
        $connection = \pg_connect($connectionStr . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
        if ($connection) {
            // Check that the DB exists, if it doesn't, we create a new one
            $connection2 = \pg_connect($connectionStr . ' dbname=' . $dbData['name'] . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
            if ($connection2) {
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (\pg_query($connection, $sqlCrearBD)) {
                return true;
            }

            $errors[] = (string) \pg_last_error($connection);
        }

        return false;
    }
}
