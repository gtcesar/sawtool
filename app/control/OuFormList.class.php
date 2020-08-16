<?php

/**
 * OuFormList
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class OuFormList extends TPage
{
    private $datagrid;
    private $samba_tool;
    private $output;
    private $dominio;
    private $listagem;
    
    public function __construct()
    {
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        $this->dominio = $config['ldap']['dominio'];
        $this->output = $config['debug']['output'];

         // creates the form
        $this->form = new BootstrapFormBuilder('form_ou');
        $this->form->setFormTitle('Unidade Organizacional');
        
        // create the form fields

        $nome = new TEntry('nome');  
        $nome_label = new TLabel('Nome');  
  
        // add a row with 2 slots
        $this->form->addFields( [ $nome_label ], [ $nome ] );     
       
         
        $nome->addValidation('Nome', new TRequiredValidator); // required field

        $nome->setSize('100%');   

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        
        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->disableDefaultClick();
        $this->datagrid->width = '100%';
        
        // add the columns
        $this->datagrid->addColumn( new TDataGridColumn('unidade',    'Unidade',    'left',   '100%') );
        $this->datagrid->addColumn( new TDataGridColumn('hierarquia',    'Hierarquia',    'left',   '100%') );
        

        $action1 = new TDataGridAction([$this, 'onDelete'],   ['unidade' => '{unidade}', 'hierarquia' => '{hierarquia}'] );
        $this->datagrid->addAction($action1, _t('Delete'), 'far:trash-alt red');        
        
        $action2 = new TDataGridAction([$this, 'onMove'],   ['unidade' => '{unidade}', 'hierarquia' => '{hierarquia}'] );
        $this->datagrid->addAction($action2, 'Mover unidade', 'fa:retweet green');
        
        $action3 = new TDataGridAction([$this, 'onRename'],   ['unidade' => '{unidade}', 'hierarquia' => '{hierarquia}'] );
        $this->datagrid->addAction($action3, 'Renomear unidade', 'far:edit blue');

        
        // creates the datagrid model
        $this->datagrid->createModel();
        
        // search box
        $input_search = new TEntry('input_search');
        $input_search->placeholder = _t('Search');
        $input_search->setSize('100%');
        
        // enable fuse search by column grupo
        $this->datagrid->enableSearch($input_search, 'unidade');
        
        $panel = new TPanelGroup( 'Unidades Organizacionais' );
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

        $comando = "sudo " . $this->samba_tool . " ou list";        
        $result = shell_exec($comando);
        
        $ous = explode("\n", $result);        
        array_pop($ous);
        sort($ous);
        
        foreach($ous as $ou){
            
            $unidade = explode(",", $ou);
            $unidade = substr($unidade[0], 3);
            
            // add an regular object to the datagrid                               
            $item = new StdClass;
            $item->unidade = $unidade;
            $item->hierarquia = $ou;
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
            
      
            $comando = "sudo " . $this->samba_tool . " ou create ou='{$data->nome}'";  
            
              if($this->output == 'on')
              {
                 echo "<pre>".$comando."</pre>";
              }
             
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Unidade Organizacional criada com sucesso!'); 
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
      $unidade = $param['unidade']; // get the parameter $key
      $hierarquia = $param['hierarquia']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . "  ou listobjects '{$hierarquia}'";        
      $result = shell_exec($comando);

      $resposta = trim(substr($result, -6));          
      
      if($resposta == "empty")
      {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir a unidade: <b>{$unidade}</b> ?", $action);
      }

      else
      {
        new TMessage('warning', "Essa unidade possui objetos vinculados a ela!"); 
      }       

    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $hierarquia = $param['hierarquia']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . "  ou delete '{$hierarquia}'";   
      
      if($this->output == 'on')
      {
         echo "<pre>".$comando."</pre>";
      }
           
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Unidade excluida com sucesso!'); 
         $this->onReload(); // reload the listing          
      }    

    }
    
    public function onMove( $param )
    {

        $form = new BootstrapFormBuilder('move_form');
        
        $unidade = new TEntry('unidade');
        $unidade->setValue($param['unidade']);
        $unidade->setEditable(FALSE);
        
        $hierarquia = new TEntry('hierarquia');
        $hierarquia->setValue($param['hierarquia']);
        $hierarquia->setEditable(FALSE);

        $unidade_destino = new TCombo('unidade_destino');
        
        //listamos as unidades organizacionais
        $comando = "sudo " . $this->samba_tool . " ou list";         
        $result = shell_exec($comando);
        
        $ous = explode("\n", $result);        
        array_pop($ous);
        sort($ous);
        
        //retiramos a unidade selecionada de dentro do array
        $key = array_search($param['hierarquia'], $ous);
        if($key!==false)
        {
            unset($ous[$key]);
        }
        
        //montamos a a DC RAIZ
        $dc = explode(".", $this->dominio); 
        $dc1 = substr($dc[0], 1);
        $dc2 = $dc[1];
        $raiz = "DC={$dc1},DC={$dc2}";
        
        //criamos um array com os itens tratados ate o momento
        $array_ous = array();
        $array_ous[$raiz] = '--- Raiz do domínio ---';
        
        
        $nome_unidade = explode(",", $param['hierarquia']);
        
        //caso o objeto ja esteja na raiz, retiramos a opção RAIZ da lista
        if(count($nome_unidade) == 1)
        {
            unset($array_ous[$raiz]);
        }

        //populamos o array
        foreach ($ous as $key => $value) { 
            
            $item = explode(",", $value);    
            $qtd = count($item);
            $pai = end($item);       
            
            //exibimos apenas o nome da UNIDADE
            $nome_simplificado = substr($item[0], 3);
            $array_ous[$value] = $nome_simplificado; 
            
            //removemos da listagem as unidades que ja sao filhas da unidade selecionada, pois a mesma não pode se tornar filho de unidades ao qual é pai
            if($qtd > 1)
            {            
                $keyx = array_search($nome_unidade[0], $item);
                if($keyx!==false)
                {
                    unset($array_ous[$value]);
                }

            }    
            
            //removemos o pai ao qual ja pertencemos para evitar redundancia
            if(count($nome_unidade) > 1)
            {            
                $keyz = array_search($nome_unidade[1], $item);
                if($keyz!==false)
                {
                    unset($array_ous[$value]);
                }
            }    
          
        }
      
                
        $unidade_destino->addItems($array_ous);


        $unidade->setSize('100%'); 
        $hierarquia->setSize('100%'); 
        $unidade_destino->setSize('100%'); 
        
        $form->addFields( [new TLabel('Unidade')], [$unidade]);
        $form->addFields( [new TLabel('Hierarquia atual')], [$hierarquia]);
        $form->addFields( [new TLabel('Mover para')], [$unidade_destino]);
        
        $form->addAction('Salvar', new TAction([__CLASS__, 'moveUnidade']), 'fa:save green');
        
        // show the input dialog
        new TInputDialog('Mover unidade', $form);

    }

    public function moveUnidade( $param )
    {

            //tratamos os dados colocando aspas simpes ao redor do nome para permitir manipulação com espaços
            $hierarquia_array = explode(',', $param['hierarquia']);
            $h = array();
            foreach ($hierarquia_array as $ouh){
                 $h[] = "'" . $ouh . "'";
            }
            $hierarquia = implode(",", $h);
            
            $unidade_destino_array = explode(',', $param['unidade_destino']);
            $u = array();
            foreach ($unidade_destino_array as $ouu){
                 $u[] = "'" . $ouu . "'";
            }
            $unidade_destino = implode(",", $u);
      
            $comando = "sudo " . $this->samba_tool . " ou move {$hierarquia} {$unidade_destino}";        
            
            if($this->output == 'on')
            {
               echo "<pre>".$comando."</pre>";
            }
            
            $result = shell_exec($comando);            
            
            if ($result) {
               new TMessage('info', 'Unidade movida com sucesso!'); 
               $this->onReload(); // reload the listing          
            } 

    }
    
    public function onRename( $param )
    {
        
        $form = new BootstrapFormBuilder('rename_form');
        
        $nome_atual = new TEntry('nome_atual');
        $nome_atual->setValue($param['unidade']);
        $nome_atual->setEditable(FALSE);
        
        $nome_real = new THidden('nome_real');
        $nome_real->setValue($param['hierarquia']);
        $nome_real->setEditable(FALSE);
        
        $nome_renomeado = new TEntry('nome_renomeado');

        $nome_atual->setSize('100%'); 
        $nome_real->setSize('100%'); 
        $nome_renomeado->setSize('100%'); 
        
        $form->addFields( [new TLabel('Nome atual')], [$nome_atual]);        
        $form->addFields( [new TLabel('Renomear para')], [$nome_renomeado]);
        $form->addFields( [$nome_real]);
        
        $form->addAction('Salvar', new TAction([__CLASS__, 'renomearUnidade']), 'fa:save green');
        
        // show the input dialog
        new TInputDialog('Renomear unidade', $form);

    }
    
    public function renomearUnidade( $param )
    {
        if($param['nome_renomeado'] == '')
        {
            new TMessage('error', 'Renomear para não pode ser vazia!'); 
        }else{
            
            $nome_atual = $param['nome_real']; // get the parameter $key
            $nome_novo = "OU=".$param['nome_renomeado']; // get the parameter $key

            $nome_array = explode(',', $param['nome_real']);
            array_shift($nome_array);
            array_unshift($nome_array, $nome_novo);
            $nome = array();
            foreach ($nome_array as $ou){
                 $nome[] = "'" . $ou . "'";
            }
            $nome_renomeado = implode(",", $nome);            
      
            $comando = "sudo " . $this->samba_tool . " ou rename '{$nome_atual}' {$nome_renomeado}";   
            
            if($this->output == 'on')
            {
               echo "<pre>".$comando."</pre>";
            }
            
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Unidade renomeada com sucesso!'); 
               $this->onReload(); // reload the listing          
            }else{
               new TMessage('error', "Ocorreu um erro, tente novamente!");
            }
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
