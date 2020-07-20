<?php

/**
 * PasswordHistoryLengthForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class PasswordHistoryLengthForm extends TWindow
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
        parent::setTitle('Comprimento do histórico de senhas');
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_password_history_length');
        
        // create the form fields
        $password_history_length = new TEntry('password_history_length');       
        $password_history_length->setMask('9!');
        
        $explicacao = new TLabel('O tamanho do histórico da senha  pode colocar numeros inteiros para dias ou default, o default é 24');
        
        if(isset($_REQUEST['value']))
        {
            $password_history_length->setValue(substr($_REQUEST['value'], 1));
        }
        
        $this->form->appendPage('Configuração');
        
        // add the form fields
        $this->form->addFields( [ $password_history_length ] ); 
        
        $password_history_length->addValidation( 'Comprimento do histórico de senhas', new TRequiredValidator);
        
        $password_history_length->setSize('100%'); 
        
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
                    
            $comando = "sudo " . $this->samba_tool . " domain passwordsettings set --history-length={$data->password_history_length}";        
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