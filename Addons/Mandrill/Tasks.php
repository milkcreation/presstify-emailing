<?php
namespace tiFy\Plugins\Emailing\Addons\Mandrill;

class Tasks
{
	public function QueueHandle()
	{
		// Récupération du quota d'envoi par heure
		$info = $this->master->Mandrill->result( 'users', 'info', array() );
		if( is_wp_error( $info ) )
			return;
		/// Vérifie si la capacité d'envoi est suffisante
		if( $info['hourly_quota'] <= 0 )
			return;		
		$md_hourly_quota = $info['hourly_quota'];
		
		// Récupération de la quantité de emails envoyés depuis une heure
		$mandrill = new Mandrill( $this->master->get_mandrill_api_key() );
		$search = $mandrill->messages->searchTimeSeries( "ts:[$prev_time $start_time]" );
		if( is_wp_error( $search ) )
			return;
		$search = current( $search );
		$md_hourly_send = empty( $search )? 0 : $search['sent'];
		/// Vérifie si les envois effectués sont inférieurs au quota autorisé
		if( $md_hourly_send >= $md_hourly_quota )
			return;
		
		// Définition du nombre d'envoi maximum
		$max = $md_hourly_quota - $md_hourly_send;
	}
}