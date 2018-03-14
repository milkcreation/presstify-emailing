<?php
namespace tiFy\Plugins\Emailing\Admin\Campaign\ListTable;

class ListTable extends \tiFy\Core\Templates\Admin\Model\ListTable\ListTable
{
	/* = ARGUMENTS = */
	// Status courant
	private $CurrentStatus 	= 'any';
	
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
		$this->CurrentStatus = empty( $_REQUEST['status'] ) ? 'any' : $_REQUEST['status'];
	}
	
	/* = DECLARATION DES PARAMETRES = */
	/** == Définition des messages de notification == **/
	public function set_notices()
	{
		return array(
			'duplicated' 			=> array(
				'message'		=> __( 'La campagne a été dupliquée', 'tify' ),
				'notice'		=> 'success'
			),
			'cancelled'			=> array(
				'message'		=> __( 'L\'expédition de la campagne a été annulé', 'tify' ),
				'notice'		=> 'success'
			),
			'deleted' 				=> array(
				'message'		=> __( 'La campagne a été supprimée définitivement', 'tify' ),
				'notice'		=> 'success'
			),
			'trashed' 				=> array(
				'message'		=> __( 'La campagne a été placée dans la corbeille', 'tify' ),
				'notice'		=> 'success'
			),
			'untrashed' 				=> array(
				'message'		=> __( 'La campagne a été restaurée', 'tify' ),
				'notice'		=> 'success'
			)
		);
	}
	
	/** == Définition des status == **/
	public function set_statuses()
	{
		return array(
			'any'	=> __( 'Tous (hors corbeille)', 'tify' ),
			'edit'	=> array(
				'singular' 	=> __( 'Édition', 'tify' ),
				'plural'	=> __( 'Éditions', 'tify' ),
			),
			'draft'	=> array(
				'singular' 	=> __( 'Brouillon', 'tify' ),
				'plural'	=> __( 'Brouillons', 'tify' ),
			),
			'ready'	=> array(
				'singular' 	=> __( 'Prête à expédier', 'tify' ),
				'plural'	=> __( 'Prêtes à expédier', 'tify' ),
			),
			'send'	=> array(
				'singular' 	=> __( 'En voie d\'expédition', 'tify' ),
				'plural'	=> __( 'En voies d\'expédition', 'tify' ),
			),
			'forwarded'	=> array(
				'singular' 	=> __( 'Expédiée', 'tify' ),
				'plural'	=> __( 'Expédiées', 'tify' ),
			),
			'trash'		=>	__( 'Corbeille', 'tify' )
		);
	}
	
	/** == Définition des vues filtrées == **/
	public function set_views()
	{
		return array(
			'any'		=> array(
				'label'				=> __( 'Tous (hors corbeille)', 'tify' ),
				'current'			=> ( $this->CurrentStatus === 'any' ) ? true : null,
				'add_query_args'	=> array( 'status' => array( 'edit', 'draft', 'preparing', 'ready', 'send', 'forwarded' ) ),
				'remove_query_args'	=> array( 'status' ),
				'count'				=> $this->count_items( array( 'status' => array( 'edit', 'preparing', 'ready', 'send', 'forwarded' ) ) )
			),
			'edit'			=> array( 
				'label'				=> __( 'Édition', 'tify' ),
				'add_query_args'	=> array( 'status' => 'edit' ),
				'count'				=> $this->count_items( array( 'status' => 'edit' ) ),
				'hide_empty'		=> true
			),
			'draft'			=> array( 
				'label'				=> __( 'Brouillon', 'tify' ),
				'add_query_args'	=> array( 'status' => 'draft' ),
				'count'				=> $this->count_items( array( 'status' => 'draft' ) ),
				'hide_empty'		=> true
			),
			'ready'			=>  array( 
				'label'				=> array( 'singular' => __( 'Prête', 'tify' ), 'plural' => __( 'Prêtes', 'tify' ) ),
				'add_query_args'	=> array( 'status' => 'ready' ),
				'count'				=> $this->count_items( array( 'status' => 'ready' ) ),
				'hide_empty'		=> true
			),
			'send'			=>  array( 
				'label'				=> __( 'Boîte d\'envoi', 'tify' ),
				'add_query_args'	=> array( 'status' => 'send' ),
				'count'				=> $this->count_items( array( 'status' => 'send' ) ),
				'hide_empty'		=> true
			),
			'forwarded'		=>  array( 
				'label'				=> array( 'singular' => __( 'Distribuée', 'tify' ), 'plural' => __( 'Distribuées', 'tify' ) ),
				'add_query_args'	=> array( 'status' => 'forwarded' ),
				'count'				=> $this->count_items( array( 'status' => 'forwarded' ) ),
				'hide_empty'		=> true
			),
			'trash' 		=> array( 
				'label'				=> __( 'Corbeille', 'tify' ),
				'add_query_args'	=> array( 'status' => 'trash' ),
				'count'				=> $this->count_items( array( 'status' => 'trash' ) ),
				'hide_empty'		=> true
			)
		);
	}
	
	/** == Définition des colonnes de la table == **/
	public function set_columns()
	{
		return array(
			'cb'       			=> '<input type="checkbox" />',
			'title' 			=> __( 'Intitulé', 'tify' ),
			'description'  		=> __( 'Description', 'tify' ),
			'infos'  			=> __( 'Informations', 'tify' )
		);
	}
	
	/** == Définition des actions groupées == **/
	public function set_bulk_actions()
	{
		if( $this->CurrentStatus !== 'trash' ) :
			$bulk_actions['trash'] = __( 'Mettre à la corbeille', 'tify' );
		else :
			$bulk_actions['untrash'] = __( 'Restaurer', 'tify' );
			$bulk_actions['delete'] = __( 'Supprimer définitivement', 'tify' );
		endif;
		if( $this->CurrentStatus === 'send' ) :
			$bulk_actions['cancel'] = __( 'Annuler l\'envoi', 'tify' );
		endif;
		return $bulk_actions;
	}
	
	/** == Définition des actions sur un élément == **/
	public function set_row_actions()
	{
		$base_uri = add_query_arg( array( 'status' => $this->CurrentStatus ), $this->BaseUri );
		
		return array(
			'edit',
			'duplicate'			=> array(
				'base_uri'				=> $base_uri
			),
			'cancel' 			=> array(
				'label'					=> __( 'Annuler l\'envoi', 'tify' ),	
				'title'					=> __( 'Annulation de l\'envoi', 'tify' ),
				'link_attrs'			=> array( 'style' => 'color:orange;'),
				'nonce'					=> $this->get_item_nonce_action( 'cancel' ),
				'base_uri'				=> $base_uri
			),
			'trash'				=> array(
				'base_uri'				=> $base_uri
			),
			'untrash'			=> array(
				'base_uri'				=> $base_uri
			),
			'delete'			=> array(
				'base_uri'				=> $base_uri
			)
		);
	}
	
	/** == Définition de l'ajout automatique des actions sur l'élément des entrées de la colonne principale == **/
	public function set_handle_row_actions()
	{
		return false;
	}
					
	/* = DECLENCHEURS = */
	public function current_screen( $current_screen )
	{		
		// Mise en file des scripts	
		wp_enqueue_style( 'tiFyEmailingAdminCampaignListTable', self::tFyAppUrl() .'/ListTable.css', array( ), '151019' );
	}
	
	/* = TRAITEMENT = */
	/** == Éxecution de l'action - duplication == **/
	public function process_bulk_action_duplicate()
	{
		if( ! $item_ids = $this->current_item() )
			return;
		
			
		// Vérification des permissions d'accès
		$item_id = reset( $item_ids );
		check_admin_referer( $this->get_item_nonce_action( 'duplicate', $item_id ) );

		if( ! $c = $this->db()->select()->row_by_id( $item_id ) )
			return;
							
		$args = array(
			'campaign_uid'				=> tify_generate_token(),
			'campaign_title'			=> $c->campaign_title,
			'campaign_description'		=> $c->campaign_description,
			'campaign_author'			=> wp_get_current_user()->ID, 
			'campaign_date'				=> current_time('mysql', false ),
			'campaign_status'			=> 'edit',
			'campaign_step'				=> $c->campaign_step, 
			'campaign_template_name'	=> $c->campaign_template_name,
			'campaign_content_html'		=> $c->campaign_content_html,
			'campaign_content_txt'		=> $c->campaign_content_txt,
			'campaign_recipients'		=> $c->campaign_recipients,
			'campaign_message_options'	=> $c->campaign_message_options,
			'campaign_send_options'		=> $c->campaign_send_options,
			'campaign_send_datetime'	=> '0000-00-00 00:00:00'							
		);
 		
		$sendback = remove_query_arg( array( 'action', 'action2' ), wp_get_referer() );
		
		$this->db()->handle()->create( $args );
		$sendback = add_query_arg( 'message', 'duplicated', $sendback );				

		wp_redirect( $sendback );
	}
	
	/** == Éxecution de l'action - Annulation de l'expédition == **/
	public function process_bulk_action_cancel()
	{
		$item_ids = $this->current_item();
		
		// Vérification des permissions d'accès
		if( ! wp_verify_nonce( @$_REQUEST['_wpnonce'], 'bulk-'. $this->Plural ) ) :
			check_admin_referer( $this->get_item_nonce_action( 'cancel' ) );
		endif;
		
		// Traitement de l'élément
		foreach( (array) $item_ids as $item_id ) :		
			$this->db()->handle()->update( $item_id, array( 'status' => 'ready' ) );
		endforeach;
		
		$sendback = remove_query_arg( array( 'action', 'action2' ), wp_get_referer() );
		$sendback = add_query_arg( 'message', 'cancelled', $sendback );

		wp_redirect( $sendback );
	}
		
	/* = AFFICHAGE = */
	/** == COLONNE - Titre == **/
	public function column_title( $item )
	{
		$title 	= ! $item->campaign_title ? __( '(Pas de titre)', 'tify' ) : $item->campaign_title;				
		$status = ( ! in_array( $item->campaign_status, array( 'edit' ) ) && ( $this->CurrentStatus === 'any' ) ) ? "<span> - ". $this->get_status( $item->campaign_status, 'singular' ) ."</span>" : false;
		
		if( in_array( $item->campaign_status, array( 'edit', 'ready' ) ) ) : 
			$row_actions =  $this->row_actions( $this->item_row_actions( $item, array( 'edit', 'duplicate', 'trash' ) ) );
		elseif( $item->campaign_status === 'send' ) :
			$row_actions =  $this->row_actions( $this->item_row_actions( $item, array( 'duplicate', 'cancel' ) ) );
		elseif( $item->campaign_status === 'forwarded' ) :
			$row_actions =  $this->row_actions( $this->item_row_actions( $item, array( 'duplicate' ) ) );
		elseif( $item->campaign_status === 'trash' ) :
			$row_actions =  $this->row_actions( $this->item_row_actions( $item, array( 'untrash', 'delete' ) ) );
		else :
			$row_actions =  $this->row_actions( $this->item_row_actions( $item, array( 'edit', 'trash' ) ) );
		endif;		
		
		return sprintf('<strong><a href="%2$s">%1$s</a> %3$s</strong>%4$s', $title, $this->get_edit_uri( $item->campaign_id ), $status, $row_actions );    	
	}

	/** == COLONNE - Description == **/
	public function column_description( $item )
	{
		return nl2br( $item->campaign_description );
	}
	
	/** == COLONNE - Informations == **/
	public function column_infos( $item )
	{
		$output  = "";
		$output .= 	"<ul>";
		// Date de création
		$output .= 		"<li>".
							"<label><strong>". __( 'Créé', 'tify' ) ." : </strong></label>".
							sprintf( __( 'le %s à %s', 'tify' ), mysql2date( 'd/m/Y', $item->campaign_date ), mysql2date( 'H\hi', $item->campaign_date ) );
		if( $userdata = get_userdata( $item->campaign_author ) )
			$output .=		"<br>". sprintf( __( 'par %s', 'tify' ), $userdata->display_name );							
		$output .= 		"</li>";
		// Date de Modification
		if( $item->campaign_modified !== "0000-00-00 00:00:00" ) :
			$output .= 		"<li>".
								"<label><strong>". __( 'Modifié', 'tify' ) ." : </strong></label>".
								sprintf( __( 'le %s à %s', 'tify' ), mysql2date( 'd/m/Y', $item->campaign_modified ), mysql2date( 'H\hi', $item->campaign_modified ) );
			if( ( $last_editor = $this->db()->meta()->get( $item->campaign_id, '_edit_last' ) ) && ( $userdata = get_userdata( $last_editor ) ) )
				$output .=		"<br>". sprintf( __( 'par %s', 'tify' ), $userdata->display_name );							
			$output .= 		"</li>";
		endif;
		$output .= "</ul>";
		
		return $output;
	}
	
	/** == COLONNE - File d'attente d'expédition == **/
	public function column_campaign_queue( $item )
	{
		$output  = "";
		$output .= "<div>". sprintf( _n( 'Envoi effectué', 'Envois effectués', $queue_token['processed'], 'tify' ) ) ." <strong>". (int) $queue_token['processed'] ."</strong> ". __( 'sur', 'tify' )." {$queue_token['total']}</div>";
		$output .= "<div>". sprintf( __( 'dernier envoi %s', 'tify'), isset( $queue_token['last_datetime'] ) ? mysql2date( 'd/m/Y à H:i:s', $queue_token['last_datetime'] ) : '0000-00-00 00:00:00' ) ."</div>";
		
		return $output;
	}
	
	/** == Définition de l'attribut classe de la ligne relative à l'élément == **/
	public function set_row_classes( $item, $classes = '' )
	{
		//if( $this->db->check_lock( $item->campaign_id, 'edit' ) )
			//return $classes." wp-locked";		
	}
}