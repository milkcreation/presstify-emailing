<?php
namespace tiFy\Plugins\Emailing\Template\Walkers;

class CampaignDropdown extends \Walker 
{
	/* = ARGUMENTS = */
	public $db_fields = array ( 
		'id' 		=> 'campaign_id', 
		'parent' 	=> '' 
	);
	
	/* = CONTRÔLEURS = */
	/** == == **/
	public function start_el( &$output, $campaign, $depth = 0, $args = array(), $id = 0 ) 
	{
		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $campaign->campaign_id ) . "\"";
		if ( $campaign->campaign_id == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		if( $args['show_date'] )
			$output .= ( $args['show_date'] === true ) ? mysql2date( get_option( 'date_format' ), $campaign->campaign_date ) : mysql2date( $args['show_date'], $campaign->campaign_date );
				
		$output .= wp_unslash( $campaign->campaign_title );
		$output .= "</option>\n";
	}
}