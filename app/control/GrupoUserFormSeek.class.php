<?php

/**
 * GrupoUserFormSeek
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class GrupoUserFormSeek extends TWindow
{
    private $form; // form
    private $datagrid; // listing
    private $formgrid;
    private $loaded;
    private $deleteButton;
    private $samba_tool;
    private $user_ignore;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        parent::setSize(0.7, 0.6);
        parent::setTitle('Gerenciar usuarios do grupo: ');
        parent::removePadding();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        
        // creates the form       
        $this->form = new BootstrapFormBuilder('form_grupouser');    

        // create the form fields
        $grupo = new TEntry('grupo');
        $grupo->setValue($_REQUEST['grupo']);
        $grupo->setEditable(FALSE);
        
        $grupo_label = new TLabel('Grupo');
        
        $usuario = new TCombo('usuario');
        $usuario->enableSearch(true);
        
        $usuario_label = new TLabel('Usuario');

        // add a row with 2 slots
        $this->form->addFields( [ $grupo_label ], [ $grupo ],[ $usuario_label ], [ $usuario ] );    
        
        $usuario->addValidation('Usuario', new TRequiredValidator); // required field 

        $usuario->setSize('100%');   

        $this->form->addAction('Adicionar', new TAction(array($this, 'onSave')), 'far:save');
        
        // creates a Datagrid
        $this->datagrid =  new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->disableDefaultClick();
        $this->datagrid->setHeight(250);
        $this->datagrid->makeScrollable();
        

        // creates the datagrid columns
        $this->datagrid->addColumn( new TDataGridColumn('usuario',    'Usuario',    'left',   '100%') );

        
        $action1 = new TDataGridAction([$this, 'onDelete'],   ['usuario' => '{usuario}', 'grupo' => $_REQUEST['grupo']] );
        $this->datagrid->addAction($action1, _t('Delete'), 'far:trash-alt red');
        

        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        
        $panel = new TPanelGroup( '' );
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    /**
     * Register the filter in the session
     */
    public function onSave()
    {
    
        try
        {
            $data = $this->form->getData();

            $this->form->validate();
            
            $usuario = $data->usuario;
            $grupo = $data->grupo;
            
            //repassamos para o onreload o parametro do grupo para ele saber onde estava
            $array_grupo = ['grupo' => $grupo];
         
            $comando = "sudo " . $this->samba_tool . " group addmembers '{$grupo}' '{$usuario}'";        
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Usuario adicionado com sucesso!'); 
               $this->onReload( $array_grupo ); // reload the listing          
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
    
    
    /**
     * Load the datagrid with data
     */
    public function onReload( $param )
    {    
    
        $grupo = $param['grupo'];
        
        $this->datagrid->clear();   
        
        $comando = "sudo " . $this->samba_tool . " group listmembers '{$grupo}'";        
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
        
        //listamos os usuarios do grupo passado pela request
        $comando1 = "sudo " . $this->samba_tool . " group listmembers '{$grupo}'";         
        $result1 = shell_exec($comando1);
        
        $usuarios1 = explode("\n", $result1);        
        array_pop($usuarios1);
        sort($usuarios1);

        //obtemos todos os usuarios do sistema para compararmos quem esta adicionado na lista
        if($this->user_ignore != '')
        {
            $user_ignore = "|egrep -v '{$this->user_ignore}'";
        }else{
            $user_ignore = '';
        }
                
        $comando2 = "sudo " . $this->samba_tool . " user list{$user_ignore}";          
        $result2 = shell_exec($comando2);
        
        $usuarios2 = explode("\n", $result2);        
        array_pop($usuarios2);
        sort($usuarios2);
        
        
        //comparamos os usuarios do grupo a lista de usuarios para poder exibir na combo apenas os usuarios que nao estao no grupo
        $usuarios3 = array_diff($usuarios2, $usuarios1);
        
        $usuarios4 = array();
        $usuarios4[''] = '';
        
        foreach ($usuarios3 as $key => $value) { 
        
            $usuarios4[$value] = $value;            
        }
        
        TCombo::reload('form_grupouser','usuario', $usuarios4);          

    }
    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {
    
        $usuario = $param['usuario']; // get the parameter $key
        $grupo = $param['grupo'];
    
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir o usuario: <b>{$usuario}</b>?", $action);

    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $usuario = $param['usuario']; // get the parameter $key
      $grupo = $param['grupo'];      
      
      //repassamos para o onreload o parametro do grupo para ele saber onde estava
      $array_grupo = ['grupo' => $grupo];
      
      $comando = "sudo " . $this->samba_tool . " group removemembers '{$grupo}' '{$usuario}'";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario excluido com sucesso!'); 
         $this->onReload( $array_grupo ); // reload the listing          
      }   

    }
    
    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
