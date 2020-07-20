<?php

/**
 * MinimumPasswordAgeForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class MinimumPasswordAgeForm extends TWindow
{
    protected $form; // form
    private $samba_tool;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        parent::setModal(true);
        parent::removePadding();
        parent::setSize(600,null);
        parent::setTitle('Idade mínima da senha (dias)');
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_minimum_password_age');
        
        // create the form fields
        $minimum_password_age = new TEntry('minimum_password_age');       
        $minimum_password_age->setMask('9!');
        
        $explicacao = new TLabel('Idade minima de senha. Valores em numero de dias, o padrão é 1, zero desabilita');
        
        if(isset($_REQUEST['value']))
        {
            $minimum_password_age->setValue(substr($_REQUEST['value'], 1));
        }
        
        $this->form->appendPage('Configuração');
        
        // add the form fields
        $this->form->addFields( [ $minimum_password_age ] ); 
        
        $minimum_password_age->addValidation( 'Idade mínima da senha (dias)', new TRequiredValidator);
        
        $minimum_password_age->setSize('100%'); 
        
        $this->form->appendPage('Explicação');
        
        $this->form->addFields( [ $explicacao ] );
        
        // define the form action
        $this->form->addAction('Save', new TAction(array($this, 'onSave')), 'fa:save green');        
        
        parent::add($this->form);
    }
    
    /**
     * Save form data
     * @param $param Request
     */
    public function onSave($param)
    {
    
     try
        {
            $data = $this->form->getData();

            $this->form->validate();            
                    
            $comando = "sudo " . $this->samba_tool . " domain passwordsettings set --min-pwd-age={$data->minimum_password_age}";        
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Configuração alterada com sucesso!', new TAction(array('SegurancaForm','onLoad'))); 
               parent::closeWindow();         
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
    
     public function onLoad()
    {

    }
}