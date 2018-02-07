<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Controller;

use FacturaScripts\Base\Installer;
use FacturaScripts\Base\TransitionController;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request)
    {
        /// Is FacturaScripts installed?
        if (!file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'config.php')) {
            return $this->redirect('/install');
        }

        $this->init($request);
        return $this->render('Login/Login.html.twig', $this->getRenderParams());
    }

    /**
     * Matches /install exactly
     *
     * @Route("/install", name="install")
     */
    public function install(Request $request)
    {
        $installer = new Installer();
        if (!defined('FS_LANG')) {
            define('FS_LANG', $request->get('fs_lang', $installer->getUserLanguage()));
        }
        $i18n = new Translator();

        $errors = [];
        if ($request->getMethod() === 'POST' && $installer->install($errors, $i18n, $request)) {
            return $this->redirect('/');
        }

        $params = [
            'errors' => $errors,
            'i18n' => $i18n,
            'languages' => $installer->getAvailableLanguages($i18n),
            'license' => file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'COPYING'),
            'memcache_prefix' => $installer->randomString(8),
            'timezones' => $installer->getTimezoneList(),
            'userLangCode' => FS_LANG
        ];
        return $this->render('Installer/Install.html.twig', $params);
    }
}
