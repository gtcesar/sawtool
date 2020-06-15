<?php

/**
 * AccountLockoutThresholdForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class AccountLockoutThresholdForm extends TWindow
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
        parent::setTitle('Account lockout threshold (attempts)');
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_account_lockout_threshold');
        
        // create the form fields
        $account_lockout_threshold = new TEntry('account_lockout_threshold');       
        $account_lockout_threshold->setMask('9!');
        
        if(isset($_REQUEST['value']))
        {
            $account_lockout_threshold->setValue(substr($_REQUEST['value'], 1));
        }
        
        // add the form fields
        $this->form->addFields( [ $account_lockout_threshold ] ); 
        
        $account_lockout_threshold->addValidation( 'Account lockout threshold (mins)', new TRequiredValidator);
        
        $account_lockout_threshold->setSize('100%'); 
        
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
                    
            $comando = "sudo " . $this->samba_tool . " domain passwordsettings set  --account-lockout-threshold={$data->account_lockout_threshold}";        
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