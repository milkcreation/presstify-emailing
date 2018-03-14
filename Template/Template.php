<?php
namespace tiFy\Plugins\Emailing\Template;

use tiFy\Plugins\Emailing\Message;

class Template extends \tiFy\App\Factory
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
        $this->appAddAction('init');
        $this->appAddAction('template_redirect');
	}

    /**
     * EVENEMENTS
     */
	/** == Initialisation global == **/
	public function init()
	{
		// Déclaration de la variable de requête 
		add_rewrite_tag( '%wistify%', '([^&]+)' );
		// Déclaration de la régle de réécriture
		$rewrite_rules = get_option( 'rewrite_rules' );
		if( ! in_array( '^wistify/?', array_keys( $rewrite_rules ) ) ) :
			add_rewrite_rule( '^wistify/?', 'index.php?wistify=true', 'top' );
			flush_rewrite_rules( );
			wp_redirect( ( stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		endif;
	}
	
	/** == Affichage en ligne d'une campagne == **/ 
	public function template_redirect()
	{
		// Bypass
		if( ! get_query_var('wistify') )
			return;		
		if( ! preg_match( '/\/wistify\/(.*)\//', $_SERVER['REQUEST_URI'], $action ) )
			return;
		
		switch( $action[1] ) :
			case 'preview' :
				self::tpl_preview();
				break;
			case 'archive' :
				self::tpl_archive();
				break;
			case 'unsubscribe' :
				self::tpl_unsub();
				break;
			case 'subscribe_list' :
				self::tpl_subscribe_list();
				break;
			case 'unsubscribe_list' :
				self::tpl_unsubscribe_list();
				break;			
			default :
				self::tpl_404();
				break;
		endswitch;
	
		exit;
	}
	
	/* = CAMPAGNES = */
	/** == Récupération du titre d'une campagne == **/
	public static function CampaignTitle( $id )
	{
		return tify_db_get( 'emailing_campaign' )->select()->cell_by_id( $id, 'title' );
	}
	
	/** == Liste déroulante des campagnes == **/
	public static function CampaignDropdown( $args = array() )
	{
		$defaults = array(
			'show_option_all' 	=> '', 
			'show_option_none' 	=> '',
			'show_date' 		=> false, // ou date format
			
			'orderby' 			=> 'id', 
			'order' 			=> 'ASC',
			'status' 			=> array(),
			'include' 			=> '',
			'exclude' 			=> '', 
			
			'echo' 				=> 1,
			'selected' 			=> 0,
			'name' 				=> 'campaign_id', 
			'id' 				=> '',
			'class' 			=> 'tiFyPluginEmailingCampaignDropdown', 
			'tab_index' 		=> 0,
			'hide_if_empty' 	=> false, 
			'option_none_value' => -1
		);
	
		$r = wp_parse_args( $args, $defaults );
		$option_none_value = $r['option_none_value'];
	
		$tab_index = $r['tab_index'];
	
		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		
		// Requête de récupération
		$query_args = array();
		$query_args['orderby'] = $r['orderby'];
		$query_args['order'] = $r['order'];
		$query_args['status'] = ( empty( $r['status'] ) ) ? array( 'edit', 'preparing', 'ready', 'send', 'forwarded' ) : $r['status'];	
		if( $r['exclude'] )
			$query_args['exclude'] = $r['exclude'];
		if( $r['include'] )
			$query_args['item__in'] = $r['include'];
	
		$campaigns = \tify_db_get( 'emailing_campaign' )->select()->rows( $query_args );
		
		$name = esc_attr( $r['name'] );
		$class = esc_attr( $r['class'] );
		$id = $r['id'] ? esc_attr( $r['id'] ) : $name;
	
		if ( ! $r['hide_if_empty'] || ! empty( $campaigns ) )
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute>\n";
		else
			$output = '';
		
		if ( empty( $campaigns ) && ! $r['hide_if_empty'] && ! empty( $r['show_option_none'] ) ) 
			$output .= "\t<option value='" . esc_attr( $option_none_value ) . "' selected='selected'>{$r['show_option_none']}</option>\n";
	
	
		if ( ! empty( $campaigns ) ) :
			if ( $r['show_option_all'] ) 
				$output .= "\t<option value='0' ". ( ( '0' === strval( $r['selected'] ) ) ? " selected='selected'" : '' ) .">{$r['show_option_all']}</option>\n";
	
			if ( $r['show_option_none'] )
				$output .= "\t<option value='" . esc_attr( $option_none_value ) . "' ". selected( $option_none_value, $r['selected'], false ) .">{$r['show_option_none']}</option>\n";
			$walker = new Walkers\CampaignDropdown;
			$output .= call_user_func_array( array( &$walker, 'walk' ), array( $campaigns, -1, $r ) );
		endif;
	
		if ( ! $r['hide_if_empty'] || ! empty( $campaigns ) )
			$output .= "</select>\n";
	
		if ( $r['echo'] )
			echo $output;
	
		return $output;
	}
	
	/* = LISTES DE DIFFUSION = */
	/** == Liste déroulante des listes de diffusion == **/
	public static function MailingListDropdown( $args = array() )
	{	
		$defaults = array(
			'show_option_all' 	=> '', 
			'show_option_none' 	=> '',
			'show_count'        => false,
			
			'orderby' 			=> 'id', 
			'order' 			=> 'ASC',
			'status'			=> array(),
			'include' 			=> '',
			'exclude' 			=> '', 
			
			'echo' 				=> 1,
			'selected' 			=> 0,
			'name' 				=> 'list_id', 
			'id' 				=> '',
			'class' 			=> 'tiFyPluginEmailingMailingListDropdown', 
			'tab_index' 		=> 0,
			'hide_if_empty' 	=> false, 
			'option_none_value' => -1
		);
	
		$r = wp_parse_args( $args, $defaults );
		$option_none_value = $r['option_none_value'];
	
		$tab_index = $r['tab_index'];
	
		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		
		// Requête de récupération
		$query_args = array();
		$query_args['orderby'] = $r['orderby'];
		$query_args['order'] = $r['order'];
		$query_args['status'] = ( empty( $r['status'] ) ) ? 'publish' : $r['status'];	
		if( $r['exclude'] )
			$query_args['exclude'] = $r['exclude'];
		if( $r['include'] )
			$query_args['item__in'] = $r['include'];
		
		$mailing_lists = \tify_db_get( 'emailing_mailinglist' )->select()->rows( $query_args );

		$name = esc_attr( $r['name'] );
		$class = esc_attr( $r['class'] );
		$id = $r['id'] ? esc_attr( $r['id'] ) : $name;
	
		if ( ! $r['hide_if_empty'] || ! empty( $mailing_lists ) )
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute autocomplete=\"off\">\n";
		else
			$output = '';
		
		if ( empty( $mailing_lists ) && ! $r['hide_if_empty'] && ! empty( $r['show_option_none'] ) ) 
			$output .= "\t<option value='" . esc_attr( $option_none_value ) . "' selected='selected'>{$r['show_option_none']}</option>\n";
	
		if ( ! empty( $mailing_lists ) ) :
			if ( $r['show_option_all'] ) 
				$output .= "\t<option value='-1' ". ( ( '-1' === strval( $r['selected'] ) ) ? " selected='selected'" : '' ) .">{$r['show_option_all']}</option>\n";
	
			if ( $r['show_option_none'] )
				$output .= "\t<option value='" . esc_attr( $option_none_value ) . "' ". selected( $option_none_value, $r['selected'], false ) .">{$r['show_option_none']}</option>\n";
			$walker = new Walkers\MailingListDropdown;
			$output .= call_user_func_array( array( &$walker, 'walk' ), array( $mailing_lists, -1, $r ) );
		endif;
	
		if ( ! $r['hide_if_empty'] || ! empty( $mailing_lists ) )
			$output .= "</select>\n";
	
		if ( $r['echo'] )
			echo $output;
	
		return $output;
	}
	
		
	/* = TEMPLATES = */
	/** == 404 == **/
	public static function tpl_404()
	{
		echo 'Wistify 404';	
	}
	
	/** == Prévisualisation de la campagne == **/
	public static function tpl_preview()
	{
		if( empty( $_REQUEST['c'] ) )
			return self::tpl_404();
		// Récupération de la campagne	
		if( ! $c = tify_db_get( 'emailing_campaign' )->select()->row_by( 'uid', $_REQUEST['c'] ) )
			return self::tpl_404();
		
		return Message::getHtmlContent( $c->campaign_id, false, false );
	}
	
	/** == Affichage de la campagne en ligne == **/
	public static function tpl_archive()
	{
		if( empty( $_REQUEST['c'] ) )
			return self::tpl_404();
		if( empty( $_REQUEST['u'] ) )
			return self::tpl_404();

		// Récupération de la campagne	
		if( ! $c = \tify_db_get( 'emailing_campaign' )->select()->row_by( 'uid', $_REQUEST['c'] ) )
			return self::tpl_404();
		
		// Récupération de l'abonné
		if( ! $u = \tify_db_get( 'emailing_subscriber' )->select()->row_by( 'uid', $_REQUEST['u'] ) )
			$u = get_transient( 'tyem_'. $_REQUEST['u'] );

		if( ! $u ) return self::tpl_404();
		
		// Affichage de la campagne
		echo "<div style=\"width:600px;margin:30px auto;\">". Message::getHtmlOutput( $c->campaign_id, false, false ) ."</div>";		
	}

	/** == Affichage du formulaire de désinscription == **/
	public static function tpl_unsub()
	{
		if( empty( $_REQUEST['c'] ) )
			return self::tpl_404();
		if( empty( $_REQUEST['u'] ) )
			return self::tpl_404();
		
		// Récupération de la campagne	
		if( ! $c = \tify_db_get( 'emailing_campaign' )->select()->row_by( 'uid', $_REQUEST['c'] ) )
			return self::tpl_404();
		
		// Récupération de l'abonné
		if( $u = \tify_db_get( 'emailing_subscriber' )->select()->row_by( 'uid', $_REQUEST['u'] ) ) :
			if( $list_ids = \tify_db_get( 'emailing_mailinglist' )->get_subscriber_list_ids( $u->subscriber_id ) ) :
				foreach( $list_ids as $list_id ) :
					\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( (int) $u->subscriber_id, $list_id, 0 );
				endforeach;
				if( \tify_db_get( 'emailing_mailinglist_relationships' )->is_orphan( $u->subscriber_id ) )
					\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( (int) $u->subscriber_id, 0, 1 );
			endif;
		else :
			$u = get_transient( 'tyem_'. $_REQUEST['u'] );
		endif;
		if( ! $u ) return self::tpl_404();
		
		// Affichage de la confirmation de désinscription
		_e( 'Vous êtes désormais désinscrit.', 'tify' );		
	}
	
	/** == Affichage du formulaire de désinscription == **/
	public static function tpl_subscribe_list()
	{
		if( empty( $_REQUEST['u'] ) )
			return self::tpl_404();
			
		// Récupération de l'abonné
		if( $u = \tify_db_get( 'emailing_subscriber' )->select()->row_by( 'uid', $_REQUEST['u'] ) ) :
			$list_uid = isset( $_REQUEST['l'] ) ? $_REQUEST['l'] : 0;				
			if(  $list_uid ) :
				$l = \tify_db_get( 'emailing_mailinglist' )->select()->row_by( 'uid', $list_uid );
				\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( $u->subscriber_id, $l->list_id, 1 );
			else :
				\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( $u->subscriber_id, 0, 1 );
			endif;
		else :
			$u = get_transient( 'tyem_'. $_REQUEST['u'] );
		endif;	

		if( ! $u ) return self::tpl_404();
		
		// Affichage de la confirmation de d'inscription
		_e( 'Félicitations, vous êtes désormais inscrit à la newsletter.', 'tify' );		
	}
	
	/** == Affichage du formulaire de désinscription == **/
	public static function tpl_unsubscribe_list()
	{
		if( empty( $_REQUEST['u'] ) )
			return self::tpl_404();
		
		// Récupération de l'abonné
		if( $u = \tify_db_get( 'emailing_subscriber' )->select()->row_by( 'uid', $_REQUEST['u'] ) ) :
			$list_uid = isset( $_REQUEST['l'] ) ? $_REQUEST['l'] : 0;
			if(  $list_uid ) :
				$l = \tify_db_get( 'emailing_mailinglist' )->select()->row_by( 'uid', $list_uid );
				\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( $u->subscriber_id, $l->list_id, 0 );
			else :
				\tify_db_get( 'emailing_mailinglist_relationships' )->insert_subscriber_for_list( $u->subscriber_id, 0, 0 );
			endif;
		else :
			$u = get_transient( 'tyem_'. $_REQUEST['u'] );
		endif;

		if( ! $u ) return self::tpl_404();
		
		// Affichage de la confirmation de d'inscription
		_e( 'Votre demande de désinscription à la newsletter a été prise en compte.', 'tify' );		
	}	
	
	/** == == **/
	public static function tpl_subscribe_form( $echo = true )
	{
		$output = "";
		if( $title = tify_emailing_get_option( 'tyem_subscribe_form', 'title' ) )
			$output .= "<h3>{$title}</h3>";
		$output .= tify_form_display( 'tiFyEmailingFormSubscribe', false );
		
		if( $echo )
			echo $output;
		else
			return $output;		
	}
}