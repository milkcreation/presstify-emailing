<?php
namespace tiFy\Plugins\Emailing\Admin\Options;

use tiFy\Plugins\Emailing\Options as tyemOptions;

/** == Informations de contact == **/
class ContactInfos extends \tiFy\Core\Taboox\Admin
{
	/* = INITIALISATION DE L'INTERFACE D'ADMINISTRATION = */
	public function admin_init()
	{
		register_setting( $this->page, 'tyem_contact_infos' );
	}
	
	/* = FORMULAIRE DE SAISIE = */
	public function form()
	{
		$opts = tyemOptions::Get( 'tyem_contact_infos' );	
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<?php _e( 'Nom de contact', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_user">
						<input type="text" name="tyem_contact_infos[contact_name]" value="<?php echo esc_attr( $opts['contact_name'] );?>" size="50"/>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Email de contact', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_email">
						<input type="text" name="tyem_contact_infos[contact_email]" value="<?php echo esc_attr( $opts['contact_email'] );?>" size="50"/>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Email de réponse', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_email">
						<input type="text" name="tyem_contact_infos[reply_to]" value="<?php echo esc_attr( $opts['reply_to'] );?>" size="50"/>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Société / Organisation', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_building">
						<input type="text" name="tyem_contact_infos[company_name]" value="<?php echo esc_attr( $opts['company_name'] );?>" size="50"/>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Site internet', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_link">
						<input type="text" name="tyem_contact_infos[website]" value="<?php echo esc_url( $opts['website'] );?>" size="50"/>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Adresse postale', 'tify' );?>
				</th>
				<td>
					<textarea name="tyem_contact_infos[address]" cols="48" rows="4" style="resize:none;box-shadow:none;"><?php echo esc_attr( $opts['address'] );?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e( 'Téléphone', 'tify' );?>
				</th>
				<td>
					<div class="tify_input_phone">
						<input type="text" name="tyem_contact_infos[phone]" value="<?php echo esc_attr( $opts['phone'] );?>" />
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
	}
}