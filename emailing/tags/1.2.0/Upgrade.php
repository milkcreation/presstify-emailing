<?php
namespace tiFy\Plugins\Emailing; 

use \tiFy\Lib\Upgrade as tiFyLibUpgrade;

class Upgrade extends tiFyLibUpgrade
{		
	/* = Modification du verrou de la file de mail = */
	protected function update_1607080945()
	{
		global $wpdb;
		
		require_once( ABSPATH .'wp-admin/install-helper.php' );
		
		$wpdb->query( "ALTER TABLE {$wpdb->wistify_queue} CHANGE `queue_locked` `queue_locked` INT(13) UNSIGNED NULL DEFAULT '0';" );
		
		return __( 'Modification du verrou de la file de mail', 'tify' );
	}
	
	/* = Ajout d'une colonne de report des erreurs dans la file de mail = */
	protected function update_1607080950()
	{
		global $wpdb;
		
		require_once( ABSPATH .'wp-admin/install-helper.php' );
		
		maybe_add_column( 
			"{$wpdb->wistify_queue}",
			"queue_errors",
			"ALTER TABLE {$wpdb->wistify_queue} ADD `queue_errors` LONGTEXT NULL DEFAULT NULL AFTER `queue_locked`;" 
		);
		
		return __( 'Ajout d\'une colonne de report des erreurs dans la file de mail', 'tify' );
	}
	
	/* = Modification des intitulés d'option = */
	protected function update_1607081706()
	{
		if( $contact_infos = get_option( 'wistify_contact_information' ) ) :
			update_option( 'tyem_contact_infos', $contact_infos );
			delete_option( 'wistify_contact_information' );
		endif;
		if( $subscribe_form = get_option( 'wistify_subscribe_form' ) ) :
			update_option( 'tyem_subscribe_form', $subscribe_form );
			delete_option( 'wistify_contact_information' );
		endif;
		
		return __( 'Modification des intitulés d\'option', 'tify' );
	}
}