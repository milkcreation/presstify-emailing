<?php
/*
Plugin Name: Emailing
Plugin URI: http://presstify.com/plugins/premiums/Emailing
Description: Gestionnaire de campagne d'emailing
Version: 1.0.0
Author: Milkcreation
Author URI: http://milkcreation.fr
*/

namespace tiFy\Plugins\Emailing;

use tiFy\Environment\Plugin;

class Emailing extends Plugin
{
    /**
     * CONSTRUCTEUR
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Contrôleurs
        new AjaxActions;
        new Forms\Forms;
        new Template\Template;
        new Tasks;
        new Upgrade('tify_plugin_emailing_version', 'emailing_page_wistify_logs_Maintenance', 1);

        require_once(self::tFyAppDirname() . '/Helpers.php');
    }
}
