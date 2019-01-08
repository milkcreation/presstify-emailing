<?php
namespace tiFy\Plugins\Emailing\Admin\Options;

use tiFy\Plugins\Emailing\Options as tyemOptions;

class Send extends \tiFy\Core\Taboox\Admin
{
	/* = INITIALISATION DE L'INTERFACE D'ADMINISTRATION = */
	public function admin_init()
	{
		register_setting( $this->page, 'tyem_send' );
	}
	
	/* = MISE EN FILE DES SCRIPTS DE L'INTERFACE D'ADMINISTRATION = */
	public function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'tiFyPluginsEmailingAdminOptionsSend', self::tFyAppUrl() .'/Send.css', array(), 160708 );
		wp_enqueue_script( 'tiFyPluginsEmailingAdminOptionsSend', self::tFyAppUrl() .'/Send.js', array( 'jquery' ), 160708, true );
	}	
	
	/* = FORMULAIRE DE SAISIE = */
	public function form()
	{
		$opts = tyemOptions::Get( 'tyem_send' );	
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<?php _e( 'Nombre de mail envoyés à l\'heure', 'tify' );?>
						<em></em>
					</th>
					<td>
						<div>
							<input type="number" name="tyem_send[hourly_quota]" value="<?php echo esc_attr( $opts['hourly_quota'] );?>" size="4"/>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		
		<div id="SendEngine">
			<h3 class="section_title"><?php _e( 'Méthode d\'expédition des messages', 'tify' );?></h3>
			
			<ul class="TabNav">
				<li>
					<label>
						<input type="radio" name="tyem_send[engine]" data-target="#SendEngine-wp" value="wp" <?php checked( $opts['engine'] === 'wp' );?>/>
						<i class="dashicons dashicons-wordpress"></i>
						<span><?php _e( 'Wordpress (recommandée)', 'tify' );?></span>
					</label>
				</li>
				<li>
					<label>
						<input type="radio" name="tyem_send[engine]" data-target="#SendEngine-smtp" value="smtp" <?php checked( $opts['engine'] === 'smtp' );?>/>
						<i class="dashicons dashicons-cloud"></i>
						<span><?php _e( 'Serveur SMTP', 'tify' );?></span>
					</label>
				</li>
				<li>
					<label>
						<input type="radio" name="tyem_send[engine]" data-target="#SendEngine-php" value="php" <?php checked( $opts['engine'] === 'php' );?>/>
						<i class="dashicons dashicons-admin-generic"></i>
						<span><?php _e( 'PHP Mail', 'tify' );?></span>
					</label>
				</li>
			</ul>

			<div class="TabContent">
				<div id="SendEngine-wp" <?php if( $opts['engine'] === 'wp' ) echo "class=\"active\"";?>></div>
				<div id="SendEngine-smtp" <?php if( $opts['engine'] === 'smtp' ) echo "class=\"active\"";?>>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<?php _e( 'Hôte', 'tify' );?>
								</th>
								<td>
									<input type="text" name="tyem_send[smtp][host]" value="<?php echo esc_attr( $opts['smtp']['host'] );?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'Port', 'tify' );?>
								</th>
								<td>
									<input type="number" name="tyem_send[smtp][port]" value="<?php echo esc_attr( $opts['smtp']['port'] );?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'Utilisateur', 'tify' );?>
								</th>
								<td>
									<input type="text" name="tyem_send[smtp][username]" value="<?php echo esc_attr( $opts['smtp']['username'] );?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'password', 'tify' );?>
								</th>
								<td>
									<input type="password" name="tyem_send[smtp][password]" value="<?php echo esc_attr( $opts['smtp']['password'] );?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'Authentification', 'tify' );?>
								</th>
								<td>
									<label>
										<input type="radio" name="tyem_send[smtp][auth]" value="on" <?php checked( $opts['smtp']['auth'] === 'on' );?>/> <?php _e( 'Oui', 'tify');?>
									</label>
									<label>
										<input type="radio" name="tyem_send[smtp][auth]" value="off" <?php checked( $opts['smtp']['auth'] === 'off' );?>/> <?php _e( 'Non', 'tify');?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'Sécurité', 'tify' );?>
								</th>
								<td>
									<label>
										<input type="radio" name="tyem_send[smtp][secure]" value="" <?php checked( ! $opts['smtp']['secure'] );?>/> <?php _e( 'Aucune', 'tify');?>
									</label>
									<label>
										<input type="radio" name="tyem_send[smtp][secure]" value="ssl" <?php checked( $opts['smtp']['secure'] === 'ssl' );?>/> <?php _e( 'SSL', 'tify');?>
									</label>
									<label>
										<input type="radio" name="tyem_send[smtp][secure]" value="tls" <?php checked( $opts['smtp']['secure'] === 'tls' );?>/> <?php _e( 'TLS', 'tify');?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div id="SendEngine-php" <?php if( $opts['engine'] === 'php' ) echo "class=\"active\"";?>></div>
			</div>
			
			<h3><?php _e( 'Tester la configuration', 'tify' );?></h3>
			<div id="send-test">		
				<div id="send-test-submit">
					<span id="send-test-ok"></span>
					<input type="text" id="send-test-email" name="tyem_send[test_email]" value="<?php echo esc_attr( $opts['test_email'] );?>" size="80" autocomplete="off"/>
					<button class="button-secondary"><i class="fa fa-paper-plane"></i><div class="tify_spinner"></div></button>	
				</div>
			</div>
		</div>
	<?php
	}
}