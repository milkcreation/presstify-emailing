<?php
namespace tiFy\Plugins\Emailing\Forms\Addons;

use tiFy\Core\Forms\Addons\Factory;
use tiFy\Plugins\Emailing\Message;
use tiFy\Plugins\Emailing\Options;

class Subscribe extends Factory
{
    /* = ARGUMENTS = */
    // ID de la liste de diffusion d'enregistrement des nouveaux abonnés
    private $ListID         = 0;
    // ID du nouvel abonné
    private $SubID          = 0;
    
    /* = CONSTRUCTEUR = */                
    public function __construct()
    {        
        parent::__construct();
        
        // Définition des fonctions de callback
        $this->callbacks = array(
            'handle_check_fields'        => array( $this, 'cb_handle_check_fields' ),
            'handle_successfully'       => array( $this, 'cb_handle_successfully' )
        );
        
        $this->ListID = Options::Get( 'tyem_subscribe_form', 'list_id' );
    }
    
    /* = FONCTION DE RAPPELS TIFY_FORMS = */
    /** == == **/
    public function cb_handle_check_fields( &$errors, $fields )
    {
        $email = false; $list_id = 0; 
        foreach( $fields as $field ) :
            if( ! $data = $this->getFieldAttr( $field, 'data', false  ) )
                continue;
            switch( $data ) :
                case 'subscriber_email' :
                    $email = $field->getValue(true);
                    break;
                case 'mailing_list' :
                    $list_id = $field->getValue(true);
                    break;
            endswitch;
        endforeach;
                
        if( ! $email )
            return;
        if( ! $list_id )
          $list_id = $this->ListID; 

        // Contrôleurs de base de données
        $DbSubscriber = \tify_db_get( 'emailing_subscriber' );

        // Vérification si l'adresse email fournie correspond à un utilisateur habilité (qui n'est pas à la corbeille)
        if( $DbSubscriber->select()->count( array( 'email' => $email, 'status' => 'trash' ) ) ) :
            return $errors[] = __( 'Désolé vous n\'êtes pas autorisé à vous inscrire à la newsletter avec cette adresse email.', 'tify' );
        endif;
        // Vérification si l'adresse email fournie correspond à un utilisateur inscrit à la newsletter
        if( $DbSubscriber->select()->count( array( 'email' => $email, 'list_id' => $list_id, 'active' => -1 ) ) ) :
            return $errors[] = __( 'Une demande d\'inscription à la newsletter a déjà été enregistrée avec cette adresse email.', 'tify' );
        endif;
        // Vérification si l'adresse email fournie correspond à un utilisateur inscrit à la newsletter
        if( $DbSubscriber->select()->count( array( 'email' => $email, 'list_id' => $list_id, 'active' => 1 ) ) ) :
            return $errors[] = __( 'Cette adresse email est déjà inscrite à la newsletter.', 'tify' );
        endif;
    }

    /** == == **/
    public function cb_handle_successfully( $handle )
    {
        // Contrôleurs de base de données
        $DbSubscriber = \tify_db_get( 'emailing_subscriber' );
        
        $email = $this->form()->getField('email')->getValue();
        
        // Récupération de l'utilisateur exitant
        if( ! $s = $DbSubscriber->select()->row( array( 'email' => $email ) ) ) :
            $data = array( 
                'subscriber_uid'        => tify_generate_token( 32 ),
                'subscriber_email'        => $email,
                'subscriber_date'        => current_time( 'mysql' )
            );
            $this->SubID = (int) $DbSubscriber->handle()->record( $data );
        else :
            $this->SubID = (int) $s->subscriber_id;
        endif;
        
        \tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( (int) $this->SubID, (int) $this->ListID, -1 );

        // Contrôleurs de base de données
        $DbSubscriber     = \tify_db_get( 'emailing_subscriber' );
        $DbList         = \tify_db_get( 'emailing_mailinglist' );
        
        $suid         = $DbSubscriber->select()->cell_by_id( $this->SubID, 'uid' );
        $luid        = $DbList->select()->cell_by_id( $this->ListID, 'uid' );
        
        $validate_link         = add_query_arg( array( 'u' => $suid, 'l' => $luid ), site_url( '/wistify/subscribe_list' ) );
        $invalidate_link     = add_query_arg( array( 'u' => $suid, 'l' => $luid ), site_url( '/wistify/unsubscribe_list' ) );
        
        $message = "";    
        $message .=     "<table width=\"600\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
        $message .=         "<tbody>";
        $message .=             "<tr>";
        $message .=                 "<td style=\"width:600px;\">";
        $message .=                     "<p>";
        $message .=                         __( 'Nous avons bien reçu votre demande d\'inscription à la newsletter, cliquez sur le lien suivant pour valider celle-ci :', 'tify' );
        $message .=                     "</p>";
        $message .=                     "<p style=\"margin-bottom:30px;\">";
        $message .=                         "<a href=\"{$validate_link}\" style=\"font-size:13px;line-height:1.1;\">{$validate_link}</a>";
        $message .=                     "</p>";
        $message .=                 "</td>";
        $message .=             "</tr>";
        $message .=             "<tr>";
        $message .=                 "<td style=\"width:600px;\">";
        $message .=                     "<p>";
        $message .=                         __( 'Si toutefois vous n\'étiez pas à l\'origine de cette demande, cliquez sur le lien suivant pour l\'annuler :', 'tify' ) ."";
        $message .=                     "</p>";
        $message .=                     "<p style=\"margin-bottom:30px;\">";
        $message .=                         "<a href=\"{$invalidate_link}\" style=\"font-size:13px;line-height:1.1;\">{$invalidate_link}</a>";
        $message .=                     "</p>";
        $message .=                 "</td>";
        $message .=             "</tr>";    
        $message .=         "</tbody>";
        $message .=     "</table>";        

        Message::Send( 
            array( 
                'from'          => array( 
                    'name'          => ( $name = Options::Get( 'tyem_subscribe_form', 'from_name' ) ) ? $name : '', 
                    'email'         => ( $from = Options::Get( 'tyem_subscribe_form', 'from' ) ) ? $from : get_option( 'admin_email' ) 
                ),
                'to'            => $DbSubscriber->select()->cell_by_id( $this->SubID, 'email' ),
                'subject'       => __( get_bloginfo('blogname').' | Validez votre inscription à la newsletter', 'tify' ),
                'html'          => $message
            )
        );
    }
}