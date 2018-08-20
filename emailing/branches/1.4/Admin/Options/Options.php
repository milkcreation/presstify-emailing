<?php
namespace tiFy\Plugins\Emailing\Admin\Options;

class Options extends \tiFy\Core\Templates\Admin\Model\TabooxOption\TabooxOption
{	
	/* = PARAMETRAGE = */
	/** == Définition des sections de formulaire d'édition == **/
	public function set_sections()
	{
		return array(
			array(
				'id' 			=> 'tiFyPluginEmailingAdminOptions-SubscribeForm',
				'title' 		=> __( 'Formulaire d\'inscription', 'tify' ),
				'cb'			=> "\\tiFy\\Plugins\\Emailing\\Admin\\Options\\SubscribeForm",
				'order'			=> 1
			),
			array(
				'id' 			=> 'tiFyPluginEmailingAdminOptions-ContactInfos',
				'title' 		=> __( 'Information de contact', 'tify' ),
				'cb'			=> "\\tiFy\\Plugins\\Emailing\\Admin\\Options\\ContactInfos",
				'order'			=> 2
			),
			array(
				'id' 			=> 'tiFyPluginEmailingAdminOptions-Send',
				'title' 		=> __( 'Paramètres d\'envoi', 'tify' ),
				'cb'			=> "\\tiFy\\Plugins\\Emailing\\Admin\\Options\\Send",
				'order'			=> 2
			)
		);	
	}		
}