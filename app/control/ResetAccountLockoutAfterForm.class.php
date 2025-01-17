<?php

/**
 * ResetAccountLockoutAfterForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class ResetAccountLockoutAfterForm extends TWindow
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
        parent::setTitle('Redefinir o bloqueio de conta após (min)');
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_reset_account_lockout_after');
        
        // create the form fields
        $reset_account_lockout_after = new TEntry('reset_account_lockout_after');       
        $reset_account_lockout_after->setMask('9!');
        
        $explicacao = new TLabel('Após esse tempo, o número registrado de tentativas são reiniciadas do zero, pode colocar numeros inteiros para minutos ou default o default é 30');
        
        if(isset($_REQUEST['value']))
        {
            $reset_account_lockout_after->setValue(substr($_REQUEST['value'], 1));
        }
        
        $this->form->appendPage('Configuração');
        
        // add the form fields
        $this->form->addFields( [ $reset_account_lockout_after ] ); 
        
        $reset_account_lockout_after->addValidation( 'Redefinir o bloqueio de conta após (min)', new TRequiredValidator);
        
        $reset_account_lockout_after->setSize('100%'); 
        
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
                    
            $comando = "sudo " . $this->samba_tool . " domain passwordsettings set --reset-account-lockout-after={$data->reset_account_lockout_after}";        
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