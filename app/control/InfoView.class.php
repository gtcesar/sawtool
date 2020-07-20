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
        
        // creates the action button
        $button = new TButton('restart');
        $button->setLabel('Reiniciar SAMBA4'); 
        $button->setImage('fas: fa-sync');        
        $button->addFunction("__adianti_load_page('index.php?class=InfoView&method=onRestart');");

        $panel->add($table);
        $panel->addFooter($button);
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        
        parent::add($vbox);
        
    }
    
    public function onRestart($param)
    {
        try
        {    
            $comando = "sudo /bin/systemctl restart samba-ad-dc";   
            $result = shell_exec($comando);
            
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        } 

    }
}
