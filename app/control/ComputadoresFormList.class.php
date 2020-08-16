<?php

/**
 * ComputadoresFormList
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class ComputadoresFormList extends TPage
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
        $this->form = new BootstrapFormBuilder('form_computador');
        $this->form->setFormTitle('Computador');
        
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
        $this->datagrid->addColumn( new TDataGridColumn('computador',    'Computador',    'left',   '100%') );
        

        $action1 = new TDataGridAction([$this, 'onDelete'],   ['computador' => '{computador}'] );
        $this->datagrid->addAction($action1, _t('Delete'), 'far:trash-alt red');        
        
        $action2 = new TDataGridAction([$this, 'onMove'],   ['computador' => '{computador}'] );
        $this->datagrid->addAction($action2, 'Mover unidade', 'fa:retweet green');

        
        // creates the datagrid model
        $this->datagrid->createModel();
        
        // search box
        $input_search = new TEntry('input_search');
        $input_search->placeholder = _t('Search');
        $input_search->setSize('100%');
        
        // enable fuse search by column grupo
        $this->datagrid->enableSearch($input_search, 'computador');
        
        $panel = new TPanelGroup( 'Computadores' );
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

        $comando = "sudo " . $this->samba_tool . " computer list";        
        $result = shell_exec($comando);
        
        $ous = explode("\n", $result);        
        array_pop($ous);
        sort($ous);
        
        foreach($ous as $ou){
            
            $computador = substr($ou, 0, -1);
            
            // add an regular object to the datagrid                               
            $item = new StdClass;
            $item->computador = $computador;
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
            
      
            $comando = "sudo " . $this->samba_tool . " computer create '{$data->nome}'";  
            
              if($this->output == 'on')
              {
                 echo "<pre>".$comando."</pre>";
              }
             
            $result = shell_exec($comando);
            
            if ($result) {
               new TMessage('info', 'Computador criado com sucesso!'); 
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
      
        $computador = $param['computador'];
      
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion("Deseja excluir a unidade: <b>{$computador}</b> ?", $action);      

    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
    
      $computador = $param['computador']; // get the parameter $key
      
      $comando = "sudo " . $this->samba_tool . "  computer delete '{$computador}'";   
      
      if($this->output == 'on')
      {
         echo "<pre>".$comando."</pre>";
      }
           
      $result = shell_exec($comando);
      
      if ($result) {
         new TMessage('info', 'Computador excluido com sucesso!'); 
         $this->onReload(); // reload the listing          
      }    

    }
    
    public function onMove( $param )
    {

        $form = new BootstrapFormBuilder('move_form');
        
        $nome_computador = $param['computador'];
        
        $computador = new TEntry('computador');
        $computador->setValue($nome_computador);
        $computador->setEditable(FALSE);
        
        //obtemos a localização atual do computador
        $comando_show = "sudo " . $this->samba_tool . " computer show {$nome_computador}";         
        $result_show = shell_exec($comando_show);
        
        $local_show = explode("\n", $result_show);        
        array_pop($local_show);    
        
        //mostramos apenas o local onde o mesmo esta alocado
        $local_array = explode(",", $local_show[0]);
        array_shift($local_array);
        array_pop($local_array); 
        array_pop($local_array); 
        $local = implode(",", $local_array);        

        $alocado = new TEntry('alocado');
        $alocado->setValue($local);
        $alocado->setEditable(FALSE);
        
        $unidade_destino = new TCombo('unidade_destino');
        
        //listamos as unidades organizacionais
        $comando = "sudo " . $this->samba_tool . " ou list";         
        $result = shell_exec($comando);
        
        $ous = explode("\n", $result);        
        array_pop($ous);
        sort($ous);
        
        //montamos a a DC RAIZ
        $dc = explode(".", $this->dominio); 
        $dc1 = substr($dc[0], 1);
        $dc2 = $dc[1];
        $raiz = "CN=Computers,DC={$dc1},DC={$dc2}";
        
        //criamos um array com os itens tratados ate o momento
        $array_ous = array();
        $array_ous[$raiz] = '--- Alocação padrão ---';
        
        //caso o objeto ja esteja na raiz, retiramos a opção RAIZ da lista
        if($local == 'CN=Computers')
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
          
        }
      
                
        $unidade_destino->addItems($array_ous);


        $computador->setSize('100%'); 
        $alocado->setSize('100%');
        $unidade_destino->setSize('100%'); 
        
        $form->addFields( [new TLabel('Computador')], [$computador]);
        $form->addFields( [new TLabel('Alocado atualmente em')], [$alocado]);
        $form->addFields( [new TLabel('Mover para')], [$unidade_destino]);
        
        $form->addAction('Salvar', new TAction([__CLASS__, 'moveComputador']), 'fa:save green');
        
        // show the input dialog
        new TInputDialog('Mover computador', $form);

    }

    public function moveComputador( $param )
    {
            $computador = $param['computador'];

            //tratamos os dados colocando aspas simpes ao redor do nome para permitir manipulação com espaços            
            $unidade_destino_array = explode(',', $param['unidade_destino']);
            $u = array();
            foreach ($unidade_destino_array as $ou){
                 $u[] = "'" . $ou . "'";
            }
            $unidade_destino = implode(",", $u);
      
            $comando = "sudo " . $this->samba_tool . " computer move '{$computador}' {$unidade_destino}";        
            
            if($this->output == 'on')
            {
               echo "<pre>".$comando."</pre>";
            }
            
            $result = shell_exec($comando);            
            
            if ($result) {
               new TMessage('info', 'Computador movido com sucesso!'); 
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
