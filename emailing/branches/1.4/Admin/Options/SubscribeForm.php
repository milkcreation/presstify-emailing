<?php
namespace tiFy\Plugins\Emailing\Admin\Options;

use tiFy\Plugins\Emailing\Options as tyemOptions;

class SubscribeForm extends \tiFy\Core\Taboox\Admin
{	
	/* = INITIALISATION DE L'INTERFACE D'ADMINISTRATION = */
	public function admin_init()
	{
		register_setting( $this->page, 'tyem_subscribe_form' );
	}
	
	/* = FORMULAIRE DE SAISIE = */
	public function form()
	{
		$opts = tyemOptions::Get( 'tyem_subscribe_form' );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<?php _e( 'Titre du formulaire', 'tify' );?>
				</th>
				<td>
					<input type="text" name="tyem_subscribe_form[title]" value="<?php echo esc_attr( $opts['title'] );?>" size="30" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Intitulé du champ email', 'tify' );?>
				</th>
				<td>
					<input type="text" name="tyem_subscribe_form[label]" value="<?php echo esc_attr( $opts['label'] );?>" size="30" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Texte de remplacement du champ email', 'tify' );?>
				</th>
				<td>
					<input type="text" name="tyem_subscribe_form[placeholder]" value="<?php echo esc_attr( $opts['placeholder'] );?>" size="30" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Texte du bouton de soumission', 'tify' );?>
				</th>
				<td>
					<input type="text" name="tyem_subscribe_form[button]" value="<?php echo esc_attr( $opts['button'] );?>" size="30" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Message de succès d\'enregistrement', 'tify' );?>
				</th>
				<td>
					<textarea name="tyem_subscribe_form[success]" style="resize:none;" cols="30" rows="4"><?php echo esc_attr( $opts['success'] );?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Liste de diffusion par défaut', 'tify' );?>
				</th>
				<td>
				<?php \tify_emailing_mailinglist_dropdown( 
						array( 
							'name' 				=> 'tyem_subscribe_form[list_id]', 
							'selected' 			=> $opts['list_id'], 
							'orderby' 			=> 'title',
							'order' 			=> 'ASC',
							'show_option_none' 	=> __( 'Aucune', 'tify' )
						) 
					);?>
				</td>
			</tr>
        </tbody>
    </table>
    <h3><?php _e( 'Email de confirmation d\'inscription', 'tify' );?></h3>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <?php _e( 'Adresse email d\'expédition', 'tify' );?>
                </th>
                <td>
                    <input name="tyem_subscribe_form[from]" value="<?php echo esc_attr( $opts['from'] );?>" size="30" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e( 'Nom de l\'expéditeur', 'tify' );?>
                </th>
                <td>
                    <input name="tyem_subscribe_form[from_name]" value="<?php echo esc_attr( $opts['from_name'] );?>" size="30" />
                </td>
            </tr>
		</tbody>
	</table>
	<?php
	}
}	