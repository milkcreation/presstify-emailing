<?php
/**
 * Usage :
 * 
 	add_filter( 'tify_mandrill_api_key', '{function_hook_name}' );
	function function_hook_name( $key ){
		return ''; // Clé D'API Mandrill (requis)
	}
 */
/**
 * @see https://github.com/darrenscerri/Mindrill
 * @see https://github.com/kai5263499/mandrill-php
 */ 

// Récupération de la librairie Mandrill		
require_once dirname( __FILE__ ) .'/mandrill-api-php/Mandrill.php';	
 
class tiFy_Mandrill extends Mandrill{	
	/* = CONSTRUCTEUR = */
	public function __construct(){		
		// Instanciation de la classe parente
		if( ( $apikey = func_get_arg(0) ) && is_string( $apikey ) )
			parent::__construct( $apikey );

		/// Forcer l'IPV4
		curl_setopt( $this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			
		// Actions et Filtres Wordpress
		add_action( 'wp_ajax_tify_mandrill', array( $this, 'wp_ajax_action' ) );
	}
	
	/* = ACTIONS ET FILTRES WORDPRESS = */
	/** == Lancement de methode de l'API via Ajax == **/
	public function wp_ajax_action(){
		if( ! $this->apikey )
			wp_die( 0 );
		
		$result = $this->result( $_REQUEST['api'], $_REQUEST['method'], $_REQUEST['args'] );
		
		is_wp_error( $result ) ? wp_send_json_error( $result ) : wp_send_json_success( $result );
	}
	
	/* = CONTROLEUR = */
	/** == Récupération du resultat d'une requête de l'API == **/
	public function result( $api, $method, $args ){
		try{
			$result = call_user_func_array( array( $this->{$api}, $method ), $args );
		} catch ( Mandrill_Error $e ){
			$result = new WP_Error( get_class($e), $e->getMessage() );
		}
		
		return $result;
	}
}