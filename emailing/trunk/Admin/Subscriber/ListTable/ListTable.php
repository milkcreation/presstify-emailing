<?php
namespace tiFy\Plugins\Emailing\Admin\Subscriber\ListTable;

class ListTable extends \tiFy\Core\Templates\Admin\Model\ListTable\ListTable
{
	/* = ARGUMENTS = */
	// Status courant
	private $CurrentStatus 	= 'any';
	// Liste de diffuction courante
	private $CurrentListID 	= -1;
	
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
		$this->CurrentStatus 	= ! empty( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'any';
		$this->CurrentListID	= ! empty( $_REQUEST['list_id'] ) ? $_REQUEST['list_id'] : -1;
	}
	
	/* = DECLARATION DES PARAMETRES = */
	/** == Définition des messages de notification == **/
	public function set_notices()
	{
		return array(
			'deleted' 				=> array(
				'message'		=> __( 'L\'abonné a été supprimé définitivement', 'tify' ),
				'notice'		=> 'success'
			),
			'trashed' 				=> array(
				'message'		=> __( 'L\'abonné a été placé dans la corbeille', 'tify' ),
				'notice'		=> 'success'
			),
			'untrashed' 				=> array(
				'message'		=> __( 'L\'abonné a été restauré', 'tify' ),
				'notice'		=> 'success'
			)
		);
	}		
	
