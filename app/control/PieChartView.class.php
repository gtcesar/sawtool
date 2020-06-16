<?php

/**
 * PieChartView
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class PieChartView extends TPage
{

    private $samba_tool;
    private $group_ignore;
    private $user_ignore;
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct( $show_breadcrumb = true )
    {
        
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->group_ignore = $config['samba-tool']['group_ignore'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        $html = new THtmlRenderer('app/resources/google_pie_chart.html');
        $data = array();
        $data[] = [ 'Usuario', 'Value' ];       

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
        
        foreach ($array_g as $key => $value) { 
        
            $comando_m = "sudo " . $this->samba_tool . " group listmembers '{$value}'";
            $result_m = shell_exec($comando_m);
            
            $array_m = explode("\n", $result_m);        
            array_pop($array_m);
            
            $qtd = count($array_m); 
        
            array_push($data, array($value, $qtd));          
        }
        
        
        # PS: If you use values from database ($row['total'), 
        # cast to float. Ex: (float) $row['total']
        
        // replace the main section variables
        $html->enableSection('main', array('data'   => json_encode($data),
                                           'width'  => '100%',
                                           'height'  => '600px',
                                           'title'  => 'Usuarios por grupo',
                                           'uniqid' => uniqid()));
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($html);
        parent::add($container);
    }
}
