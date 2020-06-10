<?php

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
        
        $panel = new TPanelGroup('SAWTOOL');
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
