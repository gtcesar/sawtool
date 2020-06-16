<?php

/**
 * PasswordComplexityForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class PasswordComplexityForm extends TWindow
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
        parent::setTitle('Complexidade da senha');
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_password_complexity');
        
        // create the form fields
        $pass_complexity = new TRadioGroup('pass_complexity');       
        $pass_complexity->setLayout('horizontal');
        $pass_complexity->setUseButton();
        $pass_complexity_itens = ['on' =>'On', 'off' => 'Off'];
        $pass_complexity->addItems($pass_complexity_itens);
        
        if(isset($_REQUEST['value']))
        {
            $pass_complexity->setValue(substr($_REQUEST['value'], 1));
        }
        
        // add the form fields
        $this->form->addFields( [ $pass_complexity ] ); 
        
        $pass_complexity->addValidation( 'Complexidade da senha', new TRequiredValidator);
        
        $pass_complexity->setSize('100%'); 
        
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
                    
            $comando = "sudo " . $this->samba_tool . " domain passwordsettings set --complexity={$data->pass_complexity}";        
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