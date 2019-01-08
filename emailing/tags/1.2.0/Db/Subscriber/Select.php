<?php
namespace tiFy\Plugins\Emailing\Db\Subscriber;

class Select extends \tiFy\Core\Db\Select
{
	/* = COMPTE = */
	/** == Compte le nombre d'éléments selon une liste de critère == **/
	public function count( $args = array() )
	{
		$name 			= $this->Db->Name;
		$primary_key 	= $this->Db->Primary;		
			
		// Traitement des arguments de requête
		$defaults = array(
			'item__not_in'	=> '',
			's'				=> '',
			'limit' 		=> -1,
			// Arguments de relation
			'list_id'		=> -1,
			'active'		=> null
		);
		$args = $this->Db->parse()->query_vars( $args, $defaults );
		
		// Traitement de la requête		
		global $wpdb;
					
		/// Selection de la table de base de données
		$query  = "SELECT COUNT( {$name}.{$primary_key} ) FROM {$name}";
		
		/// Jointure
		if( ( $args['list_id'] > -1 ) || ! is_null( $args['active'] ) )
			$query .= " INNER JOIN {$wpdb->wistify_list_relationships} ON {$name}.subscriber_id = {$wpdb->wistify_list_relationships}.rel_subscriber_id";
				
		/// Conditions définies par les arguments de requête
		if( $clause_where = $this->Db->parse()->clause_where( $args ) )
			$query .= " ". $clause_where;		
		
		/// Conditions de la table de relation
		if( $args['list_id'] > -1 ) 
			$query .= " AND {$wpdb->wistify_list_relationships}.rel_list_id = {$args['list_id']}";				
		if( ! is_null( $args['active'] ) )
			$query .= " AND {$wpdb->wistify_list_relationships}.rel_active = {$args['active']}";
		
		/// Recherche de terme
		if( $clause_search = $this->Db->parse()->clause_search( $args['s'] ) )
			$query .= " ". $clause_search;
		
		/// Exclusions
		if( $clause__not_in = $this->Db->parse()->clause__not_in( $args['item__not_in'] ) )
			$query .= " ". $clause__not_in;
	
		//// Limite
		if( $args['limit'] > -1 )
			$query .= " LIMIT {$args['limit']}";

		// Résultat		
		return (int) $wpdb->get_var( $query );
	}
	
	/* = COLONNE = */
	/** == Récupération des valeurs d'une colonne de plusieurs éléments selon des critères == **/
	public function col( $col_name = null, $args = array() )
	{
		$name 			= $this->Db->Name;
		$primary_key 	= $this->Db->Primary;
		
		// Traitement de l'intitulé de la colonne
		if( is_null( $col_name ) )
			$col_name = $primary_key;
		elseif( ! $col_name = $this->Db->isCol( $col_name ) )
			return null;
				
		// Traitement des arguments de requête
		$defaults = array(
			'item__in'		=> '',
			'item__not_in'	=> '',
			's'				=> '',				
			'per_page' 		=> -1,
			'paged' 		=> 1,
			'order' 		=> 'DESC',
			'orderby' 		=> $primary_key,
			// Arguments de relation
			'list_id'		=> -1,
			'active'		=> null
		);
		$args = $this->Db->parse()->query_vars( $args, $defaults );
		
		// Traitement de la requête
		global $wpdb;
		
		/// Selection de la table de base de données
		$query  = "SELECT {$name}.{$col_name} FROM {$name}";
		
		/// Jointure de la table en relation
		if( ( $args['list_id'] > -1 ) || ! is_null( $args['active'] ) )
			$query .= " INNER JOIN {$wpdb->wistify_list_relationships} ON {$name}.subscriber_id = {$wpdb->wistify_list_relationships}.rel_subscriber_id";
				
		/// Conditions définies par les arguments de requête
		if( $clause_where = $this->Db->parse()->clause_where( $args ) )
			$query .= " ". $clause_where;
		
		/// Conditions de la table de relation
		if( $args['list_id'] > -1 ) 
			$query .= " AND {$wpdb->wistify_list_relationships}.rel_list_id = {$args['list_id']}";				
		if( ! is_null( $args['active'] ) )
			$query .= " AND {$wpdb->wistify_list_relationships}.rel_active = {$args['active']}";
		
		/// Recherche de termes
		if( $clause_search = $this->Db->parse()->clause_search( $args['s'] ) )
			$query .= " ". $clause_search;
		
		/// Inclusions
		if( $clause__in = $this->Db->parse()->clause__in( $args['item__in'] ) )
			$query .= " ". $clause__in;
		
		/// Exclusions
		if( $clause__not_in = $this->Db->parse()->clause__not_in( $args['item__not_in'] ) )
			$query .= " ". $clause__not_in;
		
		/* 	
		/// Ordre
		if( $item__in && ( $orderby === 'item__in' ) )
			$query .= " ORDER BY FIELD( {$this->wpdb_table}.{$this->primary_key}, $item__in )";
		else */
		if( $clause_order = $this->Db->parse()->clause_order( $args['orderby'], $args['order'] ) )
			$query .= $clause_order;
		
		/// Limite
		if( $args['per_page'] > 0 ) :
			if( ! $args['paged'] )
				$args['paged'] = 1;		
			$offset = ($args['paged']-1)*$args['per_page'];
			$query .= " LIMIT {$offset}, {$args['per_page']}";
		endif;

		// Resultats				
		if( $res = $wpdb->get_col( $query ) )
			return array_map( 'maybe_unserialize', $res );
	}
}