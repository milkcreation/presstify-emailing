<?php
namespace tiFy\Plugins\Emailing\Db;

use tiFy\Core\Db\Factory as DbFactory;

class MailingList_Relationships extends DbFactory
{
	/** == Suppression de toutes les relation liste de diffusion/abonnés == **/
	public function delete_list_subscribers( $list_id )
	{
		global $wpdb;
	
		return $wpdb->delete( $this->Name, array( 'rel_list_id' => $list_id ) );
	}
	 
	/** == Ajout d'une relation abonné/liste de diffusion == **/
	public function insert_subscriber_for_list( $subscriber_id, $list_id, $active = 0 )
	{
		global $wpdb;
		
		if( ! $rel = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $this->Name ." WHERE ". $this->Name .".rel_subscriber_id = %d AND ". $this->Name .".rel_list_id = %d", $subscriber_id, $list_id ) ) ) :
			return $wpdb->insert( $this->Name, array( 'rel_list_id' => $list_id, 'rel_subscriber_id' => $subscriber_id, 'rel_created' => current_time( 'mysql' ), 'rel_active' => $active ) );
		elseif( $rel->rel_active != $active ) :
			return $wpdb->update(  $this->Name, array( 'rel_active' => $active, 'rel_modified' => current_time( 'mysql' ) ), array( 'rel_id' => $rel->rel_id ) );
		endif;
	}
		
	/** == Suppression d'une relation abonné/liste de diffusion == **/
	public function delete_subscriber_for_list( $subscriber_id, $list_id )
	{
		global $wpdb;
			
		return $wpdb->delete( $this->Name, array( 'rel_list_id' => $list_id, 'rel_subscriber_id' => $subscriber_id ) );
	}
	
	/** == Suppression de toutes les relation abonné/listes de diffusion == **/
	public function delete_subscriber_lists( $subscriber_id )
	{
		global $wpdb;
	
		return $wpdb->delete( $this->Name, array( 'rel_subscriber_id' => $subscriber_id ) );
	}

    /** == Vérifie si un abonné est affilié à la liste des orphelins == **/
    public function is_orphan($subscriber_id, $active = null)
    {
        global $wpdb;

        $query = "SELECT * FROM " . $this->Name . " WHERE rel_subscriber_id = %d AND rel_list_id = 0";

        if (!is_null($active)) :
            $query .= " AND rel_active = %d";
            $prepare = $wpdb->prepare($query, $subscriber_id, $active);
        else :
            $prepare = $wpdb->prepare($query, $subscriber_id);
        endif;

        return $wpdb->query($prepare);
    }
}