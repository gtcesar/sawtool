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
        $id = new TEntry('id');
        $usuario = new TEntry('usuario');    
        
        $usuario_label = new TLabel('Usuario');
  
        // add a row with 2 slots
        $this->form->addFields( [ $usuario_label ], [ $usuario ] );        
         
        $usuario->addValidation('Login', new TRequiredValidator); // required field

        $usuario->setSize('100%');   

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper( new TDataGrid );
        $this->datagrid->disableDefaultClick();
        $this->datagrid->style = 'width: 100%';
        

        // creates the datagrid columns
        $column_usuario = new TDataGridColumn('usuario', 'Usuario', 'left');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_usuario);

        $action2 = new TDataGridAction(array($this, 'onDelete'));
        $action2->setLabel(_t('Delete'));
        $action2->setImage('far:trash-alt red');
        $action2->setField('usuario');
        
        // add the actions to the datagrid
        $this->datagrid->addAction($action2);
        
        // create the datagrid model
        $this->datagrid->createModel();        
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid));
        
        parent::add($container);
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $this->datagrid->clear();              
                
        $comando = "sudo " . $this->samba_tool . " user list|egrep -v '{$this->user_ignore}'";        
        $result = shell_exec($comando);
        
        $usuarios = explode("\n", $result);        
        array_pop($usuarios);
        sort($usuarios);
        
        foreach($usuarios as $usuario){
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->usuario = $usuario;
            $this->datagrid->addItem($item);
        }
    }
    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {
        $usuario = $param['usuario']; // get the parameter $key
    
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir o usuario: <b>{$usuario}</b> ?", $action);
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $usuario = $param['usuario']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . " user delete {$usuario}";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario excluido com sucesso!'); 
         $this->onReload(); // reload the listing          
      }    

    }
    
    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
    
      $data = $this->form->getData();

      $this->form->validate();
      
      $comando = "sudo " . $this->samba_tool . " user create {$data->usuario} fasd2020";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario criado com sucesso!'); 
         $this->form->clear();
         $this->onReload(); // reload the listing          
      }else{
         new TMessage('error', "Ocorreu um erro, verifique se o usuario já existe!");
         $this->form->clear();
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
