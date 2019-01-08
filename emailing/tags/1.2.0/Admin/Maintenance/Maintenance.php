<?php
namespace tiFy\Plugins\Emailing\Admin\Maintenance;

use \tiFy\Plugins\Emailing\Emailing;
use \tiFy\Plugins\Emailing\Tasks;

class Maintenance
{	
	/* = AFFICHAGE = */
	/** == Rendu de la page  == **/
	public function render()
	{
	?>		
	<div class="wrap">
		<h2><?php _e( 'Maintenance (Utilisateurs avancés)', 'tify' ); ?></h2><br>
		
		<div class="tifybox">
			<h3><?php _e( 'Numéros de version', 'tify' );?></h3>
			<div class="inside">
			<?php 
				printf( __( 'Version courante : %s', 'tify' ), Emailing::getVersion() );
			?>&nbsp;-&nbsp;
			<?php
				$current = get_option( 'tify_plugin_emailing_version', 0 );
				printf( __( 'Version installée : %s', 'tify' ), $current ); 					
			?>
			<p>
				<?php version_compare( $current, Emailing::getVersion(), '>=' ) ? _e( 'Votre système est à jour', 'tify' ) : _e( 'Votre système doit être mis à jour', 'tify' );?>
			</p>
			</div>
		</div>
		
		<div class="tifybox">
			<h3><?php _e( 'Tâches planifiées', 'tify' );?></h3>
			<div class="inside">
				<?php $cron_jobs = get_option( 'cron' ); $offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;?>
				<table class="form-table">
					<tbody>
					<?php foreach( (array) Tasks::getShedules() as $hook => $args ) : ?>
						<?php foreach( $cron_jobs as $timestamp => $cronhooks ) : if( !  isset( $cronhooks[$hook] ) ) continue; ?>
						<tr>
							<th><?php echo $args['title'];?></th>
							<td>
								<ul style="margin:0; padding:0;">
									<li>
										<b><?php _e( 'Prochaine exécution :', 'tify' );?></b>
										<p><?php echo date( 'd-m-Y H:i:s', wp_next_scheduled( $hook ) + $offset );?></p>
									</li>
								</ul>
							</td>	
						</tr>
						<?php endforeach;?>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		
	</div>
	<?php
	}	
}