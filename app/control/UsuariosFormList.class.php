<?php

/**
 * UsuariosFormList
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class UsuariosFormList extends TPage
{
    protected $form; // form
    protected $datagrid; // datagrid
    private $samba_tool;
    private $user_ignore;    
    
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        $config = parse_ini_file('app/config/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_usuarios');
        $this->form->setFormTitle('Usuarios');
        
        // create the form fields

        $nome = new TEntry('nome');  
        $nome_label = new TLabel('Nome');

        $sobrenome = new TEntry('sobrenome');  
        $sobrenome_label = new TLabel('Sobrenome');

        $login = new TEntry('login');  
        $login_label = new TLabel('Login');

        $senha = new TEntry('senha');  
        $senha_label = new TLabel('Senha');

        $alterar_senha = new TRadioGroup('alterar_senha');       
        $alterar_senha->setLayout('horizontal');
        $alterar_senha->setUseButton();
        $alterar_senha_itens = ['S' =>'Sim', 'N' => 'Não'];
        $alterar_senha->addItems($alterar_senha_itens);

        $alterar_senha_label = new TLabel('Alterar senha no próximo login?');  
  
        // add a row with 2 slots
        $this->form->addFields( [ $nome_label ], [ $nome ] );     
        $this->form->addFields( [ $sobrenome_label ], [ $sobrenome ] );     
        $this->form->addFields( [ $login_label ], [ $login ] );     
        $this->form->addFields( [ $senha_label ], [ $senha ] );     
        $this->form->addFields( [ $alterar_senha_label ], [ $alterar_senha ] );        
         
        $nome->addValidation('Nome', new TRequiredValidator); // required field
        $sobrenome->addValidation('Sobrenome', new TRequiredValidator); // required field
        $login->addValidation('Login', new TRequiredValidator); // required field
        $senha->addValidation('Senha', new TRequiredValidator); // required field
        $alterar_senha->addValidation('Alterar senha no próximo login?', new TRequiredValidator); // required field

        $nome->setSize('100%');   
        $sobrenome->setSize('100%'); 
        $login->setSize('100%'); 
        $senha->setSize('100%'); 
        $alterar_senha->setSize('100%'); 

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper( new TDataGrid );
        $this->datagrid->disableDefaultClick();
        $this->datagrid->style = 'width: 100%';
        

        // creates the datagrid columns
        $column_login = new TDataGridColumn('login', 'Login', 'left');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_login);

        $action1 = new TDataGridAction(array($this, 'onDelete'));
        $action1->setLabel(_t('Delete'));
        $action1->setImage('far:trash-alt red');
        $action1->setField('login');

        $action2 = new TDataGridAction(array($this, 'onPassword'));
        $action2->setLabel('Alterar senha');
        $action2->setImage('fa:lock blue');
        $action2->setField('login');
        
        // add the actions to the datagrid
        $this->datagrid->addAction($action1);
        $this->datagrid->addAction($action2);
        
        // create the datagrid model
        $this->datagrid->createModel();      
        
         // search box
        $input_search = new TEntry('input_search');
        $input_search->placeholder = _t('Search');
        $input_search->setSize('100%');
        
        // enable fuse search by column grupo
        $this->datagrid->enableSearch($input_search, 'login');
        
        $panel = new TPanelGroup( 'Login' );
        $panel->addHeaderWidget($input_search);
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }


    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $this->datagrid->clear();     
        
        if($this->user_ignore != '')
        {
            $user_ignore = "|egrep -v '{$this->user_ignore}'";
        }else{
            $user_ignore = '';
        }
                
        $comando = "sudo " . $this->samba_tool . " user list{$user_ignore}";        
        $result = shell_exec($comando);
        
        $logins = explode("\n", $result);        
        array_pop($logins);
        sort($logins);
        
        foreach($logins as $login){
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->login = $login;
            $this->datagrid->addItem($item);
        }
    }
    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {
        $login = $param['login']; // get the parameter $key
    
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir o usuario: <b>{$login}</b> ?", $action);
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $login = $param['login']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . " user delete {$login}";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario excluido com sucesso!'); 
         $this->onReload(); // reload the listing          
      }    

    }

    public function onPassword( $param )
    {

        $form = new BootstrapFormBuilder('senha_form');
        
        $login = new TEntry('login');
        $login->setValue($param['login']);
        $login->setEditable(FALSE);

        $senha = new TEntry('senha');

        $alterar_senha = new TRadioGroup('alterar_senha');       
        $alterar_senha->setLayout('horizontal');
        $alterar_senha->setUseButton();
        $alterar_senha_itens = ['S' =>'Sim', 'N' => 'Não'];
        $alterar_senha->addItems($alterar_senha_itens);
        $alterar_senha->setValue('S');

        $login->setSize('100%'); 
        $senha->setSize('100%'); 
        $alterar_senha->setSize('100%'); 
        
        $form->addFields( [new TLabel('Login')], [$login]);
        $form->addFields( [new TLabel('Nova senha')], [$senha]);
        $form->addFields( [new TLabel('Alterar senha no próximo login?')], [$alterar_senha]);
        
        $form->addAction('Salvar', new TAction([__CLASS__, 'onAlteraSenha']), 'fa:save green');
        
        // show the input dialog
        new TInputDialog('Alterar senha', $form);

    }

    public function onAlteraSenha( $param )
    {
        if($param['senha'] == '')
        {
            new TMessage('error', 'A senha não pode ser vazia!'); 
        }else{

            $login = $param['login']; // get the parameter $key
            $senha = $param['senha']; // get the parameter $key

            if($param['alterar_senha'] == 'S')
            {
                $alterar_senha = '--must-change-at-next-login';
            }else{
              $alterar_senha = '';
            }
      
            $comando = "sudo " . $this->samba_tool . " user setpassword {$login} --newpassword={$senha} {$alterar_senha}";        
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Senha alterada com sucesso!'); 
               $this->onReload(); // reload the listing          
            }   

        }
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
            
            if($data->alterar_senha == 'S')
            {
                $alterar_senha = '--must-change-at-next-login';
            }else{
              $alterar_senha = '';
            }
      
            $comando = "sudo " . $this->samba_tool . " user create {$data->login} {$data->senha} --given-name='{$data->nome}' --surname='{$data->sobrenome}' {$alterar_senha}";   
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Usuario criado com sucesso!'); 
               $this->form->clear();
               $this->onReload(); // reload the listing          
            }else{
               new TMessage('error', "Ocorreu um erro, verifique se o usuario já existe!");
               $this->form->clear();
            } ;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }    
         

    }
    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }
}
