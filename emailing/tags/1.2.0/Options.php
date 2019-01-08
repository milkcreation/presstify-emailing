<?php
namespace tiFy\Plugins\Emailing; 

// tyem_contact_infos
// tyem_subscribe_form

class Options
{
    /* = CONTRÔLEURS = */
    /** == Options par défaut == **/
    public static function getDefaults( $option = null ){
        $defaults = array(    
            'tyem_contact_infos'    => array( 
                'contact_name'          => '',
                'contact_email'         => get_option( 'admin_email' ),
                'reply_to'              => '',
                'company_name'          => get_bloginfo( 'name' ),
                'website'               => get_bloginfo( 'url' ),
                'address'               => '',
                'phone'                 => ''            
            ),
            'tyem_subscribe_form'   => array(
                'title'                 => __( 'Inscription à la newsletter', 'tify' ),
                'label'                 => __( 'Email', 'tify' ),
                'placeholder'           => __( 'Renseignez votre email', 'tify' ),
                'button'                => __( 'Inscription', 'tify' ),
                'list_id'               => 0,
                'success'               => __( 'Un email de validation vous a été adressé, cliquez sur le lien de confirmation pour valider votre inscription', 'tify' ),
                'from'                  => get_option( 'admin_email' ),
                'from_name'             => get_bloginfo( 'name' ) 
            ),
            'tyem_send'             => array(
                'hourly_quota'          => 50,
                'test_email'            => get_option( 'admin_email' ),
                'engine'                => 'wp',
                'smtp'                  => array(
                    'host'                  => 'localhost',
                    'port'                  => '587',
                    'username'              => '',
                    'password'              => '',
                    'auth'                  => 'on',
                    'secure'                => ''
                )
            )
        );
        
        if( ! $option )
            return $defaults;
        elseif( isset( $defaults[$option] ) )
            return $defaults[$option];
    }

    /** == Récupération des options == **/
    public static function Get( $option, $sub = null ){
        if( ! $defaults = self::getDefaults( $option ) )
            return;
        
        $option = wp_parse_args( get_option( $option, array() ), $defaults );
        if( ! $sub )
            return $option;
        elseif( isset( $option[$sub] ) )
            return $option[$sub];
    }    
}