<?php

/**
 * InfoView
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class InfoView extends TPage
{

    private $samba_tool;
    
    function __construct()
    {
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        $div = new TElement('div'); 
        $div->style = 'text-align: left;';
        $pre = new TElement('pre');   
                
        $comando = "sudo " . $this->samba_tool . " domain info 127.0.0.1";        
        $result = shell_exec($comando);
        
        $info = explode("\n", $result);        
        array_pop($info); 

        
        // creates a panel
        $panel = new TPanelGroup('Informações do dominio');
        
        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        
         foreach($info as $inf){
            $table->addRowSet($inf);
        }       

        $panel->add($table);
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        
        parent::add($vbox);
        
    }
}
