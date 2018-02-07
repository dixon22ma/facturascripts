<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Controller;

Use FacturaScripts\Base\TransitionController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Description of DefaultController
 *
 * @author carlos
 */
class DefaultController extends TransitionController
{

    /**
     * Matches / exactly
     *
     * @Route("/", name="home_page")
     */
    public function index()
    {
        if (!file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'config.php')) {
            return $this->redirect('/install');
        }

        return $this->render('base.html.twig');
    }

    /**
     * Matches /install exactly
     *
     * @Route("/install", name="home_page")
     */
    public function install()
    {
        $params = [
            'errors' => [],
            'i18n' => $this->get('translator'),
            'languages' => $this->getAvailableLanguages(),
            'license' => file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'COPYING'),
            'memcache_prefix' => $this->randomString(8),
            'timezones' => $this->getTimezoneList(),
            'userLangCode' => 'en'
        ];

        return $this->render('Installer/Install.html.twig', $params);
    }

    /**
     * Returns an array with the languages with available translations.
     *
     * @return array
     */
    private function getAvailableLanguages()
    {
        $languages = [];
        $dir = FS_FOLDER . '/Core/Translation';
        foreach (scandir($dir, SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName !== '.' && $fileName !== '..' && !is_dir($fileName) && substr($fileName, -5) === '.json') {
                $key = substr($fileName, 0, -5);
                $languages[$key] = $this->get('translator')->trans('languages-' . substr($fileName, 0, -5));
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
    private function getTimezoneList()
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
     * Return a random string
     *
     * @param int $length
     *
     * @return bool|string
     */
    private function randomString($length = 20)
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}
