<?php
//https://developer.wordpress.org/plugins/cron/hooking-into-the-system-task-scheduler/
namespace tiFy\Plugins\Emailing;

use tiFy\Plugins\Emailing\Message;

class Tasks
{
	/* = ARGUMENTS = */
	private static $Shedules = array();
					
	/* = CONSTRUCTEUR = */
	public function __construct()
	{		
		// Configuration
		/// Définition des tâches planifiées
		self::$Shedules = array( 
			'tiFyEmailingQueueHandle'		=> array(
				'title'			=> __( 'Traitement de la file de mail', 'tify' ),
				'timestamp'		=> mktime( date("H")-1, 0, 0, date("m"), date("d"), date("Y") ),
				'recurrance' 	=> 'hourly',
				'action'		=> array( $this, 'QueueHandle' )
			)/*, 
			'tiFyEmailingReportUpdate'	=> array(
				'title'			=> __( 'Mise à jour des rapports d\'envoi', 'tify' ),
				'timestamp'		=> mktime( 4, 0, 0, date("m"), date("d")+1, date("Y") ),
				'recurrance' 	=> 'twicedaily',
				'action'		=> array( $this, 'ReportUpdate' )
			),  
			'tiFyEmailingReportArchive'		=> array(
				'title'			=> __( 'Archivage des rapports d\'envoi', 'tify' ),
				'timestamp'		=> mktime( 2, 0, 0, date("m"), date("d")+1, date("Y") ),
				'recurrance' 	=> 'daily',
				'action'		=> array( $this, 'ReportArchive' )
			) */
		);
				
		// Plannification des tâches
		foreach( (array) self::$Shedules as $hook => $args ) :
			if( ! wp_get_schedule( $hook ) )
				wp_schedule_event( $args['timestamp'], $args['recurrance'], $hook );
			add_action( $hook, $args['action'] );
		endforeach;	
			
		// Actions et Filtres Wordpress	
		add_action( 'init', array( $this, 'init' ) );	
	}
	
	/* = DECLENCHEURS = */
	/** == Initialisation de l'interface d'administration == **/
	public function init()
	{
		if( defined( 'DOING_CRON') && DOING_CRON === true && isset( $_REQUEST['tyemqhdle'] ) )
			$this->QueueHandle();
		if( defined( 'DOING_CRON') && DOING_CRON === true && isset( $_REQUEST['tyemrup'] ) )
			$this->ReportUpdate();
		if( defined( 'DOING_CRON') && DOING_CRON === true && isset( $_REQUEST['tyemrarch'] ) )
			$this->ReportArchive();
	}
		
	/* = CONTRÔLEURS = */
	public static function getShedules()
	{
		return self::$Shedules;
	}
	
