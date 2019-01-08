<?php
namespace tiFy\Plugins\Emailing\Forms;

use tiFy\Plugins\Emailing\Options;

class Forms extends \tiFy\App\Factory
{
    /* = ARGUMENTS = */
    // Liste des actions à déclencher
    protected $tFyAppActions                = array(
        'tify_form_register',
        'tify_form_register_addon'    
    ); 
        
    /* = DECLENCHEURS = */
    /** == Déclaration des formulaires == **/    
    final public function tify_form_register()
    {    
        // Formulaire d'inscription à la newsletter
        tify_form_register(
            'tiFyPluginEmailingFormSubscribe',
            array(
                'title'             =>  __( 'Formulaire d\'inscription à la newsletter', 'tify' ),
                'prefix'             => 'tiFyEmailingFormSubscribe',                
                'fields'             => array(
                    array(
                        'slug'              => 'email',
                        'label'             => Options::Get( 'tyem_subscribe_form', 'label' ),
                        'placeholder'       => Options::Get( 'tyem_subscribe_form', 'placeholder' ),
                        'type'              => 'input',
                        'value'             => is_user_logged_in() ? wp_get_current_user()->user_email : '',
                        'integrity_cb'      => 'is_email',
                        'required'          => true,
                        'addons'           => array(
                            'tiFyPluginEmailingFormSubscribe' => array(
                                'data'          => 'subscriber_email'
                            )
                        )
                    )
                ),
                'options'               => array( 
                    'success'               => array( 'message' => Options::Get( 'tyem_subscribe_form', 'success' ) )
                ),
                'buttons'               => array( 'submit' => array( 'label' => Options::Get( 'tyem_subscribe_form', 'button' ) ) ),
                'addons'               => array(
                    'tiFyPluginEmailingFormSubscribe'
                )
            )
        );        
    }
    
    /** == Déclaration des addons de formulaire == **/
    final public function tify_form_register_addon()
    {
        tify_form_register_addon( 'tiFyPluginEmailingFormSubscribe', 'tiFy\Plugins\Emailing\Forms\Addons\Subscribe' );
    }
}