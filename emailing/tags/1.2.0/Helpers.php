<?php
/* = CAMPAGNES = */
/** == Récupération du titre d'une campagne == **/
function tify_emailing_campaign_title( $id )
{
	return tiFy\Plugins\Emailing\Template\Template::CampaignTitle( $id );
}

/** == Liste déroulante des campagnes == **/
function tify_emailing_campaign_dropdown( $args = array() )
{
	return tiFy\Plugins\Emailing\Template\Template::CampaignDropdown( $args );
}

/* = LISTES DE DIFFUSION = */
/** == Liste déroulante des listes de diffusion == **/
function tify_emailing_mailinglist_dropdown( $args = array() )
{
	return tiFy\Plugins\Emailing\Template\Template::MailingListDropdown( $args );
}