	/** == Traitement de la file des mails == **/
	public function QueueHandle()
	{
		global $wpdb;
		
		// Récupération de la temporisation de traitement		
		$prev_time 			= date( 'U', mktime( date('H')-1, date('i'), date('s'), date('m'), date('d'), date('Y') ) );
		$current_time 		= current_time( 'timestamp' );
		$end_time			= date( 'U', mktime( date('H')+1, date('i'), date('s'), date('m'), date('d'), date('Y') ) );
		
		// Vérifie s'il existe des campagnes à envoyer
		if( ! $cids = $wpdb->get_col( $wpdb->prepare( "SELECT campaign_id FROM {$wpdb->wistify_campaign} WHERE UNIX_TIMESTAMP(campaign_send_datetime) <= %d AND campaign_status = 'send' ORDER BY campaign_send_datetime", $current_time ) ) )
			return;
		
		// Typage
		$cids = array_map( 'intval', $cids );
		
		// Contrôleurs de base de données
		$DbCampaign = \tify_db_get( 'emailing_campaign' );
		$DbQueue 	= \tify_db_get( 'emailing_queue' );
		
		// Définition du quota d'envoi à l'heure
		$quota = ! empty( Options::Get( 'tyem_send', 'hourly_quota' ) ) ? (int) Options::Get( 'tyem_send', 'hourly_quota' ) : 50;
		
		// Définition du nombre d'envoi effectués
		$count = 0;
		
		foreach( $cids as $cid ) :
			// Le nombre d'envoi maximum est atteint
			if( $count >= $quota )
				return;
						
			// Vérifie s'il y a encore des messages en attente d'acheminement dans la file
			if( 0 === $DbQueue->select()->count( array( 'campaign_id' => $cid ) ) ) :
				$DbCampaign->update_status( $cid, 'forwarded' );
				continue;
			endif;	
						
			// Récupération des messages
			for( $count; $count <= $quota; $count++ ) :
				$send_time = current_time( 'timestamp', true );
				// La fin de la tâche est atteinte
				if( $send_time >= ( $end_time - 60 ) )
					return;
				
				// Vérifie s'il existe encore des messages à envoyer
				if( ! $q = $DbQueue->select()->row( array( 'campaign_id' => $cid, 'locked' => 0, 'orderby' => 'id', 'order' => 'ASC' ) ) ) :
					$DbCampaign->update_status( $cid, 'forwarded' );
					break;
				endif;
	
				// Verrouillage du message à envoyer
				$DbQueue->handle()->update( $q->queue_id, array( 'locked' => $send_time ) );	
				
			    $message =  maybe_unserialize( base64_decode( $q->queue_message ) );
    
    	        // Test d'intégrité du message
		        if( ! is_array( $message ) ) :
		            $send = new \WP_Error( 'tiFyEmailing_BadMessageIntegrity', __( 'L\'intégrité du message à expédier n\'est pas valide', 'tify' ) );
		        // Tentative d'envoi du message
		        else :				    
				    $send = Message::Send( $message );
				endif;
				
				
				if( is_wp_error( $send ) ) :
					// Récuperation des erreurs				
					$DbQueue->handle()->update( $q->queue_id, array( 'locked' => 0 ) );
					continue;
				endif;

				// Suppression du message de la file
				$DbQueue->handle()->delete_by_id( $q->queue_id );

				// Sauvegarde du rapport d'envoi
				/** @todo **/
			endfor;

			// Vérifie s'il y a encore des messages en attente d'acheminement dans la file
			if( 0 === $DbQueue->select()->count( array( 'campaign_id' => $cid ) ) ) :
				$DbCampaign->update_status( $cid, 'forwarded' );
			endif; 
		endforeach;
	}
		
	/** == Mise à jour des rapports d'acheminement == **/
	public function ReportUpdate()
	{
		global $wpdb;
		
		$query = "SELECT report_id FROM {$wpdb->wistify_report} WHERE 1 AND report_posted_ts < %d ORDER BY FIELD(report_md_state,'posted') DESC, report_updated_ts ASC, report_posted_ts ASC";
		$report_ids = $wpdb->get_col( $wpdb->prepare( $query, time() - HOUR_IN_SECONDS ) );

		foreach( (array) $report_ids as $report_id ) :
			$this->update_report( $report_id, true );
		endforeach;
	}
	
