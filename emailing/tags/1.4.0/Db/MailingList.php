<?php

namespace tiFy\Plugins\Emailing\Db;

use tiFy\Core\Db\Factory as DbFactory;

class MailingList extends DbFactory
{

    /**
     * Récupére les identifiants de qualification des listes de diffusion d'affiliation d'un abonné
     *
     * @param int $subscriber_id Identifiant de qualification de l'abonnée
     * @param bool $active Limite la récupération aux listes actives
     *
     * @return int[]
     */
    public function get_subscriber_list_ids($subscriber_id, $active = 1)
    {
        global $wpdb;

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT rel_list_id" .
                " FROM {$wpdb->wistify_list_relationships}" .
                " INNER JOIN {$this->Name} ON ( {$wpdb->wistify_list_relationships}.rel_list_id = {$this->Name}.list_id )" .
                " WHERE {$wpdb->wistify_list_relationships}.rel_subscriber_id = %d" .
                " AND {$wpdb->wistify_list_relationships}.rel_active = %d",
                $subscriber_id, $active
            )
        );
    }
}	