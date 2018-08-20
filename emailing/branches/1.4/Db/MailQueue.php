<?php
namespace tiFy\Plugins\Emailing\Db;

use tiFy\Core\Db\Factory as DbFactory;

class MailQueue extends DbFactory
{	
	/** == Suppression de la file d'une campagne == **/
	public function reset_campaign( $campaign_id )
	{
		global $wpdb;
		
		return $wpdb->delete( $this->Name, array( 'queue_campaign_id' => $campaign_id ), '%d' );		
	}
}