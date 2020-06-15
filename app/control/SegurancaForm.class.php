<?php

/**
 * SegurancaForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class SegurancaForm extends TPage
{
    
    private $form;
    private $samba_tool;
        
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {    
        parent::__construct();
        
        $config = parse_ini_file('param/config.ini', true);
        
        $this->samba_tool = $config['samba-tool']['path'];
        
        $comando = "sudo " . $this->samba_tool . " domain passwordsettings show";        
        $result = shell_exec($comando);
        
        $info = explode("\n", $result);     
        array_shift($info);   
        array_shift($info); 
        array_pop($info); 
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_config');
        $this->form->setFormTitle('Segurança');      
     
        $pass_complex = new TEntry('pass_complex');        
        $pass_complex_array = explode(":", $info[0]);        
        $pass_complex->setValue($pass_complex_array[1]);
        $pass_complex->setEditable(FALSE);
        
        $button_pass_complex = new TActionLink('', new TAction(['PasswordComplexityForm','onLoad'],['value' => $pass_complex_array[1]]), 'blue', null, null, 'far:edit');
        $button_pass_complex->class = 'btn btn-default inline-button';
        $button_pass_complex->title = 'Editar';
        $pass_complex->after($button_pass_complex);        
       
        $hist_length = new TEntry('hist_length');        
        $hist_length_array = explode(":", $info[2]);        
        $hist_length->setValue($hist_length_array[1]);
        $hist_length->setEditable(FALSE);
        
        $button_hist_length = new TActionLink('', new TAction(['PasswordHistoryLengthForm', 'onLoad'],['value' => $hist_length_array[1]]), 'blue', null, null, 'far:edit');
        $button_hist_length->class = 'btn btn-default inline-button';
        $button_hist_length->title = 'Editar';
        $hist_length->after($button_hist_length);
        
        $pass_length = new TEntry('pass_length');        
        $pass_length_array = explode(":", $info[3]);        
        $pass_length->setValue($pass_length_array[1]);
        $pass_length->setEditable(FALSE);
        
        $button_pass_length = new TActionLink('', new TAction(['MinimumPasswordLengthForm', 'onLoad'],['value' => $pass_length_array[1]]), 'blue', null, null, 'far:edit');
        $button_pass_length->class = 'btn btn-default inline-button';
        $button_pass_length->title = 'Editar';
        $pass_length->after($button_pass_length);
        
        $minimum_pass_age = new TEntry('minimum_pass_age');        
        $minimum_pass_age_array = explode(":", $info[4]);        
        $minimum_pass_age->setValue($minimum_pass_age_array[1]);
        $minimum_pass_age->setEditable(FALSE);
        
        $button_minimum_pass_age = new TActionLink('', new TAction(['MinimumPasswordAgeForm', 'onLoad'],['value' => $minimum_pass_age_array[1]]), 'blue', null, null, 'far:edit');
        $button_minimum_pass_age->class = 'btn btn-default inline-button';
        $button_minimum_pass_age->title = 'Editar';
        $minimum_pass_age->after($button_minimum_pass_age);
        
        $maximum_pass_age = new TEntry('maximum_pass_age');        
        $maximum_pass_age_array = explode(":", $info[5]);        
        $maximum_pass_age->setValue($maximum_pass_age_array[1]);
        $maximum_pass_age->setEditable(FALSE);
        
        $button_maximum_pass_age = new TActionLink('', new TAction(['MaximumPasswordAgeForm', 'onLoad'],['value' => $maximum_pass_age_array[1]]), 'blue', null, null, 'far:edit');
        $button_maximum_pass_age->class = 'btn btn-default inline-button';
        $button_maximum_pass_age->title = 'Editar';
        $maximum_pass_age->after($button_maximum_pass_age);
        
        $acconunt_lockout_duration = new TEntry('acconunt_lockout_duration');        
        $acconunt_lockout_duration_array = explode(":", $info[6]);        
        $acconunt_lockout_duration->setValue($acconunt_lockout_duration_array[1]);
        $acconunt_lockout_duration->setEditable(FALSE);
        
        $button_acconunt_lockout_duration = new TActionLink('', new TAction(['AccountLockoutDurationForm', 'onLoad'],['value' => $acconunt_lockout_duration_array[1]]), 'blue', null, null, 'far:edit');
        $button_acconunt_lockout_duration->class = 'btn btn-default inline-button';
        $button_acconunt_lockout_duration->title = 'Editar';
        $acconunt_lockout_duration->after($button_acconunt_lockout_duration);
        
        $acconunt_lockout_threshold = new TEntry('acconunt_lockout_threshold');        
        $acconunt_lockout_threshold_array = explode(":", $info[7]);        
        $acconunt_lockout_threshold->setValue($acconunt_lockout_threshold_array[1]);
        $acconunt_lockout_threshold->setEditable(FALSE);
        
        $button_acconunt_lockout_threshold = new TActionLink('', new TAction(['AccountLockoutThresholdForm', 'onLoad'],['value' => $acconunt_lockout_threshold_array[1]]), 'blue', null, null, 'far:edit');
        $button_acconunt_lockout_threshold->class = 'btn btn-default inline-button';
        $button_acconunt_lockout_threshold->title = 'Editar';
        $acconunt_lockout_threshold->after($button_acconunt_lockout_threshold);
        
        $reset_account_lockout = new TEntry('reset_account_lockout');        
        $reset_account_lockout_array = explode(":", $info[8]);        
        $reset_account_lockout->setValue($reset_account_lockout_array[1]);
        $reset_account_lockout->setEditable(FALSE);
        
        $button_reset_account_lockout = new TActionLink('', new TAction(['ResetAccountLockoutAfterForm', 'onLoad'],['value' => $reset_account_lockout_array[1]]), 'blue', null, null, 'far:edit');
        $button_reset_account_lockout->class = 'btn btn-default inline-button';
        $button_reset_account_lockout->title = 'Editar';
        $reset_account_lockout->after($button_reset_account_lockout);
        
        $row = $this->form->addFields( [new TLabel('Password complexity:')], [$pass_complex] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Password history length:')], [$hist_length] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Minimum password length:')], [$pass_length] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Minimum password age (days):')], [$minimum_pass_age] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Maximum password age (days):')], [$maximum_pass_age] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Account lockout duration (mins):')], [$acconunt_lockout_duration] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Account lockout threshold (attempts):')], [$acconunt_lockout_threshold] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
        $row = $this->form->addFields( [new TLabel('Reset account lockout after (mins):')], [$reset_account_lockout] );
        $row->layout = ['col-sm-6', 'col-sm-6' ];
       
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);

    }
    
    public function onLoad()
    {

    }
    
}
