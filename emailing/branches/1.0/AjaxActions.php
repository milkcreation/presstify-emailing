<?php
namespace tiFy\Plugins\Emailing;

class AjaxActions extends \tiFy\App\Factory
{
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
		/// Abonnés
		add_action( 'wp_ajax_tiFyPluginEmailingRecipientsSuggest', array( $this, 'RecipientsSuggest' ) );

		/// Campagnes
		add_action( 'wp_ajax_tiFyPluginEmailingCampaignTestMessageSend', array( $this, 'CampaignTestMessageSend' ) );
		add_action( 'wp_ajax_tiFyPluginEmailingCampaignPrepareLog', array( $this, 'CampaignPrepareLog' ) );
		add_action( 'wp_ajax_tiFyPluginEmailingCampaignPrepareRecipientsSubscriber', array( $this, 'CampaignPrepareRecipientsSubscriber' ) );
		add_action( 'wp_ajax_tiFyPluginEmailingCampaignPrepareRecipientsMailingList', array( $this, 'CampaignPrepareRecipientsMailingList' ) );
		add_action( 'wp_ajax_tiFyPluginEmailingCampaignPrepareStatusReady', array( $this, 'CampaignPrepareStatusReady' ) ); 
		
		/// Options
		add_action( 'wp_ajax_tiFyPluginEmailingOptionsTestMessageSend', array( $this, 'OptionsTestMessageSend' ) );
			
	}
	
	/* = ABONNÉS = */
	/** == Récupération de destinataires de recherche par autocomplétion == **/
	final public function RecipientsSuggest()
	{
		// Contrôleurs de base de données		
		$DbSubscriber 	= \tify_db_get( 'emailing_subscriber' );
		$DbList 		= \tify_db_get( 'emailing_mailinglist' );
		
		// Traitement des arguments de requête
		$defaults = array(
			'term'				=> '',
			'elements'			=> array( 'label', 'type', 'type_label', 'ico', 'numbers' ),			
			'extras'			=> array(),
			'types'				=> array( 'subscriber', 'list' ),
			'name'				=> 'campaign_recipients'			
		);
		extract( $defaults );				
		
		if( isset( $_POST['term'] ) )
			$term = $_POST['term'];
		if( ! empty( $_POST['elements'] ) && is_array( $_POST['elements'] ) )
			$elements = $_POST['elements'];
					
		// Valeur de retour par défaut
		$response = array();	
			
		// Recherche parmi les abonnés Wistify
		if( in_array( 'subscriber', $types ) ) :					
			if( $results = $DbSubscriber->select()->rows( array( 'status' => 'registred', 's' => $term ) ) ) :
				foreach ( (array) $results as $result ) :
					// Données requises
					$label 				= $result->subscriber_email;
					$value 				= $result->subscriber_id;
					
					// Données de rendu
					$type 				= 'wystify_subscriber';
					$type_label			= __( 'Abonné', 'tify' ); 
					$ico 				= '<i class="fa fa-user"></i><i class="badge wisti-logo"></i>';
					
					// Génération du rendu
					$render				= "<a href=\"#\">". $this->RecipientsSuggestItemRender( compact( $elements ) ) ."</a>";
					
					// Génération de la selection	
					$selected 			= 	"<li data-numbers=\"1\">\n". 
											"\t". $this->RecipientsSuggestItemRender( compact( $elements ) ) ."\n".
											"\t<a href=\"\" class=\"tify_button_remove remove\"></a>\n".
											"\t<input type=\"hidden\" name=\"{$name}[{$type}][]\" value=\"{$value}\">\n".										
											"</li>\n";
												
					// Valeur de retour
					$response[] = compact( 'label', 'value', 'render', 'selected' );
				endforeach;
			endif;
		endif;
		
		// Recherche parmi les listes de diffusion
		if( in_array( 'list', $types ) ) :		
			if( $results = $DbList->select()->rows( array( 's' => $term ) ) ) :
				foreach ( (array) $results as $result ) :
					// Données requises
					$label 				= $result->list_title;
					$value 				= $result->list_id;
					
					// Données de rendu					
					$type 				= 'wystify_mailing_list';
					$type_label			= __( 'Liste de diffusion', 'tify' );					
					$ico 				= "<i class=\"fa fa-group\"></i><i class=\"badge wisti-logo\"></i>";
					$numbers 			= $DbSubscriber->select()->count( array( 'list_id' => $result->list_id, 'status' => 'registred', 'active' => 1 ) );
					
					// Génération du rendu
					$render			 	= "<a href=\"#\">". $this->RecipientsSuggestItemRender( compact( $elements ) ) ."</a>";
					
					// Génération de la selection
					$selected		 	= "<li data-numbers=\"{$numbers}\">\n". 
										  "\t". $this->RecipientsSuggestItemRender( compact( $elements ) ) ."\n".
										  "\t<a href=\"\" class=\"tify_button_remove remove\"></a>\n".
										  "\t<input type=\"hidden\" name=\"{$name}[{$type}][]\" value=\"{$value}\">\n".
										  "\t</li>\n";
										  
					// Valeur de retour
					$response[] = compact( 'label', 'value', 'render', 'selected' );
				endforeach;
			endif;
		endif;
		
		wp_send_json( $response );
	}
	
	/** == Rendu d'un élément de l'autocomplétion des abonnés == **/
	private function RecipientsSuggestItemRender( $args = array() ){
		extract( $args );
		$output   = "";
		$output  .= "<span class=\"ico\">{$ico}</span>\n". 
					"<span class=\"label\">{$label}</span>\n".
					"<span class=\"type\">{$type_label}</span>\n";
		if( isset( $numbers ) )
			$output .= "<span class=\"numbers\">{$numbers}</span>\n";
		
		return $output;
	}

	/* = CAMPAGNES = */
	/** == Lancement de la préparation de la campagne == **/
	final public function CampaignPrepareLog()
	{
		$recipients = false;		
		$types 		= array();
		$count 		= array(); 
		$total 		= 0;
		
		// Contrôleurs de base de données
		$DbCampaign 	= \tify_db_get( 'emailing_campaign' );
		$DbSubscriber 	= \tify_db_get( 'emailing_subscriber' );
		$DbQueue 		= \tify_db_get( 'emailing_queue' );
		
		// Récupération des variables de requête
		$cid = (int) $_POST['campaign_id'];
		
		// Réattribution du status d'édition pour la campagne
		$DbCampaign->update_status( $cid, 'edit' );

		// Nettoyage des messages déja présent dans la file
		$DbQueue->reset_campaign( $cid );
		$DbCampaign->set_prepare_log( $cid );				
				
		// Compte le nombre d'emails à envoyer
		if( $recipients = $DbCampaign->select()->cell_by_id( $cid, 'recipients' ) ) :						
			/// Abonnés Wistify
			if( isset( $recipients['wystify_subscriber'] ) ) :
				$count['wystify_subscriber'] = 0;
				foreach( $recipients['wystify_subscriber'] as $sid ) :
					if( $DbSubscriber->select()->cell_by_id( $sid, 'status' ) === 'registred' ) :
						$count['wystify_subscriber']++; 
						$total++;
					endif;
				endforeach;		
				if( $count['wystify_subscriber'] ) :
					array_push( $types, 'wystify_subscriber' );	
				endif;
			endif;
			
			/// Listes de diffusion
			if( isset( $recipients['wystify_mailing_list'] ) ) :
				$count['wystify_mailing_list'] = 0;				
				foreach( $recipients['wystify_mailing_list'] as $lid ) :					
					if( ! $c = $DbSubscriber->select()->count( array( 'list_id' => $lid, 'status' => 'registred', 'active' => 1 ) ) )
						continue;
					if( ! isset( $count['wystify_mailing_list'] ) ) :
						$count['wystify_mailing_list'] = 0;			
					endif;
					$count['wystify_mailing_list'] += $c; 
					$total += $c;
				endforeach;
				if( $count['wystify_mailing_list'] ) :
					array_push( $types, 'wystify_mailing_list' );
				endif;
			endif;				
		endif;
		
		// Mise à jour des logs
		$DbCampaign->update_prepare_log( $cid, array( 'total' => $total ) );

		$response = compact( 'recipients', 'types', 'count', 'total' );
				
		wp_send_json( $response );
	}
		
	/** == Préparation des destinataires abonnés == **/
	final public function CampaignPrepareRecipientsSubscriber()
	{
		// Contrôleurs de base de données
		$DbCampaign 	= \tify_db_get( 'emailing_campaign' );
		$DbSubscriber 	= \tify_db_get( 'emailing_subscriber' );
		
		// Récupération des variables de requête
		$cid 	= (int) $_POST['campaign_id'];
		$sids 	= (array) $_POST['subscriber_ids'];
		
		// Récupération des emails de destination
		$emails = array();
		foreach( $sids as $sid ) :		
			$emails[] = $DbSubscriber->select()->cell_by_id( $sid, 'email' );
		endforeach;
		
		// Définition des variables
		$processed 		= count( $emails );
		$unprocessed	= 0;
		
		// Mise en file des messages
		$errors = array();
		foreach( (array) $emails as $email ) :
			$data = Message::Queue( $email, $cid );
			if( is_wp_error( $data ) ) :
				$errors[$data->get_error_code()][] = $data->get_error_message();
				$unprocessed++;
			endif;
		endforeach;

		// Calcul du nombre d'email mis en file
		$enqueue = ceil( $processed - $unprocessed );
		
		// Mise à jour des logs
		$DbCampaign->update_prepare_log( $cid, $errors, true );
		
		$response = compact( 'emails', 'processed', 'errors', 'enqueue' );
					
		wp_send_json( $response );
	}
		
	/** == Préparation des destinataires de liste de diffusion == **/
	final public function CampaignPrepareRecipientsMailingList()
	{
		// Contrôleurs de base de données
		$DbCampaign 	= \tify_db_get( 'emailing_campaign' );
		$DbSubscriber 	= \tify_db_get( 'emailing_subscriber' );
		
		// Récupération des variables de requête
		$cid 		= (int) $_POST['campaign_id'];
		$lid 		= (int) $_POST['list_id'];
		$per_page	= (int) $_POST['per_page'];
		$paged		= (int) $_POST['paged'];		
		
		// Récupération des emails de destination
		$emails = $DbSubscriber->select()->col( 'email', array( 'list_id' => $lid, 'status'=> 'registred', 'active' => 1, 'per_page' => $per_page, 'paged' => $paged ) );
		
		// Définition des variables
		$processed = count( $emails );
		$unprocessed	= 0;
		
		// Mise en file des messages
		$errors = array();
		foreach( (array) $emails as $email ) :
			$data = Message::Queue( $email, $cid );
			if( is_wp_error( $data ) ) :
				$errors[$data->get_error_code()][] = $data->get_error_message();
				$unprocessed++;
			endif;
		endforeach;
		
		// Calcul du nombre d'email mis en file
		$enqueue = ceil( $processed - $unprocessed );
		
		// Mise à jour des logs
		$DbCampaign->update_prepare_log( $cid, $errors, true );
		
		$response = compact( 'emails', 'processed', 'errors', 'enqueue' );
		
		wp_send_json( $response );
	}
	
	/*** === Préparation de la campagne - === ***/
	final public function CampaignPrepareStatusReady()
	{
		// Contrôleurs de base de données
		$DbCampaign 	= \tify_db_get( 'emailing_campaign' );
		$DbQueue 		= \tify_db_get( 'emailing_queue' );
		
		// Récupération des variables de requête
		$cid  			= (int) $_POST['campaign_id'];
		$enqueue		= (int) $_POST['enqueue'];
		
		// Mise à jour des logs
		$DbCampaign->update_prepare_log( $_REQUEST['campaign_id'], array( 'enqueue' => $enqueue ), true );
			
		if( $DbQueue->select()->has( 'campaign_id', $cid ) && $DbCampaign->update_status( $cid, 'ready' ) ) :
			wp_send_json_success();
		else :
			wp_send_json_error();
		endif;		
	}
	
	/*** === Envoi d'un message de test === ***/
	public static function CampaignTestMessageSend()
	{
		// Récupération des variables de requête
		$campaign_id 		= (int) $_POST['campaign_id'];
		$email				= ! empty ( $_POST['email'] ) ? $_POST['email'] : '';
		
		$message 	= Message::Prepare( $campaign_id, $email, array(), true );
		$response 	= Message::Send( $message );		

		is_wp_error( $response ) ? wp_send_json_error( $response->get_error_message() ) : wp_send_json_success( $response );
	}
	
	/** == OPTIONS == **/
	/*** === Envoi d'un message de test === ***/
	public function OptionsTestMessageSend()
	{
		// Récupération des variables de requête
		$email		= $_POST['email'];

		$response 	= Message::Send( array( 'to' => $email ) );		

		is_wp_error( $response ) ? wp_send_json_error( $response->get_error_message() ) : wp_send_json_success( $response );
	}
}

