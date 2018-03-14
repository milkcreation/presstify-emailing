<?php
namespace tiFy\Plugins\Emailing\Admin\MailingList\ListTable;

use tiFy\Core\Templates\Admin\Model\ListTable\ListTable as tiFyCoreAdminModelListTable;

class ListTable extends tiFyCoreAdminModelListTable
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
		return  array(
			'deleted' 				=> array(
				'message'		=> __( 'La liste de diffusion a été défintivement supprimée', 'tify' ),
				'notice'		=> 'success'
			),
			'trashed' 				=> array(
				'message'		=> __( 'La liste de diffusion a été placée dans la corbeille', 'tify' ),
				'notice'		=> 'success'
			),
			'untrashed' 				=> array(
				'message'		=> __( 'La liste de diffusion a été rétablie', 'tify' ),
				'notice'		=> 'success'
			)
		);	
	}
	
	/** == Définition des vues filtrées == **/
	public function set_views()
	{
		$public 		= isset( $_REQUEST['public'] ) ? ( ! empty( $_REQUEST['public'] ) ? true : false ) : null;
		
		return 	array(
			'any'	=> array(
				'label'					=> __( 'Toutes (hors corbeille)', 'tify' ),
				'current'				=> ( $this->CurrentStatus === 'any' && is_null( $public ) ) ? true : null,
				'remove_query_args'		=> array( 'status', 'public' ),
				'count'					=> $this->count_items( array( 'status' => 'publish' ) ),
				'hide_empty'			=> true
			),				
			'public'		=> array(
				'label'				=> array( 'singular' => __( 'Publique', 'tify' ), 'plural' => __( 'Publiques', 'tify' ) ),
				'current'			=> ( ( $this->CurrentStatus === 'any' ) && ! is_null( $public ) && $public ) ? true : false,
				'add_query_args'	=> array( 'status' => 'publish', 'public' => 1 ),
				'count'				=> $this->count_items( array( 'status' => 'publish', 'public' => 1 ) ),
				'hide_empty'			=> true			
			),
			'private'		=> array(
				'label'				=> array( 'singular' => __( 'Privée', 'tify' ), 'plural' => __( 'Privées', 'tify' ) ),
				'current'			=> ( ( $this->CurrentStatus === 'any' ) && ! is_null( $public ) && ! $public ) ? true : false,
				'add_query_args'	=> array( 'status' => 'publish', 'public' => 0 ),
				'count'				=> $this->count_items( array( 'status' => 'publish', 'public' => 0 ) ),
				'hide_empty'			=> true				
			),
			'trash' 	=>  array(
				'label'				=> __( 'Corbeille', 'tify' ),
				'current'			=> ( $this->CurrentStatus === 'trash' ) ? true : false,
				'add_query_args'	=> array( 'status' => 'trash' ),
				'count'				=> $this->count_items(  array( 'status' => 'trash' ) ),
				'hide_empty'			=> true
			)
		);
	}
	
	/** == Définition des colonnes de la table == **/
	public function set_columns()
	{
		return array(
			'cb'       				=> '<input type="checkbox" />',
			'list_title' 			=> __( 'Intitulé', 'tify' ),
			'list_content'  		=> __( 'Description', 'tify' ),
			'subscribers_number'    => __( 'Nombre d\'abonnés', 'tify' ),
			'list_date' 			=> __( 'Date de création', 'tify' ),
			'list_public' 			=> __( 'Droit d\'accès', 'tify' )
		);
	}
	
	/** == Définition des arguments de requête == **/
	public function set_query_args()
	{
		return array(
			'status' => $this->CurrentStatus === 'any' ? array( 'publish' ) : $this->CurrentStatus
		);
	}
	
	/** == Définition des colonnes pouvant être ordonnées == **/
	public function set_sortable_columns()
	{
		return array(	
			'list_title'  => 'title'
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
		return $bulk_actions;
	}
	
	/** == Définition des actions sur un élément == **/
	public function set_row_actions()
	{
		$base_uri = add_query_arg( array( 'status' => $this->CurrentStatus ), $this->BaseUri );
		$base_uri = isset( $_REQUEST['public'] ) ? add_query_arg( array( 'public' => $_REQUEST['public'] ), $base_uri ) : $base_uri;
		
		return array( 
			'edit', 
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
				
	/* = TRAITEMENT = */
	/** == Éxecution de l'action - Suppression == **/
	public function process_bulk_action_delete()
	{
		$item_ids = $this->current_item();
		
		// Vérification des permissions d'accès
		if( ! wp_verify_nonce( @$_REQUEST['_wpnonce'], 'bulk-'. $this->Plural ) ) :
			check_admin_referer( $this->get_item_nonce_action( 'delete', reset( $item_ids ) ) );
		endif;
		
		// Traitement de l'élément
		foreach( (array) $item_ids as $item_id ) :	
			// Destruction des liaisons abonnés <> liste
			\tify_db_get( 'emailing_mailinglist_relationships' )->delete_list_subscribers( $item_id );
			// Suppression de la liste de diffusion
			$this->db()->handle()->delete_by_id( $item_id );
		endforeach;
			
		$sendback = remove_query_arg( array( 'action', 'action2' ), wp_get_referer() );
		$sendback = add_query_arg( 'message', 1, $sendback );

		wp_redirect( $sendback );
	}	
	
	/* = AFFICHAGE = */
	/** == Colonne - Titre == **/
	public function column_list_title( $item )
	{
		$title = ! $item->list_title ? __( '(Pas de titre)', 'tify' ) : $item->list_title;			
		$label = ( ! in_array( $item->list_status, array( 'publish', 'auto-draft' ) ) && ( $this->current_view() === 'any' ) ) ? "<span> - ". $this->views[$item->list_status]['label'] ."</span>" : false;
		
		// Actions sur l'élément				
		if( $item->list_status !== 'trash' )
			$row_actions = $this->row_actions( $this->item_row_actions( $item, array( 'edit', 'trash' ) ) );
		else
			$row_actions = $this->row_actions( $this->item_row_actions( $item, array( 'untrash', 'delete' ) ) );		
		
		return sprintf('<strong><a href="%2$s">%1$s</a> %3$s</strong>%4$s', $title, $this->get_edit_uri( $item->list_id ), $label, $row_actions ); 	
	}

	/** == Colonne - Nombre d'abonnés == **/
	public function column_subscribers_number( $item )
	{
		$DbSubscriber	= \tify_db_get( 'emailing_subscriber' );
		
		$registred 		= (int) $DbSubscriber->select()->count( array( 'list_id' => $item->list_id, 'status' => 'registred', 'active' => 1 ) );
		$unsubscribed 	= (int) $DbSubscriber->select()->count( array( 'list_id' => $item->list_id, 'status' => 'registred', 'active' => 0 ) );
		$waiting 		= (int) $DbSubscriber->select()->count( array( 'list_id' => $item->list_id, 'status' => 'registred', 'active' => -1 ) );
		$trashed 		= (int) $DbSubscriber->select()->count( array( 'list_id' => $item->list_id, 'status' => 'trash' ) );			
		$total 			= (int) $DbSubscriber->select()->count( array( 'list_id' => $item->list_id ) );
		
		$output = "<strong style=\"text-transform:uppercase\">". sprintf( _n( '%d abonné au total', '%d abonnés au total', ( $total <= 1 ), 'tify' ), $total ) ."</strong>";
		$output .= "<br><em style=\"color:#999; font-size:0.9em;\">". sprintf( _n( '%d inscrit', '%d inscrits', ( $registred <= 1 ), 'tify' ), $registred ) .", </em>";
		$output .= "<em style=\"color:#999; font-size:0.9em;\">". sprintf( _n( '%d désinscrit', '%d désinscrits', ( $unsubscribed <= 1 ), 'tify' ), $unsubscribed ) .", </em>";
		$output .= "<em style=\"color:#999; font-size:0.9em;\">". sprintf( _n( '%d en attente', '%d en attente', ( $waiting <= 1 ), 'tify' ), $waiting ) .", </em>";
		$output .= "<em style=\"color:#999; font-size:0.9em;\">". sprintf( _n( '%d à la corbeille', '%d à la corbeille', ( $trashed <= 1 ), 'tify' ), $trashed ) ."</em>";
		
		return $output;
	}
	
	/** == Colonne - Date de création de la liste == **/
	public function column_list_date( $item )
	{
		if( $item->list_date !== '0000-00-00 00:00:00' )
			return mysql2date( __( 'd/m/Y à H:i', 'tify' ), $item->list_date );
		else
			return __( 'Indéterminée', 'tify' );
	}
	
	/** == Colonne - Droit d'accès à la liste == **/
	public function column_list_public( $item )
	{
		return ( $item->list_public ) ? "<strong style=\"color:green;\">". __( 'Publique', 'tify' ) ."</strong>" : "<strong style=\"color:red;\">". __( 'Privée', 'tify' ) ."</strong>";
	}
}