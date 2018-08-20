<?php
namespace tiFy\Plugins\Emailing\Admin\Subscriber\Import;

use tiFy\Core\Db\Db;

class Import extends \tiFy\Core\Templates\Admin\Model\FileImport\FileImport
{
    public function set_columns()
    {
        return ['email'=> __('Adresse de messagerie', 'Theme')];
    }

    public function set_file_columns()
    {
        return ['email'];
    }

    public function set_importer()
    {
        return '\tiFy\Plugins\Emailing\Admin\Subscriber\Import\Importer';
    }

    /**
     * Définition des champs d'options du formulaire d'import
     */
    public function set_options_fields()
    {
        $mailingDb = Db::get('emailing_mailinglist');

        $options[] = __('Aucune liste', 'tify');
        if ($list = $mailingDb->select()->pairs('list_title', null, ['status' => 'publish', 'order' => 'ASC'])) :
            $options += $list;
        endif;

        return [
            [
                'label' => __('Liste(s) de diffusion d\'affiliation', 'tify'),
                'type'  => 'select',
                'attrs' => [
                    'name'              => 'mailing_list',
                    'container_class'   => 'mailingList-Select',
                    'options'           => $options,
                    'multiple'          => true
                ]
            ]
        ];
    }

    /**
     * Mise en file des scripts de l'interface d'administration
     * {@inheritDoc}
     * @see \tiFy\Core\Templates\Admin\Model\AjaxListTable::_admin_enqueue_scripts()
     */
    public function admin_enqueue_scripts()
    {
        parent::admin_enqueue_scripts();

        // Chargement des scripts
        wp_enqueue_style( 'tiFyPluginsEmailingAdminSubscriberImporter', self::tFyAppUrl(get_class()) .'/Importer.css', [], 170924);
    }

    /**
     * Vérification d'existance d'un élément
     *
     * @param obj $item données de l'élément à tester
     *
     * @return bool false l'élément n'existe pas en base | true l'élément existe en base
     */
    public function item_exists($item)
    {
        return $this->db()->select()->has('subscriber_email', $item->email);
    }
}