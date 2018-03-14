<?php

namespace tiFy\Plugins\Emailing\Forms;

use tiFy\Plugins\Emailing\Options;

class Forms extends \tiFy\App\Factory
{
    /**
     * CONSTRUCTEUR
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Déclaration des événements
        $this->appAddAction('tify_form_register');
        $this->appAddAction('tify_form_register_addon');
    }

    /**
     * EVENEMENTS
     */
    /** == Déclaration des formulaires == **/
    final public function tify_form_register()
    {
        // Formulaire d'inscription à la newsletter
        tify_form_register(
            'tiFyPluginEmailingFormSubscribe',
            [
                'title'   => __('Formulaire d\'inscription à la newsletter', 'tify'),
                'prefix'  => 'tiFyEmailingFormSubscribe',
                'fields'  => [
                    [
                        'slug'         => 'email',
                        'label'        => Options::Get('tyem_subscribe_form', 'label'),
                        'placeholder'  => Options::Get('tyem_subscribe_form', 'placeholder'),
                        'type'         => 'input',
                        'value'        => is_user_logged_in() ? wp_get_current_user()->user_email : '',
                        'integrity_cb' => 'is_email',
                        'required'     => true,
                        'addons'       => [
                            'tiFyPluginEmailingFormSubscribe' => [
                                'data' => 'subscriber_email',
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'success' => ['message' => Options::Get('tyem_subscribe_form', 'success')],
                ],
                'buttons' => ['submit' => ['label' => Options::Get('tyem_subscribe_form', 'button')]],
                'addons'  => [
                    'tiFyPluginEmailingFormSubscribe',
                ],
            ]
        );
    }

    /** == Déclaration des addons de formulaire == **/
    final public function tify_form_register_addon()
    {
        tify_form_register_addon('tiFyPluginEmailingFormSubscribe', 'tiFy\Plugins\Emailing\Forms\Addons\Subscribe');
    }
}