<?php
namespace tiFy\Plugins\Emailing\Admin\Subscriber\Messager;

use tiFy\Core\Db\Db;

class Messager extends \tiFy\Core\Templates\Admin\Model\Messager\Messager
{
    /**
     * Définition des listes de diffusion
     * @var array
     */
    public function set_group_list()
    {
        $group_list = [];
        if (! $dbList = Db::get('emailing_mailinglist'))
            return $group_list;

        if ($lists = $dbList->select()->rows(['list_status'=>'publish'])) :
            foreach ($lists as $l) :
                $group_list[(int) $l->list_id] =  $l->list_title;
            endforeach;
        endif;

        return $group_list;
    }
}