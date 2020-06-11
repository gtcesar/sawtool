<?php

/**
 * GruposFormList
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class GruposFormList extends TPage
{
    private $datagrid;
    private $samba_tool;
    private $group_ignore;
    private $user_ignore;
    private $listagem;
    
    public function __construct()
    {
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->group_ignore = $config['samba-tool']['group_ignore'];
        $this->user_ignore = $config['samba-tool']['user_ignore'];

         // creates the form
        $this->form = new BootstrapFormBuilder('form_grupos');
        $this->form->setFormTitle('Grupos');
        
        // create the form fields

        $nome = new TEntry('nome');  
        $nome_label = new TLabel('Nome');

        $descricao = new TEntry('descricao');  
        $descricao_label = new TLabel('Descrição');
  
  
        // add a row with 2 slots
        $this->form->addFields( [ $nome_label ], [ $nome ] );     
        $this->form->addFields( [ $descricao_label ], [ $descricao ] );     
       
         
        $nome->addValidation('Nome', new TRequiredValidator); // required field

        $nome->setSize('100%');   
        $descricao->setSize('100%'); 

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        
        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->disableDefaultClick();
        $this->datagrid->width = '100%';
        
        // add the columns
        $this->datagrid->addColumn( new TDataGridColumn('grupo',    'Grupo',    'left',   '100%') );
        

        $action1 = new TDataGridAction([$this, 'onDelete'],   ['grupo' => '{grupo}'] );
        $this->datagrid->addAction($action1, _t('Delete'), 'far:trash-alt red');

        //$action2 = new TDataGridAction([$this, 'onGroup'],   ['grupo' => '{grupo}'] );
        //$this->datagrid->addAction($action2, 'Gerenciar usuarios', 'fa:users-cog blue');

        $action2 = new TDataGridAction(['GrupoUserFormSeek', 'onReload'],   ['grupo' => '{grupo}'] );
        $this->datagrid->addAction($action2, 'Gerenciar usuarios', 'fa:users-cog blue');

        
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
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    
    /**
     * Load the data into the datagrid
     */
    function onReload()
    {
        $this->datagrid->clear();   
        
        if($this->group_ignore != '')
        {
            $group_ignore = "|egrep -v '{$this->group_ignore}'";
        }else{
            $group_ignore = '';
        }
                
        $comando = "sudo " . $this->samba_tool . " group list{$group_ignore}";        
        $result = shell_exec($comando);
        
        $grupos = explode("\n", $result);        
        array_pop($grupos);
        sort($grupos);
        
        foreach($grupos as $grupo){
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->grupo = $grupo;
            $this->datagrid->addItem($item);
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
            
            if($data->descricao != '')
            {
                $descricao = "--description='{$data->descricao}'";
            }else{
              $descricao = '';
            }
      
            $comando = "sudo " . $this->samba_tool . " group add '{$data->nome}' {$descricao}";   
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Grupo criado com sucesso!'); 
               $this->form->clear();
               $this->onReload(); // reload the listing          
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
     * Ask before deletion
     */
    public function onDelete($param)
    {
        $grupo = $param['grupo']; // get the parameter $key
    
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir o grupo: <b>{$grupo}</b> ?", $action);
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $grupo = $param['grupo']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . " group delete {$grupo}";        
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Grupo excluido com sucesso!'); 
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
