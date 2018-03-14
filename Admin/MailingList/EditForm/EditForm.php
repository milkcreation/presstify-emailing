<?php
namespace tiFy\Plugins\Emailing\Admin\MailingList\EditForm;

class EditForm extends \tiFy\Core\Templates\Admin\Model\Form
{
	/* = PARAMETRAGE = */
	/** == Définition des messages de notification == **/
	public function set_notices()
	{
		return array(
			'updated' 		=> array(
				'message'		=> __( 'La liste de diffusion a été enregistrée avec succès', 'tify' ),
				'notice'		=> 'success',
				'dismissible'	=> true
			),
			'existing_list' 				=> array(
				'message'		=> __( 'Il y a déjà une autre liste de diffusion pourtant le même nom', 'tify' ),
				'notice'		=> 'error'
			)
		);
	}
	
	/* = DECLENCHEURS = */
	/** == Mise en file des scripts de l'interface d'administration == **/
	public function admin_enqueue_scripts()
	{						
		// Mise en file des scripts
		tify_control_enqueue( 'switch' );
		tify_control_enqueue( 'text_remaining' );
		wp_enqueue_style( 'tify_Emailing_Admin_MailingList_EditForm', self::tFyAppUrl() .'/EditForm.css', array( ), '150406' );	
	}
	
	/* = TRAITEMENT DES DONNÉES = */
	/** == Traitement des données de requete == **/
	public function parse_postdata( $data )
	{
		// Identifiant
		if( ! empty( $data['list_id'] ) )
			 $data['list_id'] = (int) $data['list_id'];
		
		// Vérification
		if( ! empty( $data['list_title'] ) && $this->db()->select()->cell( 'title', array( 'exclude' => $data['list_id'], 'title' => $data['list_title'] ) ) ) 
			return new \WP_Error( 'existing_list' ); 		
				
		// Token
		if( empty( $data['list_uid'] ) )
			 $data['list_uid'] = tify_generate_token();	
		// Titre
		if( ! empty( $data['list_title'] ) )
			 $data['list_title'] = wp_unslash( $data['list_title'] );
		// Description
		if( ! empty( $data['list_description'] ) )
			 $data['list_description'] = wp_unslash( $data['list_description'] );
		// Date
		if( empty( $data['list_date'] ) || ( $data['list_date'] === '0000-00-00 00:00:00' ) )
			$data['list_date'] = current_time( 'mysql' );
		// Date de modification
		if( $data['list_date'] !== '0000-00-00 00:00:00' ) :
			$data['list_modified'] = current_time( 'mysql', false );
			$data['item_meta']['_edit_last'] = $data['user_ID'];
		endif; 
		// Status
		if( ! empty( $data['list_title'] ) && ( $data['list_status'] === 'auto-draft' ) )
			 $data['list_status'] = 'publish';
		if( ! isset( $data['list_public'] ) )
			 $data['list_public'] = 1; 
		
		return $data;
	}
		
	/* = AFFICHAGE = */
	/** == Champs cachés == **/
	public function hidden_fields()
	{
	?>		
		<input type="hidden" id="list_id" name="list_id" value="<?php echo esc_attr( $this->item->list_id );?>" />
		<input type="hidden" id="list_uid" name="list_uid" value="<?php echo esc_attr( $this->item->list_uid );?>" />
		<input type="hidden" id="list_date" name="list_date" value="<?php echo esc_attr( $this->item->list_date );?>" />
		<input type="hidden" id="list_status" name="list_status" value="<?php echo esc_attr( $this->item->list_status );?>" />
	<?php
	}
	
	/** == Formulaire d'édition == **/
	public function form()
	{
	?>
		<input type="text" id="title" name="list_title" value="<?php echo $this->item->list_title;?>" placeholder="<?php _e( 'Intitulé de la liste de diffusion', 'tify' );?>">						
		<?php tify_control_text_remaining( array( 'id' => 'content', 'name' => 'list_description', 'value' => $this->item->list_description, 'attrs' => array( 'placeholder' => __( 'Brève description de la liste de diffusion', 'tify' ) ) ) );
	}
	
	/** == Affichage des actions secondaire de la boîte de soumission du formulaire == **/
	public function minor_actions()
	{
	?>
		<div class="access" style="padding:10px 0;">
			<strong><?php _e( 'Publique :', 'tify' );?></strong>
			<?php 
			tify_control_switch( 
				array(
					'name'				=> 'list_public',
					'value_on'			=> 1,
					'value_off'			=> 0,
					'checked' 			=> (int) $this->item->list_public
				)
			);
			?>
			<em style="color:#AAA; display:block;margin-top:5px;"><?php _e( 'Si une liste n\'est pas déclarée publique elle sera considérée comme privée. <br>Ce qui signifie que les abonnés n\'auront alors pas la possibilité de se désinscrire directement.<br>Une demande de désinscription sera soumise à validation du modérateur des listes de diffusion privées', 'tify' );?></em>
		</div>
	<?php
	}
}