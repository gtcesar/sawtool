<?php

/**
 * DashboardView
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class DashboardView extends TPage
{
    
    private $samba_tool;
    private $group_ignore;
    private $user_ignore;
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->group_ignore = $config['samba-tool']['group_ignore'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        
        //obter grupos
        if($this->group_ignore != '')
        {
            $group_ignore = "|egrep -v '{$this->group_ignore}'";
        }else{
            $group_ignore = '';
        }
                
        $comando_g = "sudo " . $this->samba_tool . " group list{$group_ignore}";        
        $result_g = shell_exec($comando_g);
        
        $array_g = explode("\n", $result_g);        
        array_pop($array_g);
        
        $grupos = count($array_g); 
        
        
        //obter usuarios
        if($this->user_ignore != '')
        {
            $user_ignore = "|egrep -v '{$this->user_ignore}'";
        }else{
            $user_ignore = '';
        }
                
        $comando_u = "sudo " . $this->samba_tool . " user list{$user_ignore}";        
        $result_u = shell_exec($comando_u);
        
        $array_u = explode("\n", $result_u);        
        array_pop($array_u);
        
        $usuarios = count($array_u);        
        
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        
        $div = new TElement('div');
        $div->class = "row";
        
        $indicator1 = new THtmlRenderer('app/resources/info-box.html');
        $indicator2 = new THtmlRenderer('app/resources/info-box.html');
        
        $indicator1->enableSection('main', ['title'     => 'Grupos',
                                           'icon'       => 'users',
                                           'background' => 'green',
                                           'value'      => $grupos ] );
        
        $indicator2->enableSection('main', ['title'      => 'Usuarios',
                                            'icon'       => 'user',
                                            'background' => 'orange',
                                            'value'      => $usuarios ] );
        $div->add( $i1 = TElement::tag('div', $indicator1) );
        $div->add( $i2 = TElement::tag('div', $indicator2) );
        $div->add( $g1 = new PieChartView(false) );
        
        
        $i1->class = 'col-sm-6';
        $i2->class = 'col-sm-6';
        $g1->class = 'col-sm-12';
        
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($div);
        
        parent::add($vbox);
    }
}
