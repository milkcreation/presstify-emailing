<?php
namespace tiFy\Plugins\Emailing\Db;

use tiFy\Core\Db\Factory as tiFyCoreDbFactory;

class Campaign extends tiFyCoreDbFactory
{
	/* == METHODES GLOBAL == **/
	/** == Mise à jour du status == 
	 * @param int 		$campaign_id	ID de la campagne
	 * @param string 	$status		
	**/
	public function update_status( $campaign_id, $status = '' )
	{
		if( ! in_array( $status, array( 'edit', 'preparing', 'ready', 'send', 'forwarded', 'trash' ) ) )
			return;
		
		return $this->handle()->update( $campaign_id, array( 'status' => $status ) );
	}
		
	/* = JOURNALISATION DE LA PREPARATION = */
	/** == Attributs par défaut des logs de préparation == **/
	public function defaults_prepare_log()
	{
		return array( 
			'total' 		=> 0, 
			'enqueue' 		=> 0,
			'invalid'		=> array(), 
			'duplicate' 	=> array(), 
			'hard-bounce' 	=> array(), 
			'soft-bounce' 	=> array(), 
			'rejected' 		=> array() 
		);
	}
	
	/** == Création des logs de préparation == **/
	public function set_prepare_log( $campaign_id )
	{
		$this->meta()->update( $campaign_id, 'prepare_log', maybe_serialize( $this->defaults_prepare_log() ), true );
	}

	/** == Récupération des logs de préparation == **/
	public function get_prepare_log( $campaign_id )
	{
		return $this->meta()->get( $campaign_id, 'prepare_log', true );
	}
	
	/** == Mise à jours des logs de préparation == **/
	public function update_prepare_log( $campaign_id, $datas = array(), $combine = false )
	{
		$current		= ( $log = $this->get_prepare_log( $campaign_id ) ) ? $log :  $this->defaults_prepare_log();
		$keys 			= array_keys( $this->defaults_prepare_log() );
		$updated_datas	= array();
		
		foreach( $datas as $key => $value ) :
			if( ! in_array( $key, $keys ) )
				continue;
			if( $combine && is_array( $value ) && isset( $current[$key] ) && is_array( $current[$key] ) ) :
				$updated_datas[$key] = array_merge_recursive( $current[$key], $value );
			else :
				$updated_datas[$key] = $value;
			endif;
		endforeach;
		
		$meta_value = wp_parse_args( $updated_datas, $current );
		
		return $this->meta()->update( $campaign_id, 'prepare_log', $meta_value );
	}
	
	/** == Récupération des logs de préparation == **/
	public function delete_prepare_log( $campaign_id )
	{
		return $this->meta()->delete( $campaign_id, 'prepare_log' );
	}
	
	/* = JOURNALISATION D'ENVOI = */
	/** == Attributs par défaut des logs d'envoi == **/
	public function defaults_send_log()
	{
		return array( 
			'start' 		=> '0000-00-00 00:00:00',
			'end'			=> '0000-00-00 00:00:00',
			'processed' 	=> 0
		);
	}
	
	/** == Création des logs d'envoi == **/
	public function set_send_log( $campaign_id )
	{
		$this->meta()->update( $campaign_id, 'send_log', maybe_serialize( $this->defaults_prepare_log() ), true );
	}

	/** == Récupération des logs d'envoi == **/
	public function get_send_log( $campaign_id )
	{
		return $this->meta()->get( $campaign_id, 'send_log', true );
	}
	
	/** == Mise à jours des logs d'envoi == **/
	public function update_send_log( $campaign_id, $datas = array(), $combine = false )
	{
		$current		= ( $log = $this->get_send_log( $campaign_id ) ) ? $log :  $this->defaults_send_log();
		$keys 			= array_keys( $this->defaults_send_log() );
		$updated_datas	= array();
		
		foreach( $datas as $key => $value ) :
			if( ! in_array( $key, $keys ) )
				continue;
			if( $combine && is_array( $value ) && isset( $current[$key] ) && is_array( $current[$key] ) ) :
				$updated_datas[$key] = array_merge_recursive( $current[$key], $value );
			else :
				$updated_datas[$key] = $value;
			endif;
		endforeach;
		
		$meta_value = wp_parse_args( $updated_datas, $current );
		
		return $this->meta()->update( $campaign_id, 'send_log', $meta_value );
	}
	
	/** == Récupération des logs d'envoi == **/
	public function delete_send_log( $campaign_id )
	{
		return $this->meta()->delete( $campaign_id, 'send_log' );
	}
}