	/** == Archivage des rapports d'acheminement == **/
	public function ReportArchive()
	{		
		global $wpdb;
		require_once( ABSPATH .'wp-admin/install-helper.php' );
		
		$expired = current_time( 'timestamp' ) - (30 * DAY_IN_SECONDS );
		$report_ids = $wpdb->get_col( $wpdb->prepare( "SELECT report_id FROM {$wpdb->wistify_report} WHERE report_posted_ts < %d ORDER BY report_posted_ts ASC", $expired ) );	
	
		foreach( $report_ids as $key => $report_id ) :
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->wistify_report} WHERE report_id = %d", $report_id ), ARRAY_A );
			$table_name = $wpdb->prefix. 'wistify_report_archives'. date( 'Y', $data['report_posted_ts'] ) . date( 'm', $data['report_posted_ts'] );
			maybe_create_table( $table_name, $this->report_archive_create_dll( $table_name ) );
			unset( $data['report_id'] );			
			if( $wpdb->insert( $table_name, $data ) )
				$wpdb->delete( $wpdb->wistify_report, array( 'report_id' => $report_id ) );
		endforeach;	
		
	}
			
	/** == Création de la table des archives mensuel des rapports == **/
	private function ReportArchiveCreateDll( $table_name ){
		global $wpdb;
		
		$charset_collate = '';
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
		
		return "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`report_id` bigint(20) unsigned NOT NULL auto_increment,
			`report_campaign_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			`report_posted_ts` int(13) NOT NULL DEFAULT '0',
			`report_updated_ts` int(13) NOT NULL DEFAULT '0',
			`report_md_ts` int(13) NOT NULL DEFAULT '0',
			`report_md__id` varchar(32) NOT NULL,
			`report_md_sender` varchar(255) NOT NULL,
			`report_md_template` varchar(255) NOT NULL,
			`report_md_subject` varchar(255) NOT NULL,
			`report_md_email` varchar(255) NOT NULL,
			`report_md_tags` longtext NOT NULL,
			`report_md_opens` int(5) NOT NULL,
			`report_md_opens_detail` longtext NOT NULL,
			`report_md_clicks` int(5) NOT NULL,
			`report_md_clicks_detail` longtext NOT NULL,
			`report_md_state` varchar(25) NOT NULL,
			`report_md_metadata` longtext NOT NULL,
			`report_md_smtp_events` longtext NOT NULL,
			`report_md_resends` longtext NOT NULL,
			`report_md_reject_reason` longtext NOT NULL,
			PRIMARY KEY  (`report_id`)
		) $charset_collate;\n";		
	}
	
	/** == Mise à jour des informations de rapport d'un message == **/
	public function update_report( $report_id, $resolve = false ){
		if( ! $id = $this->master->db->report->select()->cell_by_id( $report_id, 'md__id' ) )
			return false;
				
		$info = $this->master->Mandrill->result( 'messages', 'info', array( $id ) );	
		
		$args = array( 'ts', 'sender', 'template', 'subject', 'email', 'tags', 'opens', 'opens_detail', 'clicks', 'clicks_detail', 'state', 'metadata', 'smtp_events', 'resends' );
		if( ! is_wp_error( $info ) ) :			
			$data = array(
				'report_updated_ts'	=> current_time( 'timestamp' )
			);
			foreach( $args as $arg ) :
				if( isset( $info[$arg] ) )
					$data['report_md_'. $arg] = $info[$arg];		
			endforeach;
		elseif( $resolve && ( $info = $this->resolve_report( $report_id ) ) ) :
			$data = array(
				'report_updated_ts'	=> current_time( 'timestamp' )
			);
			foreach( $args as $arg ) :
				if( isset( $info[$arg] ) )
					$data['report_md_'. $arg] = $info[$arg];
			endforeach;
		else :
			$data = array(
				'report_updated_ts'	=> current_time( 'timestamp' ),
				'report_md_state' 	=> 'unknown'
			);				
		endif;	

		return $this->master->db->report->handle()->update( $report_id, $data );
	}

	/** == Resolution des rapport inconnus == 
	 * @see https://mandrill.zendesk.com/hc/en-us/articles/205583137-How-do-I-search-my-outbound-activity-in-Mandrill- 
	 **/
	public function resolve_report( $report_id )
	{		
		if( ! $report = $this->master->db->report->select()->row_by_id( $report_id ) )
			return false;

		$result = $this->master->Mandrill->result( 'messages', 'search', array( "full_email:{$report->report_md_email} AND subject:{$report->report_md_subject}" ) );
		if( ! is_wp_error( $result ) && ( count( $result ) === 1 ) )
			return $result[0];
		
		return false;
	}
}