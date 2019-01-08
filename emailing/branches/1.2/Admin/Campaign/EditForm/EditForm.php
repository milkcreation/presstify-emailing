<?php
namespace tiFy\Plugins\Emailing\Admin\Campaign\EditForm;

use tiFy\Plugins\Emailing\Emailing;
use tiFy\Plugins\Emailing\Options;

class EditForm extends \tiFy\Core\Templates\Admin\Model\EditForm\EditForm
{
	/* = ARGUMENTS = */
	// Étape courante
	private	$CurrentStep;
	
	/* = CONSTRUCTEUR = */
	public function __construct()
	{
	    parent::__construct();
	    $this->CurrentStep = isset( $_REQUEST['_step'] ) ? (int) $_REQUEST['_step'] : 1;
	}
	
	/* = PARAMETRAGE = */
	/** == Définition des messages de notification == **/
	public function set_notices()
	{
		return array(
			'updated' 						=> array(
				'message'		=> __( 'La campagne a été enregistré avec succès', 'tify' ),
				'notice'		=> 'success',
				'dismissible'	=> true
			),
			'invalid_format_from_email' 	=> array(
				'message'		=> __( 'Le format de l\'email de l\'expéditeur n\'est pas valide', 'tify' ),
				'notice'		=> 'error'
			)
		);
	}
	
