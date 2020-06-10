<?php

/**
 * LoginLdap
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class GruposList extends TPage
{
    private $datagrid;
    private $samba_tool;
    private $group_ignore;
    private $user_ignore;
    private $listagem;
    
    public function __construct()
    {
        parent::__construct();
        
        $config = parse_ini_file('app/config/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->group_ignore = $config['samba-tool']['group_ignore'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];
        
        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->disableDefaultClick();
        $this->datagrid->width = '100%';
        
        // add the columns
        $this->datagrid->addColumn( new TDataGridColumn('grupo',    'Grupo',    'left',   '100%') );
        
        //$action1 = new TDataGridAction([$this, 'onView'],   ['grupo' => '{grupo}'] );
        //$this->datagrid->addAction($action1, 'View', 'fa:search blue');
        
        $action1 = new TDataGridAction([$this, 'onGroup'],   ['grupo' => '{grupo}'] );
        $this->datagrid->addAction($action1, 'View', 'fa:search blue');
        
        // creates the datagrid model
        $this->datagrid->createModel();
        
        // search box
        $input_search = new TEntry('input_search');
        $input_search->placeholder = _t('Search');
        $input_search->setSize('100%');
        
        // enable fuse search by column grupo
        $this->datagrid->enableSearch($input_search, 'grupo');
        
        $panel = new TPanelGroup( 'Grupos' );
        $panel->addHeaderWidget($input_search);
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('');
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        parent::add($vbox);
    }
    
    /**
     * Load the data into the datagrid
     */
    function onReload()
    {
        $this->datagrid->clear();              
                
        $comando = "sudo " . $this->samba_tool . " group list|egrep -v '{$this->group_ignore}'";        
        $result = shell_exec($comando);
        
        $grupos = explode("\n", $result);        
        array_pop($grupos);
        sort($grupos);
        
        foreach($grupos as $grupo){
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->grupo = strtoupper($grupo);
            $this->datagrid->addItem($item);
        }

    }
    
    /**
     * Executed when the user clicks at the view button
     */
    public function onView($param)
    {
        // get the parameter and shows the message
        $grupo = $param['grupo'];
        
        $comando = "sudo " . $this->samba_tool . " group listmembers {$grupo}";        
        $result = shell_exec($comando);
        
        new TMessage('info', "Grupo selecionado : <b>$grupo</b> <br>Membros : <b>$result</b>");
    }
    
      /**
     * Open an input dialog
     */
    public function onGroup( $param )
    {
    
        // get the parameter and shows the message
        $grupo = $param['grupo'];
        
        // creates one datagrid
        $this->listagem = new BootstrapDatagridWrapper(new TDataGrid);
        $this->listagem->disableDefaultClick();
        $this->listagem->width = '100%';
        $this->listagem->setHeight(200);
        $this->listagem->makeScrollable();
        
        // add the columns
        $this->listagem->addColumn( new TDataGridColumn('usuario',    'Usuario',    'left',   '100%') );
        
        $action1 = new TDataGridAction([$this, 'onDeleteUsuario'],   ['usuario' => '{usuario}', 'grupo_atual' => $grupo] );
        $this->listagem->addAction($action1, 'Excluir', 'far:trash-alt red');

        // creates the datagrid model
        $this->listagem->createModel();
        $this->listagem->clear();

        $this->listagem->clear();              
                
        $comando = "sudo " . $this->samba_tool . " group listmembers {$grupo}";         
        $result = shell_exec($comando);
        
        $usuarios = explode("\n", $result);        
        array_pop($usuarios);
        sort($usuarios);
        
        foreach($usuarios as $usuario){
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->usuario = $usuario;
            $this->listagem->addItem($item);
        }

               
                
        // input fields
        $grupo_atual   = new THidden('grupo_atual');
        $grupo_atual->setValue($grupo);
        $usuario   = new TCombo('usuario');
        
        
        $comando2 = "sudo " . $this->samba_tool . " user list|egrep -v '{$this->user_ignore}'";        
        $result2 = shell_exec($comando2);
        
        $usuarios2 = explode("\n", $result2);        
        array_pop($usuarios2);
        sort($usuarios2);
        
        $usuarios3 = array_diff($usuarios2, $usuarios);
        
        $usuarios4 = array();
        
        foreach ($usuarios3 as $key => $value) { 
        
            $usuarios4[$value] = $value;            
        }
        
        $usuario->addItems($usuarios4);
        
        $form = new BootstrapFormBuilder('input_form');
        $form->add($this->listagem);        
        $form->addFields( [new TLabel('Usuario')],     [$grupo_atual,$usuario] );      

        // form action
        $form->addAction('Adicionar', new TAction(array($this, 'onAdicionar')), 'far:save'); 

        // show input dialot
        new TInputDialog('Informações do Grupo', $form);
    }
    
        /**
     * Save form data
     * @param $param Request
     */
    public function onAdicionar( $param )
    {
    
      $usuario = $param['usuario'];
      $grupo = $param['grupo_atual'];
     
      $comando = "sudo " . $this->samba_tool . " group addmembers {$grupo} {$usuario}";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario adicionado com sucesso!'); 
         $this->onReload(); // reload the listing          
      }else{
         new TMessage('error', "Ocorreu um erro!");
      }          

    }
    
        /**
     * Ask before deletion
     */
    public function onDeleteUsuario($param)
    {
        $usuario = $param['usuario']; // get the parameter $key
        $grupo = strtolower($param['grupo_atual']);
    
        // define the delete action
        $action = new TAction(array($this, 'DeleteUsuario'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir o usuario: <b>{$usuario}</b>?", $action);
    }
    
        /**
     * Delete a record
     */
    public function DeleteUsuario($param)
    {

      $usuario = $param['usuario']; // get the parameter $key
      $grupo = strtolower($param['grupo_atual']);
      
      $comando = "sudo " . $this->samba_tool . " group removemembers {$grupo} {$usuario}";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Usuario excluido com sucesso!'); 
         $this->onReload(); // reload the listing          
      }   
 

    }
    
    /**
     * shows the page
     */
    function show()
    {
        $this->onReload();
        parent::show();
    }
}
