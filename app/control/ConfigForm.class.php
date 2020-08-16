<?php

/**
 * ConfigForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class ConfigForm extends TPage
{
    private $form;
    private $output;
    private $ip_servidor;
    private $dominio;
    private $user_admin;
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
        
        $this->output = $config['debug']['output'];
        $this->ip_servidor = $config['ldap']['server'];
        $this->dominio = $config['ldap']['dominio'];
        $this->user_admin = $config['ldap']['user'];
        $this->samba_tool = $config['samba-tool']['path'];
        $this->group_ignore = $config['samba-tool']['group_ignore'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_config');
        $this->form->setFormTitle('Formulario de configurações');
        
        // create the form fields
        $output = new TRadioGroup('output');       
        $output->setLayout('horizontal');
        $output->setUseButton();
        $output_itens = ['on' =>'Ligado', 'off' => 'Desligado'];
        $output->addItems($output_itens);
        $output->setValue($this->output);
        $output_label = new TLabel('Exibir saida de comandos');
        
        $ip_servidor = new TEntry('ip_servidor');  
        $ip_servidor->setValue($this->ip_servidor);        
        $ip_servidor_label = new TLabel('IP do servidor');

        $dominio = new TEntry('dominio');  
        $dominio->setValue($this->dominio);
        $dominio_label = new TLabel('Dominio');

        $user_admin = new TEntry('user_admin');  
        $user_admin->setValue($this->user_admin);
        $user_admin_label = new TLabel('Usuario administrador');

        $samba_tool = new TEntry('samba_tool');  
        $samba_tool->setValue($this->samba_tool);
        $samba_tool_label = new TLabel('Path para samba-tool');

        $user_ignore = new TMultiSearch('user_ignore');  
        $user_ignore->setMinLength(1);
        
        //listamos os usuarios ignorados
        if ($this->user_ignore != '') {       
            
            $uignore_arg = explode('|', $this->user_ignore);     
            sort($uignore_arg);    
            
            $uignore_array = array();             
          
            foreach ($uignore_arg as $key => $value) { 
            
                $uignore_array[$value] = $value;            
            }
   
         } else {
            $uignore_array = '';
         }
         
        //listamos os usuarios cadastrados no samba
        $com_ucadastrados = "sudo " . $this->samba_tool . " user list";        
        $result_ucadastrados = shell_exec($com_ucadastrados);
  
        $ucadastrados_arg = explode("\n", $result_ucadastrados);        
        array_pop($ucadastrados_arg);   
        sort($ucadastrados_arg);            
        
        $ucadastrados_array = array();
  
        foreach ($ucadastrados_arg as $key => $value) { 
        
            $ucadastrados_array[$value] = $value;            
        }
        
        $user_ignore->addItems($ucadastrados_array);
        $user_ignore->setValue($uignore_array);    
    
        $user_ignore_label = new TLabel('Usuarios ignorados');
        
        $group_ignore = new TMultiSearch('group_ignore');  
        $group_ignore->setMinLength(1);
        
        //listamos os usuarios ignorados
        if ($this->group_ignore != '') {       
            
            $gignore_arg = explode('|', $this->group_ignore);     
            sort($gignore_arg);    
            
            $gignore_array = array();             
          
            foreach ($gignore_arg as $key => $value) { 
            
                $gignore_array[$value] = $value;            
            }
   
         } else {
            $gignore_array = '';
         }
         
        //listamos os grupos cadastrados no samba
        $com_gcadastrados = "sudo " . $this->samba_tool . " group list";        
        $result_gcadastrados = shell_exec($com_gcadastrados);
  
        $gcadastrados_arg = explode("\n", $result_gcadastrados);        
        array_pop($gcadastrados_arg);   
        sort($gcadastrados_arg);            
        
        $gcadastrados_array = array();
  
        foreach ($gcadastrados_arg as $key => $value) { 
        
            $gcadastrados_array[$value] = $value;            
        }
        
        $group_ignore->addItems($gcadastrados_array);
        $group_ignore->setValue($gignore_array);  
        
        $group_ignore_label = new TLabel('Grupos ignorados');

  
        // add a row with 2 slots
        $this->form->addFields( [ $output_label ], [ $output ] ); 
        $this->form->addFields( [ $ip_servidor_label ], [ $ip_servidor ] );     
        $this->form->addFields( [ $dominio_label ], [ $dominio ] ); 
        $this->form->addFields( [ $user_admin_label ], [ $user_admin ] ); 
        $this->form->addFields( [ $samba_tool_label ], [ $samba_tool ] );    
        $this->form->addFields( [ $user_ignore_label ], [ $user_ignore ] ); 
        $this->form->addFields( [ $group_ignore_label ], [ $group_ignore ] );        
         
        $ip_servidor->addValidation('IP do servidor', new TRequiredValidator); // required field
        $dominio->addValidation('Dominio', new TRequiredValidator); // required field
        $user_admin->addValidation('Usuario administrador', new TRequiredValidator); // required field
        $samba_tool->addValidation('Path para samba-tool', new TRequiredValidator); // required field


        $ip_servidor->setSize('100%');   
        $dominio->setSize('100%'); 
        $user_admin->setSize('100%'); 
        $samba_tool->setSize('100%'); 
        $user_ignore->setSize('100%'); 
        $group_ignore->setSize('100%'); 

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }
    
    
    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {

        try
        {
            $data = $this->form->getData();

            $this->form->validate();
            
            if($data->user_ignore != '')
            {
                $user_ignore = implode('|', $data->user_ignore);
            }else{
                $user_ignore = '';
            }
            
            if($data->group_ignore != ''){
                $group_ignore = implode('|', $data->group_ignore);
            }else{
                $group_ignore = '';
            }
            
            $fp = fopen('param/config.ini', "w+");            
           
$dados = '[debug]
output = "'. $data->output .'"
[ldap]
server = "'. $data->ip_servidor .'"
dominio = "'. $data->dominio .'"
user = "'. $data->user_admin .'"
[samba-tool]
path = "'. $data->samba_tool .'"
user_ignore = "'. $user_ignore .'"
group_ignore = "'. $group_ignore .'"';     
       
            $ini_file = utf8_decode($dados);
            
            if (fwrite($fp, utf8_encode($ini_file))) {
               // Fecha o arquivo
               fclose($fp);
               new TMessage('info', 'Configurações salva com sucesso!');      
            }else{
               new TMessage('error', "Ocorreu um erro, tente novamente!");
               $this->form->clear();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }    
         

    }
}
