<?php
namespace tiFy\Plugins\Emailing\Admin\Subscriber\Import;

use tiFy\Core\Db\Db;

class Importer extends \tiFy\Lib\Importer\tiFyDb
{
    /**
     * Abonné existant
     * @var array
     */
    public $Item              = null;

    /**
     * Type de données prises en charge
     */
    protected $DataType     = ['data', 'misc'];

    /**
     * Cartographie des données
     * @var array
     */
    public $DataMap             = [
        'subscriber_id',
        'subscriber_uid',
        'subscriber_email'      => 'email',
        'subscriber_date',
        'subscriber_modified'
    ];

    public function setDb()
    {
        return 'emailing_subscriber';
    }

    public function set_data_subscriber_id()
    {
        if ($this->Item = $this->Db->select()->row(['subscriber_email' => $this->getInput('email')])) :
            return $this->Item->subscriber_id;
        else :
            return 0;
        endif;
    }

    public function set_data_subscriber_uid()
    {
        if ($this->Item && $this->Item->subscriber_uid) :
            return $this->Item->subscriber_uid;
        else :
            return \tify_generate_token();
        endif;
    }

    public function set_data_subscriber_date()
    {
        if ($this->Item && ($this->Item->subscriber_date !== '0000-00-00 00:00:00')) :
            return $this->Item->subscriber_date;
        else :
            return \current_time('mysql');
        endif;
    }

    public function set_data_subscriber_modified()
    {
        return \current_time('mysql');
    }

    public function check_data_subscriber_email($value, $insert_id)
    {
        if (!\is_email($value)) :
            $this->Notices->AddError(sprintf(__('Import de l\'abonné impossible, l\'adresse de messagerie %s est invalide', 'tify'), $value), 'tiFyPluginEmailingSubscriberImport-InvalidEmail');
            $this->setStop();
        endif;
    }

    public function after_insert_datas($insert_id)
    {
        // Contrôleurs de base de données
        if(!$DbListRel = Db::get('emailing_mailinglist_relationships')) :
            $this->Notices->AddError(__('ERREUR SYSTEME - Impossible d\'affilier l\'abonné à une liste de diffusion', 'tify'), 'tiFyPluginEmailingSubscriberImport-MailingListAcessFailed');
        endif;

        $this->Notices->AddInfo(__('test info', 'tify'), 'tiFyPluginEmailingSubscriberImport-MailingListAcessFailed');
        $this->Notices->AddWarning(__('test avertissement', 'tify'), 'tiFyPluginEmailingSubscriberImport-MailingListAcessFailed');

        // Relation Abonné / Liste de diffusion
        $list_ids = (array)$this->getAttr('mailing_list', []);

        // Suppression des abonnés importés de la liste orpheline
        if ($list_ids) :
            $DbListRel->delete_subscriber_for_list($insert_id, 0);
        endif;

        // Ajout des abonnés dans la liste de destination
        foreach ($list_ids as $list_id) :
            if (!$list_id) :
                continue;
            endif;
            $DbListRel->insert_subscriber_for_list($insert_id, $list_id, 1);
        endforeach;
    }
}