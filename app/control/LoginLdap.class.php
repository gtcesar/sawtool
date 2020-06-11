<?php

/**
 * LoginLdap
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Augusto Cesar da Costa Marques
 */
class LoginLdap extends TPage
{
    protected $form; // form

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    public function __construct($param)
    {
        parent::__construct();

        // create the form
        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle('Acesso ao sistema SAWTOOL');
        $this->form->generateAria(); // automatic aria-label

        // create the form fields
        $password = new TPassword('password');
        $password->setSize('80%');
        $password->addValidation('Password', new TRequiredValidator);

        // add the fields inside the form
        $this->form->addFields([new TLabel('Senha')], [$password]);

        // define the form action
        $this->form->addAction('Ok', new TAction(array($this, 'onLogin')), 'far:check-circle green');

        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        parent::add($vbox);

    }

    /**
     * Valida LDAP
     */
    public function onLdap($srv, $usr, $pwd)
    {
        $ldap_server = $srv;
        $auth_user = $usr;
        $auth_pass = $pwd;

        // Tenta se conectar com o servidor
        if (!($connect = @ldap_connect($ldap_server))) {
            return false;
        }

        // Tenta autenticar no servidor
        if (!($bind = @ldap_bind($connect, $auth_user, $auth_pass))) {
            // se não validar retorna false
            return false;
        } else {
            // se validar retorna true
            return true;
        }

    }

    /**
     * Authenticate the User
     */
    public function onLogin($param)
    {
        try
        {
            $config = parse_ini_file('param/config.ini', true);

            $data = $this->form->getData();

            $this->form->validate();

            $conn = $this->onLdap($config['ldap']['server'], $config['ldap']['user'] . $config['ldap']['dominio'], $data->password);

            if ($conn) {
                TSession::setValue('logged', true);
                AdiantiCoreApplication::gotoPage('DashboardView'); // reload          
            } else {
                new TMessage('error', 'Senha inválida');
            }

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }

    }

    /**
     * Logout
     */
    public static function onLogout()
    {
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginLdap', '');
    }
}
