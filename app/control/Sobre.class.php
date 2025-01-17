<?php

/**
 * Sobre
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class Sobre extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
        
        $html = new THtmlRenderer('app/resources/sobre.html');

        // replace the main section variables
        $html->enableSection('main', array());
        
        $panel = new TPanelGroup('SAWTOOL 1.1.0.1');
        $panel->add($html);

        $vbox = TVBox::pack($panel);
        $vbox->style = 'display:block; width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        
         // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        parent::add($vbox);
    }
}
