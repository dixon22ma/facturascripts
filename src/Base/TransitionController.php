<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Base;

use FacturaScripts\Core\Base\Translator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of TransitionController
 *
 * @author carlos
 */
class TransitionController extends Controller
{

    /**
     * Translator engine.
     *
     * @var Translator
     */
    protected $i18n;
    
    protected $langcode2;

    protected function init(Request $request)
    {
        if ($request->cookies->get('fsLang')) {
            $this->i18n = new Translator($request->cookies->get('fsLang'));
        } else {
            $this->i18n = new Translator();
        }
        
        $this->langcode2 = substr($request->cookies->get('fsLang', FS_LANG), 0, 2);
    }
    
    protected function getRenderParams()
    {
        return [
            'langcode2' => $this->langcode2,
            'i18n' => $this->i18n,
            'title' => ''
        ];
    }
}