// BROUILLON : AUTOCOMPLETION DES UTILISATEURS NATIF DE WORDPRESS
// Recherche parmi les utilisateurs Wordpress
/*if( in_array( 'wordpress-user', $types ) ) :
	$user_query_args = array(
		'search'         => '*'. $_REQUEST['term'] .'*',
		'search_columns' => array( 'user_login', 'user_email', 'user_nicename' )
	);	
	$user_query = new WP_User_Query( $user_query_args ); 	
	if( $results = $user_query->get_results() ) :
		foreach ( (array) $results as $result ){
			$label				= $result->user_email;
			$type 				= 'wordpress_user';
			$type_label			= __( 'Utilisateur Wordpress', 'tify' ); 
			$value 				= $result->ID;
			$ico 				= '<i class="fa fa-user"></i><i class="badge dashicons dashicons-wordpress"></i>';
			$_render			= 	"<span class=\"ico\">{$ico}</span>\n". 
									"<span class=\"label\">{$label}</span>\n".
									"<span class=\"type\">{$type_label}</span>\n";
			$render['label'] 	= 	"<a href=\"\">". $_render ."</a>";	
			$render['value'] 	= 	"<li data-numbers=\"1\">\n". 
									"\t". $_render ."\n".
									"\t<a href=\"\" class=\"tify_button_remove remove\"></a>\n".
									"\t<input type=\"hidden\" name=\"{$_REQUEST['name']}[{$type}][]\" value=\"{$value}\">\n".
									"\t</li>\n";	
			$response[] = $render;
		}
	endif;
endif;
 * 
// Recherche parmi les roles Wordpress
if( in_array( 'wordpress-role', $types ) ) :
	$results = array();
	foreach( get_editable_roles() as $role => $value ) :
		if( preg_match( '/'. preg_quote( $_REQUEST['term'] ) .'/i', translate_user_role( $value['name'] ) ) ) :
		 	$results[$role] = translate_user_role( $value['name'] );	
		endif;
	endforeach;
			
	if( $results ) :
		foreach ( (array) $results as $role_id => $result ){
			
			$label				= $result;
			$type 				= 'wordpress_role';
			$type_label			= __( 'Groupe d\'utilisateurs Wordpress', 'tify' ); 
			$value 				= $role_id;
			$user_query 		= new WP_User_Query( array( 'role' => $role_id ) );
			$numbers			= $user_query->get_total();
			$ico 				= '<i class="fa fa-group"></i><i class="badge dashicons dashicons-wordpress"></i>';
			$_render			= 	"<span class=\"ico\">{$ico}</span>\n". 
									"<span class=\"label\">{$label}</span>\n".
									"<span class=\"type\">{$type_label}</span>\n".
									"<span class=\"numbers\">{$numbers}</span>\n";
			$render['label'] 	= 	"<a href=\"\">". $_render ."</a>";	
			$render['value'] 	= 	"<li data-numbers=\"{$numbers}\">\n". 
									"\t". $_render ."\n".
									"\t<a href=\"\" class=\"tify_button_remove remove\"></a>\n".
									"\t<input type=\"hidden\" name=\"{$_REQUEST['name']}[{$type}][]\" value=\"{$value}\">\n".
									"\t</li>\n";	
			$response[] = $render;
		}
	endif;
endif;*/