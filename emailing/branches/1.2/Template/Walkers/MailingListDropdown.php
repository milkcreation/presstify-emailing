<?php
namespace tiFy\Plugins\Emailing\Template\Walkers;

class MailingListDropdown extends \Walker 
{
	/* = ARGUMENTS = */
	public $db_fields = array ( 'id' => 'list_id', 'parent' => '' );

	/* = CONTRÔLEURS = */
	/** == == **/
	public function start_el( &$output, $mailing_list, $depth = 0, $args = array(), $id = 0 ) 
	{
		$DbSubscriber = \tify_db_get( 'emailing_subscriber' );

		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $mailing_list->list_id ) . "\"";
		if ( $mailing_list->list_id == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $mailing_list->list_title;
		
		if( $args['show_count'] )
			$output .= "  (". (int) $DbSubscriber->select()->count( array( 'list_id' => $mailing_list->list_id ) ) .")";
				
		$output .= "</option>\n";
	}
}