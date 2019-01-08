<?php
namespace tiFy\Plugins\Emailing\Admin\Subscriber\EditForm;

class EditForm extends \tiFy\Core\Templates\Admin\Model\EditForm\EditForm
{	
	/* = PARAMETRAGE = */
	/** == Définition des messages de notification == **/
	public function set_notices()
	{
		return array(
			'updated' 				=> array(
				'message'		=> __( 'L\'abonné a été enregistré avec succès', 'tify' ),
				'notice'		=> 'success',
				'dismissible'	=> true
			),
			'empty_email'			=> array(
				'message'		=> __( 'L\'adresse email de l\'abonné doit être renseignée', 'tify' ),
				'notice'			=> 'error'
			),
			'invalid_format_email'	=> array(
				'message'		=> __( 'Le format de l\'adresse email n\'est pas valide', 'tify' ),
				'notice'		=> 'error'
			),
			'existing_email'	=> array(
				'message'		=> __( 'Cet email est déjà utilisé par un autre abonné', 'tify' ),
				'notice'		=> 'error'
			)
		);	
	}
	
	/* = DECLENCHEURS = */	
	/** == Mise en file des scripts de l'interface d'administration == **/
	public function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'tiFy_Emailing_Admin_Subscriber_EditForm', self::tFyAppUrl() .'/EditForm.css', array( ), '150406' );
	}
	
	/* = TRAITEMENT DES DONNÉES = */	
	/** == Translation des données de formulaire == **/
	public function parse_postdata( $data )
	{
		// Vérification
		if( empty( $data['subscriber_email'] ) ) 
			return new \WP_Error( 'empty_email' );
		elseif( ! is_email( $data['subscriber_email'] ) )
			return new \WP_Error( 'invalid_format_email' );	
		elseif( $this->db()->select()->has( 'email', $data['subscriber_email'], array( 'item__not_in' => $data['subscriber_id'] ) ) )
			return new \WP_Error( 'existing_email' );	
		
		// Identifiant
		if( ! empty( $data['subscriber_id'] ) )
			 $data['subscriber_id'] = (int) $data['subscriber_id'];
		// Token
		if( empty( $data['subscriber_uid'] ) )
			 $data['subscriber_uid'] = tify_generate_token();
		// Date
		if( empty( $data['subscriber_date'] ) || ( $data['subscriber_date'] === '0000-00-00 00:00:00' ) )
			$data['subscriber_date'] = current_time( 'mysql' );
		// Date de modification
		if( $data['subscriber_date'] !== '0000-00-00 00:00:00' ) :
			$data['subscriber_modified'] = current_time( 'mysql', false );
			$data['item_meta']['_edit_last'] = $data['user_ID'];
		endif;
		
		return $data;
	}
	
	/** == Éxecution de l'action - mise à jour == **/
	protected function process_bulk_action_update()
	{
		
		$DbList		= \tify_db_get( 'emailing_mailinglist' );
		$DbListRel	= \tify_db_get( 'emailing_mailinglist_relationships' );
		
		$sendback = remove_query_arg( array( 'action', 'error', 'notice', 'message' ), wp_get_referer() );			
		$sendback = add_query_arg( array( 'action' => 'edit' ), $sendback );

		$data = $this->parse_postdata( $_POST );
		if( is_wp_error( $data ) ) :
			$sendback = add_query_arg( array( 'message' => $data->get_error_code() ), $sendback );	
		else :
			// Enregistrement de l'abonné	 
			$subscriber_id = $this->db()->handle()->record( $data );					
			// Enregistrement des liaisons abonné/liste
			/// Récupération des listes liées à l'abonné
			$original_lists = $DbList->get_subscriber_list_ids( $subscriber_id );	
			/// Mise à jour des listes liées à l'abonné
			$update_lists = ! empty( $_REQUEST['subscriber_list'] ) ? $_REQUEST['subscriber_list'] : array();
			
			/// Inscription/Désinscription à la liste orpheline (inscription sans liste)
			if( empty( $update_lists ) )
				$DbListRel->insert_subscriber_for_list( (int) $subscriber_id, 0, 1 );
			else
				$DbListRel->delete_subscriber_for_list( (int) $subscriber_id, 0 );
							
			//// Suppression des anciennes listes
			foreach( array_diff( $original_lists, $update_lists ) as $list_id )
				 $DbListRel->delete_subscriber_for_list( (int) $subscriber_id, (int) $list_id );
			//// Ajout des nouvelles listes
			foreach( (array) $update_lists as $list_id )
				$DbListRel->insert_subscriber_for_list( (int) $subscriber_id, (int) $list_id, 1 );
				
			$sendback = add_query_arg( array( 'message' => 'updated' ), $sendback );				
		endif;

		wp_redirect( $sendback );
		exit;
	}
	
	/* = AFFICHAGE = */
	/** == Champs cachés == **/
	public function hidden_fields()
	{
	?>	
		<input type="hidden" id="subscriber_id" name="subscriber_id" value="<?php echo esc_attr(  $this->item->subscriber_id );?>" />
		<input type="hidden" id="subscriber_uid" name="subscriber_uid" value="<?php echo esc_attr(  $this->item->subscriber_uid );?>" />
		<input type="hidden" id="subscriber_date" name="subscriber_date" value="<?php echo esc_attr( $this->item->subscriber_date );?>" />
	<?php
	}
	
	/** == Formulaire d'édition == **/
	public function form()
	{
		$DbList		= \tify_db_get( 'emailing_mailinglist' );
		$DbListRel	= \tify_db_get( 'emailing_mailinglist_relationships' );
		
		$suscriber_list = $DbList->get_subscriber_list_ids( $this->item->subscriber_id );
	?>
		<input type="text" id="email" name="subscriber_email" value="<?php echo $this->item->subscriber_email;?>" placeholder="<?php _e( 'Adresse email de l\'abonné', 'tify' );?>">
		<div class="tifybox">
			<h3><?php _e( 'Listes de diffusion', 'tify' );?></h3>
			<div class="inside">				
				<?php if( $DbListRel->is_orphan( $this->item->subscriber_id ) ) :?>
				<div class="notice notice-error inline">
					<p>
						<?php _e( 'Cet abonné est actuellement affilié à la liste orpheline (abonné inscrit sans aucune liste d\'appartenance).', 'tify' );?>
					</p>
				</div>
				<?php endif;?>
				
				<ul id="mailing-lists">
				<?php foreach( (array) $DbList->select()->rows( array( 'orderby' => 'title', 'order' => 'ASC', 'status' => 'publish' ) ) as $l ) : $checked = in_array( $l->list_id, $suscriber_list ) ? true : false; ?>
					<li>
						<label>
							<input type="checkbox" name="subscriber_list[]" value="<?php echo $l->list_id;?>" <?php checked( $checked );?>/>
							<span class="title"><?php echo $l->list_title;?></span>
							<span class="description"><?php echo nl2br( $l->list_description );?></span>
							<span class="numbers"><?php echo $DbListRel->select()->count( array( 'list_id' => $l->list_id, 'status' => 'registred', 'active' => 1 ) );?></span>
						</label>
					</li>
				<?php endforeach;?>
				</ul>
			</div>
		</div>
	<?php
	}
}