	/* = DECLENCHEURS = */	
	/** == Mise en file des scripts de l'interface d'administration == **/
	final public function admin_enqueue_scripts()
	{
		// Initialisation des scripts
		wp_enqueue_style( 'tiFyPluginEmailingCampaignEditForm', self::tFyAppUrl() .'/EditForm.css', array( ), '150403' );

		switch( $this->CurrentStep ) :
			case 1 :
				tify_control_enqueue( 'text_remaining' );
				break;
			case 2 :
				wp_enqueue_script( 'tiFyPluginEmailingCampaignEditForm-step2', self::tFyAppUrl() .'/EditForm-step2.js', array( 'jquery' ), '150928', true );
				// Actions et Filtres Wordpress
				add_filter( 'tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ), 99, 2 );
				add_filter( 'mce_css', array( $this, 'mce_css' ) );
				break;
			case 3 :
				wp_enqueue_style( 'tiFyPluginEmailingCampaignEditForm-step3', self::tFyAppUrl() .'/EditForm-step3.css', array( 'tify_control-suggest' ), '150918' );
				wp_enqueue_script( 'tiFyPluginEmailingCampaignEditForm-step3', self::tFyAppUrl() . '/EditForm-step3.js', array( 'jquery', 'tify_control-suggest' ), '150918', true );
				break;
			case 4 :						
				tify_control_enqueue( 'switch' );
			break;
			case 5 :
				wp_enqueue_style( 'tiFyPluginEmailingCampaignEditForm-step5', self::tFyAppUrl() . '/EditForm-step5.css', array( 'tify_control-touch_time', 'tify_control-progress' ), '150918' );
				wp_enqueue_script( 'tiFyPluginEmailingCampaignEditForm-step5', self::tFyAppUrl() . '/EditForm-step5.js', array( 'jquery', 'tify_control-touch_time', 'tify_control-progress'  ), '150918', true );
				wp_localize_script( 'tiFyPluginEmailingCampaignEditForm-step5', 'tyem_campaign', array( 
						'handle_prepare'	=> __( 'Récupération des élments à traiter', 'tify' ),
						'handle_sub'		=> __( 'Traitement des emails abonnés', 'tify' ),
						'handle_list'		=> __( 'Traitement des emails liste de diffusion', 'tify' )			
					)
				);
			break;
		endswitch;
	}
	
	/** == Configuration de tinyMCE == **/
	final public function tiny_mce_before_init( $mceInit, $editor_id )
	{
		if( $editor_id !== 'tiFyPluginEmailingCampaignEditForm-editor' )
			return $mceInit;
		
		$mceInit['toolbar1'] 			= 'bold,italic,underline,strikethrough,blockquote,|,alignleft,aligncenter,alignright,alignjustify,|,bullist,numlist,outdent,indent,|,link,unlink,hr';
		$mceInit['toolbar2'] 			= 'pastetext,|,formatselect,fontselect,fontsizeselect';
		$mceInit['toolbar3'] 			= 'table,|,forecolor,backcolor,|,subscript,superscript,charmap,|,removeformat,|,undo,redo';
		$mceInit['toolbar4'] 			= '';	
		
		
		$mceInit['block_formats'] 		= 	'Paragraphe=p;Paragraphe sans espace=div;Titre 1=h1;Titre 2=h2;Titre 3=h3;Titre 4=h4';
		/** @see http://www.cssfontstack.com/ **/
		$mceInit['font_formats'] 		= 	"Arial=Arial,Helvetica Neue,Helvetica,sans-serif;".
											//"Comic Sans MS=comic sans ms,marker felt-thin,arial,sans-serif;".
											"Courier New=Courier New,Courier,Lucida Sans Typewriter,Lucida Typewriter,monospace;".
											"Georgia=Georgia,Times,Times New Roman,serif;".
											//"Lucida=lucida sans unicode,lucida grande,sans-serif;".
								 			"Tahoma=Tahoma,Verdana,Segoe,sans-serif;".
								 			"Times New Roman=TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;".
								 			"Trebuchet MS=Trebuchet MS,Lucida Grande,Lucida Sans Unicode,Lucida Sans,Tahoma,sans-serif;".
								 			"Verdana=Verdana,Geneva,sans-serif;";
																					
		$mceInit['table_default_attributes'] 	= json_encode( 
			array(
				'width' 					=> '600',
				'cellspacing'				=> '0', 
				'cellpadding'				=> '0', 
				'border'					=> '0'
			)
		);
		$mceInit['table_default_styles'] 		= json_encode( 
			array(				
				'border-collapse' 			=> 'collapse',
				'mso-table-lspace' 			=> '0pt',
				'mso-table-rspace' 			=> '0pt',
				'-ms-text-size-adjust' 		=> '100%',
				'-webkit-text-size-adjust' 	=> '100%',
				'background-color' 			=> '#FFFFFF',
				'border-top' 				=> '0',
				'border-bottom' 			=> '0'
			) 
		);
		
		$mceInit['wordpress_adv_hidden'] 	= false;
			
		return $mceInit;
	}
	
	/** == Ajout des styles dans TinyMCE == **/
	final public function mce_css( $mce_css )
	{
		return $mce_css = Emailing::tFyAppUrl() . '/assets/reset.css, '. self::tFyAppUrl() .'/editor-style.css';
	}
	
	/* = AFFICHAGE = */
	/** == Champs cachés == **/
	public function hidden_fields()
	{
	?>
		<input type="hidden" id="current_step" name="current_step" value="<?php echo $this->CurrentStep; ?>" />
		<input type="hidden" id="campaign_id" name="campaign_id" value="<?php echo esc_attr( $this->item->campaign_id );?>" />
		<input type="hidden" id="campaign_uid" name="campaign_uid" value="<?php echo esc_attr( $this->item->campaign_uid );?>" />
		<input type="hidden" id="campaign_author" name="campaign_author" value="<?php echo esc_attr( $this->item->campaign_author ); ?>" />
		<input type="hidden" id="campaign_date" name="campaign_date" value="<?php echo esc_attr( $this->item->campaign_date );?>" />
		<input type="hidden" id="campaign_status" name="campaign_status" value="<?php echo esc_attr( $this->item->campaign_status ); ?>" />
		<input type="hidden" id="campaign_step" name="campaign_step" value="<?php echo esc_attr( $this->item->campaign_step ); ?>" />
	<?php
	}
	
	/** == Navigation haute (étapes) == **/
	public function top_nav()
	{
		$step_title = array( 
			1 => __( 'Informations générales', 'tify' ),
			2 => __( 'Préparation du Message', 'tify' ),
			3 => __( 'Choix des destinataires', 'tify' ),
			4 => __( 'Options d\'envoi', 'tify' ),
			5 => __( 'Test et distribution', 'tify' )
		);
	?>	
		<ul id="step-breadcrumb">
		<?php foreach( range( 1, 5, 1 ) as $step ) :?>
			<li <?php if( $step === $this->CurrentStep ) echo 'class="current"';?>>
			<?php $step_txt = sprintf( __( 'Étape %d', 'tify' ), $step ). "<br><span style=\"font-size:0.7em\">". $step_title[$step] ."</span>";?>
			<?php if( ( $step <= $this->item->campaign_step ) && ( $step != $this->CurrentStep ) ) :?>
				<a href="<?php echo add_query_arg( array( $this->db()->Primary => $this->item->campaign_id, '_step' => $step ), $this->BaseUri );?>"><?php echo $step_txt;?></a>
			<?php else :?>
				<span><?php echo $step_txt;?></span>
			<?php endif;?>	
			</li>
		<?php endforeach;?>
		</ul>
	<?php
	}
	
	/** == Formulaire d'édition == **/
	public function form()
	{		
	?>	
		<div id="tiFyPluginEmailingCampaign-edit">
			<?php $this->top_nav();?>
			<div id="step-edit-<?php echo $this->CurrentStep;?>">
				<?php call_user_func( array( $this, 'step_'. $this->CurrentStep ) );?>
			</div>
		</div>
	<?php	
	}
	
	/** == ETAPE #1 - INFORMATIONS GENERALES == **/
	public function step_1( )
	{
	?>	
		<input type="text" autocomplete="off" id="title" value="<?php echo esc_attr( $this->item->campaign_title );?>" size="30" name="campaign_title" placeholder="<?php _e( 'Intitulé de la campagne', 'tify' );?>">
		
		<?php tify_control_text_remaining( array( 'id' => 'content', 'name' => 'campaign_description', 'value' => esc_html( $this->item->campaign_description ), 'attrs' => array( 'placeholder' => __( 'Brève description de la campagne', 'tify' ) ) ) );?>
	<?php
	}

	/** == ETAPE #2 - PERSONNALISATION DU MESSAGE == **/
	public function step_2( )
	{
		$content_html = $this->item->campaign_content_html;

		wp_editor( 	
			$content_html, 
			'tiFyPluginEmailingCampaignEditForm-editor', 
			array(
				'wpautop'		=> false,
				'media_buttons'	=> true,
				'textarea_name'	=> 'campaign_content_html'
			) 
		);	
	}

	/** == ETAPE #3 - DESTINATAIRES == **/
	public function step_3( )
	{
		$total = 0;
		tify_control_suggest( 
			array(
				'id'			=> 'recipient-search',
				'container_id'  => 'recipient-search',
				'placeholder'	=> __( 'Tapez l\'email d\'un abonné ou l\'intitulé d\'une liste de diffusion', 'tify' ),
				'ajax_action'	=> 'tiFyPluginEmailingRecipientsSuggest',
				'elements'			=> array( 'label', 'type', 'type_label', 'ico', 'numbers' )
			)
		);
		$DbSubcriber 	= \tify_db_get( 'emailing_subscriber' );
		$DbList 		= \tify_db_get( 'emailing_mailinglist' );
	?>

		<div style="padding:5px;"><i class="fa fa-info-circle" style="font-size:24px; vertical-align:middle; color:#1E8CBE;"></i>&nbsp;&nbsp;<b><?php _e( 'Emails : abonné ou utilisateur Wordpress | Intitulés : liste/groupe de diffusion ou rôle Wordpress', 'tify' );?></b></div>
		<ul id="recipients-list">
		<?php if( isset( $this->item->campaign_recipients['wystify_subscriber'] ) ) :?>
			<?php foreach( (array) $this->item->campaign_recipients['wystify_subscriber'] as $recipient ) : ?>
				<?php if( ! $DbSubcriber->select()->row_by_id( $recipient ) ) continue; ?>
				<li data-numbers="1">
					<span class="ico">
						<i class="fa fa-user"></i>
						<i class="badge wisti-logo"></i>
					</span>
					<span class="label"><?php echo $DbSubcriber->select()->cell_by_id( $recipient, 'email' );?></span>
					<span class="type"><?php _e( 'Abonné', 'tify' );?></span>
					<a href="" class="tify_button_remove remove"></a>					
					<input type="hidden" name="campaign_recipients[wystify_subscriber][]" value="<?php echo $recipient;?>">	
				</li>	
			<?php $total++; endforeach;?>
		<?php endif; ?>
		<?php if( isset( $this->item->campaign_recipients['wystify_mailing_list'] ) ) :?>
			<?php foreach( (array) $this->item->campaign_recipients['wystify_mailing_list'] as $list_id ) : ?>
				<?php $numbers = $DbSubcriber->select()->count( array( 'list_id' => $list_id, 'status' => 'registred', 'active' => 1 ) );?>
				<li data-numbers="<?php echo $numbers;?>">
					<span class="ico">
						<i class="fa fa-group"></i>
						<i class="badge wisti-logo"></i>
					</span>
					<span class="label"><?php echo $DbList->select()->cell_by_id( $list_id, 'title' );?></span>
					<span class="type"><?php _e( 'Liste de diffusion', 'tify' );?></span>
					<span class="numbers"><?php echo $numbers;?></span>
					<a href="" class="tify_button_remove remove"></a>					
					<input type="hidden" name="campaign_recipients[wystify_mailing_list][]" value="<?php echo $list_id;?>">	
				</li>	
			<?php $total+= $numbers; endforeach;?>
		<?php endif; ?>
		</ul>
		<div id="recipients-total">
			<span class="label"><?php _e( 'Total :', 'tify' );?></span>&nbsp;<strong class="value"><?php echo $total;?></strong>
		</div>
	<?php
	}
	
	/** == ETAPE #4 - OPTIONS DE MESSAGE == **/
	public function step_4( )
	{
		// Définition des options par defaut
		$defaults = array(
			'subject' 		=> $this->item->campaign_title,
			'from_email'	=> ( $from_email = Options::Get( 'tyem_contact_infos', 'contact_email' ) ) ? $from_email : get_option( 'admin_email' ),
			'from_name'		=> ( $from_name = Options::Get( 'tyem_contact_infos', 'contact_name' ) ) ? $from_name : ( ( $user = get_user_by( 'email', get_option( 'admin_email' ) ) ) ? $user->display_name : '' ),
			'headers'		=> array(
				'Reply-To'		=> ( $reply_to = Options::Get( 'tyem_contact_infos', 'reply_to' ) ) ? $reply_to : ''
			),
			'important'		=> 'off',
			'track_opens'	=> 'on',
			'track_clicks'	=> 'on'	
		);
		$this->item->campaign_message_options = wp_parse_args( $this->item->campaign_message_options, $defaults );
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><?php _e( 'Sujet du message', 'tify' );?></th>
					<td><input type="text" name="campaign_message_options[subject]" value="<?php echo $this->item->campaign_message_options['subject'];?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><?php _ex( 'Email de l\'expéditeur', 'tiFyPluginEmailing', 'tify' );?></th>
					<td><input type="text" name="campaign_message_options[from_email]" value="<?php echo $this->item->campaign_message_options['from_email'];?>" class="widefat" placeholder="<?php printf( __( '%s (par défaut)', 'tify' ), ( ( $contact_email = Options::Get( 'tyem_contact_infos', 'contact_email' ) ) ? $contact_email : get_option( 'admin_email' ) ) );?>" /></td>
				</tr>
				<tr>
					<th><?php _ex( 'Nom de l\'expéditeur', 'tiFyPluginEmailing', 'tify' );?></th>
					<td><input type="text" name="campaign_message_options[from_name]" value="<?php echo $this->item->campaign_message_options['from_name'];?>" class="widefat" placeholder="<?php echo ( ( $contact_name = Options::Get( 'tyem_contact_infos', 'contact_name' ) ) && empty( $this->item->campaign_message_options['from_email'] ) ) ? sprintf( __( '%s (par défaut)', 'tify'), $contact_name ) : '';?>" /></td>
				</tr>
				<tr>
					<th><?php _ex( 'Email de réponse', 'tiFyPluginEmailing', 'tify' );?></th>
					<td><input type="text" name="campaign_message_options[headers][Reply-To]" value="<?php echo $this->item->campaign_message_options['headers']['Reply-To']?>" class="widefat" /></td>
				</tr>
				<tr>
					<th><?php _e( 'Marqué le message comme important', 'tify' );?></th>
					<td><?php tify_control_switch( array( 'name' => 'campaign_message_options[important]', 'checked' => ( $this->item->campaign_message_options['important'] ? $this->item->campaign_message_options['important'] : 'off' ) ) );?></td>
				</tr>
				<tr>
					<th><?php _e( 'Suivi de l\'ouverture des messages', 'tify' );?></th>
					<td><?php tify_control_switch( array( 'name' => 'campaign_message_options[track_opens]', 'checked' => ( $this->item->campaign_message_options['track_opens'] ? $this->item->campaign_message_options['track_opens'] : 'on' ) ) );?></td>
				</tr>
				<tr>
					<th><?php _e( 'Suivi des clics depuis les liens du message', 'tify' );?></th>
					<td><?php tify_control_switch( array( 'name' => 'campaign_message_options[track_clicks]', 'checked' => ( $this->item->campaign_message_options['track_clicks'] ? $this->item->campaign_message_options['track_clicks'] : 'on'  ) ) );?></td>
				</tr>
			</tbody>
		</table>
	<?php
	}

	/** == ETAPE #5 - OPTIONS D'ENVOI == **/
	public function step_5( )
	{
		$DbSubcriber 	= \tify_db_get( 'emailing_subscriber' );
		$DbMailQueue 	= \tify_db_get( 'emailing_queue' );

		$defaults = array(
			'test_email' 	=> wp_get_current_user()->user_email,
		);
		$this->item->campaign_send_options = wp_parse_args( $this->item->campaign_send_options, $defaults );

		$total  = 0;
		if( isset( $this->item->campaign_recipients['wystify_subscriber'] ) )
			foreach( $this->item->campaign_recipients['wystify_subscriber'] as $subscriber_id )
				if( $DbSubcriber->select()->cell_by_id( $subscriber_id, 'status' ) === 'registred' )
					$total++;
		if( isset( $this->item->campaign_recipients['wystify_mailing_list'] ) )
			foreach( $this->item->campaign_recipients['wystify_mailing_list'] as $list_id )
				$total += $DbSubcriber->select()->count( array( 'list_id' => $list_id, 'status' => 'registred' ) );
			
		$set_send_active = $DbMailQueue->select()->has( 'campaign_id', $this->item->campaign_id );	
	?>
		<div class="tifybox">
			<h3><?php _e( 'Tester la campagne', 'tify' );?></h3>
			<div class="inside">
				<div id="send-test">		
					<div id="send-test-submit">
						<span id="send-test-ok"></span>
						<input type="text" id="send-test-email" name="campaign_send_options[test_email]" value="<?php echo $this->item->campaign_send_options['test_email'];?>" size="80" autocomplete="off"/>
						<button class="button-secondary"><i class="fa fa-paper-plane"></i><div class="tify_spinner"></div></button>	
					</div>
					<em style="margin-top:5px;display:block;color:#999;font-size:0.9em;"><?php _e( 'La visualisation en ligne et le lien de désinscription resteront actifs pendant 60 minutes après l\'expédition de ce mail.<br />La désinscription n\'affectera pas les abonnements relatifs à l\'email d\'expédition de test, le système procède à une désinscription pour un compte de service fictif.', 'tify');?></em>
				</div>	
			</div>	
		</div>
		
		<div id="prepare" class="tifybox">
			<h3><?php _e( 'Préparation de la campagne', 'tify' );?></h3>
			<div class="inside">
				<div id="logs">
				 	<div class="duplicates">
						<h5><?php _e( 'Doublons supprimés', 'tify' );?> (<span class="total"></span>)</h5>
						<ul></ul>
					</div>
					<div class="invalids">
						<h5><?php _e( 'Emails invalides', 'tify' );?> (<span class="total"></span>)</h5>
						<ul></ul>
					</div>
					<div class="not_found">
						<h5><?php _e( 'Correspondances introuvables', 'tify' );?> (<span class="total"></span>)</h5>
						<ul></ul>
					</div>
				</div>
									
				<div id="actions">		
					<a href="#" id="campaign-prepare"><?php _e( 'Préparer la campagne', 'tify' );?></a>
				</div>
				
				<div id="totals">
					<h5><?php _e( 'Totaux', 'tify' );?> : </h5>
					<ul>
						<li class="expected"><?php _e( 'Attendus', 'tify' );?> : <span class="value"><?php echo $total;?></span></li>
						<li class="processed"><?php _e( 'Mis en file', 'tify' );?> : <span class="value"><?php echo $DbMailQueue->select()->count( array( 'campaign_id' => $this->item->campaign_id ) );?></span></li>
					</ul>
				</div> 
			</div>	
		</div>		
					
		<?php 
			\tify_control_progress( 
				array(
					'id'		=> 'CampaignPrepareProgress'
				)
			);
		?>
	<?php	
	}
	
	/** Affichage des actions secondaires de la boîte de soumission == **/
	public function minor_actions()
	{
		if( $this->CurrentStep === 5 ) : 
			$set_send_active = \tify_db_get( 'emailing_queue' )->select()->has( 'campaign_id', $this->item->campaign_id );
	?>
		<div id="programmation">
			<h4><?php _e( 'Date d\'envoi : ', 'tify' );?></h4>
			<div class="inside">
				<ul>
					<li>
					<?php tify_control_touch_time( 
			 			array( 
			 				'name' 		=> 'campaign_send_datetime', 
			 				'id' 		=> 'campaign_send_datetime', 
			 				'value' 	=> ( $this->item->campaign_send_datetime !== '0000-00-00 00:00:00' ) ? $this->item->campaign_send_datetime : date( 'Y-m-d H:00:00', current_time( 'timestamp' ) ),
							'hour'		=> '<span style="vertical-align:middle;display:inline-block;height:1em;margin-bottom:0.5em;">h 00</span>',
							'minute'	=> false,
							'second'	=> false,
							'time_sep'	=> false
						) 
					);?> 
					</li>
					
					<li id="set_send" class="<?php echo ( $set_send_active ) ? 'active': '' ;?>">
						<label>
							<strong><?php _e( 'Placer dans la boîte d\'envoi', 'tify' );?></strong><br>
							<em style="color:#999;font-size:11px"><?php _e( 'La campagne ne pourra plus être modifiée', 'tify' );?></em>
							<input type="checkbox" name="campaign_status" value="send" <?php echo ( ! $set_send_active ) ? 'disabled="disabled"': '' ;?> <?php checked( $this->item->campaign_status === 'send' );?> autocomplete="off" />
						</label>
					</li>
				</ul>			 	
			</div>	
		</div>
	<?php endif; ?>
	
	<?php /*if( ( $this->CurrentStep > 1 ) || ( $this->item->campaign_step > $this->CurrentStep ) ) :?>
		<div class="nav">
			<?php if( $this->CurrentStep > 1 ) :?>
			<a href="<?php echo add_query_arg( array( 'step' => $this->CurrentStep-1, $this->db()->Primary => $this->item->campaign_id ), $this->BaseUri );?>" class="prev button-secondary"><?php _e( 'Étape précédente', 'tify' );?></a>
			<?php endif;?>
			<?php if( $this->item->campaign_step > $this->CurrentStep ) :?>
			<a href="<?php echo add_query_arg( array( 'step' => $this->CurrentStep+1, $this->db()->Primary => $this->item->campaign_id ), $this->BaseUri );?>" class="next button-secondary"><?php _e( 'Étape suivante', 'tify' );?></a>
			<?php endif;?>
		</div>
	<?php endif; */
	}	 
	
	/* = TRAITEMENT = */
	/** == Traitement des données à enregistrer == **/
	public function parse_postdata( $data )
	{
		// Identifiant
		if( ! empty( $data['campaign_id'] ) )
			 $data['campaign_id'] = (int) $data['campaign_id'];
		// Token
		if( empty( $data['campaign_uid'] ) )
			 $data['campaign_uid'] = tify_generate_token();
		// Auteur
		if( empty( $data['campaign_author'] ) )
			$data['campaign_author'] = get_current_user_id();
		// Date de création
		if( empty( $data['campaign_date'] ) || ( $data['campaign_date'] === '0000-00-00 00:00:00' ) )
			$data['campaign_date'] = current_time( 'mysql', false );
		// Date de modification
		if( $data['campaign_date'] !== '0000-00-00 00:00:00' ) :
			$data['campaign_modified'] = current_time( 'mysql', false );
			$data['item_meta']['_edit_last'] = $data['user_ID'];
		endif;
		// Status
		if( ! empty( $data['campaign_title'] ) && ( $data['campaign_status'] === 'auto-draft' ) )
			 $data['campaign_status'] = 'edit';				
		/// Etape
		if( ( (int) $data['campaign_step'] < 5 ) && ( (int) $data['campaign_step'] <= $this->CurrentStep ) )
			$data['campaign_step'] = (int) $this->CurrentStep+1;		
		// Titre
		if( ! empty( $data['campaign_title'] ) )
			 $data['campaign_title'] = wp_unslash( $data['campaign_title'] );
		// Description
		if( ! empty( $data['campaign_description'] ) )
			$data['campaign_description'] = wp_unslash( $data['campaign_description'] );
		// Contenu HTML
		if( ! empty( $data['campaign_content_html'] ) )
			$data['campaign_content_html'] = wp_unslash( $data['campaign_content_html'] );
		// Sujet du message
		if( ! empty( $data['campaign_message_options']['subject'] ) )
			 $data['campaign_message_options']['subject'] = wp_unslash( $data['campaign_message_options']['subject'] );
		// Destinataires
		if( ( $this->CurrentStep === 3 ) && empty( $data['campaign_recipients'] ) )
			$data['campaign_recipients'] = array();	  
				
		return $data;
	}
	
	/** == Éxecution des actions == **/
	protected function process_bulk_actions()
	{		
		// Vérification des habilitations
		if( ! current_user_can( $this->Cap ) )
			wp_die( __( 'Vous n\'êtes pas autorisé à modifier ce contenu.', 'tify' ) );

		// Traitement de l'élément courant
		if( ! $item_id = $this->current_item() )
			$item_id = $this->get_default_item_to_edit();

		// Vérification
		if( ! $item_id ) :
			wp_die( __( 'ERREUR SYSTEME : Impossible de créer un nouvel élément', 'tify' ) );
		elseif( ! $this->db()->select()->row_by_id( $item_id ) ) :
			wp_die( __( 'Vous tentez de modifier un contenu qui n’existe pas. Peut-être a-t-il été supprimé ?!', 'tify' ) );
        endif;
        		
		// Traitement des actions
		if( ! $this->current_item() ) :
		      
			wp_safe_redirect( add_query_arg( $this->db()->Primary, $item_id ) );
			exit;
		elseif( method_exists( $this, 'process_bulk_action_'. $this->current_action() ) ) :
			// Suppression de la file de mail et des log de préparations
			if( (int) $_GET['_step'] !== 5 ) :
				\tify_db_get( 'emailing_campaign' )->update_status( $item_id, 'edit' );
				\tify_db_get( 'emailing_queue' )->reset_campaign( $item_id );
				\tify_db_get( 'emailing_campaign' )->delete_prepare_log( $item_id );
			endif;
		
			call_user_func( array( $this, 'process_bulk_action_'. $this->current_action() ) );
		elseif( $this->item = $this->db()->select()->row_by_id( (int) $_GET[$this->db()->Primary] ) ) :		  
			if( ( $this->item->campaign_status === 'send' ) && ( @$_REQUEST['message'] === 'updated' ) ) :
				wp_redirect( $this->ListBaseUri );
				exit;
			elseif( ! in_array( $this->item->campaign_status, array( 'edit', 'ready', 'draft', 'auto-draft' ) ) ) :
				wp_die( sprintf( __( 'Le statut actuel de la campagne ne permet pas de la modifier.', 'tify' ) ) );
			endif;
						
			if( ! isset( $_GET['_step'] ) ) :
				$step = ( ! $this->item->campaign_step ) ? 1 : $this->item->campaign_step;
				$sendback = add_query_arg( array( $this->db()->Primary => $this->item->campaign_id, '_step' => $step ), $this->BaseUri );
				wp_redirect( $sendback );
				exit;
			elseif( $this->item->campaign_step && ( (int) $_GET['_step'] > $this->item->campaign_step ) ) :
				wp_die( __( 'Ne soyez pas trop impatient et complétez d\'abord toutes les étapes précédentes', 'tify' ) );
			elseif( ! empty( $_REQUEST['_wp_http_referer'] ) ) :
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $_REQUEST['_wp_http_referer'] ) );
				exit;
			endif;			
		else :
			wp_die( __( 'Vous tentez de modifier un contenu qui n’existe pas. Peut-être a-t-il été supprimé ?', 'tify' ) );	
		endif;	
	}
}