	/** == Définition des vues filtrées == **/
	public function set_views()
	{
		$active = isset( $_REQUEST['active'] ) ? ( ! empty( $_REQUEST['active'] ) ? (int) $_REQUEST['active'] : 0 ) : null;
		
		return array(
			'any'		=> array(
				'label'					=> __( 'Tous (hors corbeille)', 'tify' ),
				'current'				=> ( $this->CurrentStatus === 'any' ) ? true : null,
				'remove_query_args'		=> array( 'status' ),
				'add_query_args'		=> array( 'list_id' => $this->CurrentListID ),
				'count'					=> $this->count_items( array( 'status' => array( 'registred', 'waiting', 'unsubscribed' ), 'list_id' => $this->CurrentListID ) ),
				'hide_empty'			=> true
			),
			'registred'		=> array(
				'label'					=> __( 'Inscrits', 'tify' ),
				'current'				=> ( ( $this->CurrentStatus === 'registred' ) && ! is_null( $active ) && ( $active === 1 ) ) ? true : false,
				'add_query_args'		=> array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => 1 ),
				'count'					=> $this->count_items( array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => 1 ) ),
				'hide_empty'			=> true				
			),			
			'unsubscribed'	=> array(
				'label'					=> __( 'Désinscrits', 'tify' ),
				'current'				=> ( ( $this->CurrentStatus === 'registred' ) && ! is_null( $active ) && ( $active === 0 ) ) ? true : false,
				'add_query_args'		=> array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => 0 ),
				'count'					=> $this->count_items( array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => 0 ) ),
				'hide_empty'			=> true
			),
			'waiting'		=> array(
				'label'					=> __( 'En attente', 'tify' ),
				'current'				=> ( ( $this->CurrentStatus === 'registred' ) && ! is_null( $active ) && ( $active === -1 ) ) ? true : false,
				'add_query_args'		=> array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => -1 ),
				'count'					=> $this->count_items( array( 'status' => 'registred', 'list_id' => $this->CurrentListID, 'active' => -1 ) ),
				'hide_empty'			=> true
			),
			'trash' => array(
				'label'					=> __( 'Corbeille', 'tify' ),
				'current'				=> ( $this->CurrentStatus === 'trash' ) ? true : false,			
				'add_query_args'		=> array( 'status' => 'trash' ),
				'remove_query_args'		=> array( 'active' ),
				'count'					=> $this->count_items( array( 'status' => 'trash' ) ),
				'hide_empty'			=> true
			)
		);
	}
	
	/** == Définition des colonnes de la table == **/
	public function set_columns()
	{
		return array(
			'cb'       					=> '<input type="checkbox" />',
			'subscriber_email' 			=> __( 'Email', 'tify' ),
			'subscriber_lists' 			=> __( 'Listes de diffusion', 'tify' ),			
			'subscriber_date' 			=> __( 'Depuis le', 'tify' )
		);
	}
	
	/** == Définition des colonnes pouvant être ordonnées == **/
	public function set_sortable_columns()
	{
		return array(	
			'subscriber_email'  => 'email',
			'subscriber_date'	=> array( 'date', true )
		);
	}
	
	/** == Définition des arguments de requête == **/
	public function set_query_args()
	{
		return array(
			'status' 	=> $this->CurrentStatus === 'any' ? array( 'registred', 'waiting', 'unsubscribed' ) : $this->CurrentStatus,
			'list_id'	=> $this->CurrentListID,
			'active'	=> isset( $_REQUEST['active'] ) ? $_REQUEST['active'] : null
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
		$base_uri = add_query_arg( array( 'status' => $this->CurrentStatus, 'list_id' => $this->CurrentListID ), $this->BaseUri );
		$base_uri = isset( $_REQUEST['active'] ) ? add_query_arg( array( 'active' => $_REQUEST['active'] ), $base_uri ) : $base_uri;
		
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
			\tify_db_get( 'emailing_mailinglist_relationships' )->delete_subscriber_lists( $item_id );
			// Suppression de la liste de diffusion
			$this->db()->handle()->delete_by_id( $item_id );
		endforeach;
			
		$sendback = remove_query_arg( array( 'action', 'action2' ), wp_get_referer() );
		$sendback = add_query_arg( 'message', 1, $sendback );

		wp_redirect( $sendback );
	}	
			
	/* = AFFICHAGE = */
	/** == Filtrage avancé  == **/
	public function extra_tablenav( $which ) 
	{
	?>
		<div class="alignleft actions">
		<?php if ( 'top' == $which ) : ?>
			<label class="screen-reader-text" for="list_id"><?php _e( 'Filtre par liste de diffusion', 'tify' ); ?></label>
			<?php 
				\tify_emailing_mailinglist_dropdown( 
					array(
						'show_option_all'	=> __( 'Toutes les listes de diffusion', 'tify' ),
						'show_count'		=> true,
						'name'				=> 'list_id',
						'selected' 			=> $this->CurrentListID,

						'orderby'			=> 'title',
						'order'				=> 'ASC'
					)
				); 
				submit_button( __( 'Filtrer', 'tify' ), 'button', 'filter_action', false, array( 'id' => 'mailing_list-query-submit' ) );?>
		<?php endif;?>
		</div>
	<?php
	}
	
	/** == Contenu personnalisé : Titre == **/
	public function column_subscriber_email( $item )
	{
		$title = ! $item->subscriber_email ? __( '(Pas d\'email)', 'tify' ) : $item->subscriber_email;			
		$label = ( \tify_db_get( 'emailing_mailinglist_relationships' )->is_orphan( $item->subscriber_id ) ) ? "<span> - ". __( 'Orphelin', 'tify' ) ."</span>" : false;
		
		// Définition des actions sur l'élément
		if( $item->subscriber_status !== 'trash' ) :
			$row_actions = $this->row_actions( $this->item_row_actions(  $item, array( 'edit', 'trash' ) ) );
		else :
			$row_actions = $this->row_actions( $this->item_row_actions(  $item, array( 'untrash', 'delete' ) ) );
		endif;
		
		return sprintf('<strong><a href="%2$s">%1$s</a> %3$s</strong>%4$s', $title, $this->get_edit_uri( $item->subscriber_id ), $label, $row_actions );     	
	}

	/** == Contenu personnalisé : Listes de diffusion == **/
	public function column_subscriber_lists( $item )
	{
		$DbList 	= \tify_db_get( 'emailing_mailinglist' );
		$DbListRel 	= \tify_db_get( 'emailing_mailinglist_relationships' );
					
		$output  = "";
		$output .= "<ul style=\"margin:0\">\n";
		$output .= "\t<li style=\"margin-bottom:2px;\"><b>".__( 'Inscrit à : ', 'tify' )."</b>\n";
		if( $list_ids = $DbListRel->select()->col( 'list_id', array( 'subscriber_id' => $item->subscriber_id, 'active' => 1 ) ) ) : 
			$list = array();
			foreach( $list_ids as $list_id )
				$list[] = "<a href=\"". ( add_query_arg( 'list_id', $list_id, $this->BaseUri ) ) ."\">". ( $list_id ? $DbList->select()->cell_by_id( $list_id, 'title' ) : __( 'Inscription sans liste', 'tify' ) )  ."</a>";
			$output .= join( ', ', $list );
		else :
			$output .=  __( 'Aucune', 'tify' );
		endif;
		$output .= "\t<li style=\"margin-bottom:2px;\"><b>".__( 'Désinscrit de : ', 'tify' )."</b>\n";
		if( $list_ids = $DbList->get_subscriber_list_ids( $item->subscriber_id, 0 ) ) : 
			$list = array();
			foreach( $list_ids as $list_id )
				$list[] = "<a href=\"". ( add_query_arg( 'list_id', $list_id, $this->BaseUri ) ) ."\">". $DbList->select()->cell_by_id( $list_id, 'title' ) ."</a>";
			$output .= join( ', ', $list );
		else :
			$output .=  __( 'Aucune', 'tify' );
		endif;
		$output .= "\t</li>\n";
		$output .= "\t<li style=\"margin-bottom:2px;\"><b>".__( 'En attente pour : ', 'tify' )."</b>\n";
		if( $list_ids = $DbListRel->select()->col( 'list_id', array( 'subscriber_id' => $item->subscriber_id, 'active' => -1 ) ) ) : 
			$list = array();
			foreach( (array) $list_ids as $list_id )
				$list[] = "<a href=\"". ( add_query_arg( 'list_id', $list_id, $this->BaseUri ) ) ."\">". ( $list_id ? $DbList->select()->cell_by_id( $list_id, 'title' ) : __( 'Inscription sans liste', 'tify' ) ) ."</a>";
			$output .= join( ', ', $list );
		else :
			$output .=  __( 'Aucune', 'tify' );
		endif;
		$output .= "\t</li>\n";	
		
		$output .= "<ul>\n";
		
		return $output;		
	}
	
	/** == Contenu personnalisé : Date d'inscription == **/
	public function column_subscriber_date( $item )
	{
		if( $item->subscriber_date !== '0000-00-00 00:00:00' )
			return mysql2date( __( 'd/m/Y à H:i', 'tify' ), $item->subscriber_date );
		else
			return __( 'Indéterminé', 'tify' );
	}
}