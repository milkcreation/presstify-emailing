<?php
namespace tiFy\Plugins\Emailing;

use tiFy\Plugins\Emailing\Template\Template;
use tiFy\Plugins\Emailing\Options;
use tiFy\Lib\Mailer\Mailer;

class Message extends \tiFy\App\Factory
{
	/* = CONTROLEUR = */
	/** == Mise en file == **/
	public static function Queue( $email, $campaign_id )
	{
		// Contrôleurs de base de données
		$DbQueue = \tify_db_get( 'emailing_queue' );
		
		// Vérification de la validité du mail
		if( ! is_email( $email ) ) :
			return new \WP_Error( 'invalid_email', sprintf( __( '"%s" n\'est pas un email valide', 'tify' ), $email ) );
		endif;
				
		// Vérification des doublons
		if( $exists = $DbQueue->select()->row( array( 'email' => esc_attr( $email ), 'campaign_id' => $campaign_id ) ) ) :
			return new \WP_Error( 'duplicate_message', sprintf( __( '"%s" a déjà un message pour cette campagne', 'tify' ), $email ) );
		endif;
		
		// Préparation du message			
		$message = self::Prepare( $campaign_id, $email );	

		// Insertion du message dans la file d'attente
		return $DbQueue->handle()->record( array( 'queue_email' => esc_attr( $email ), 'queue_campaign_id' => $campaign_id, 'queue_message' => base64_encode( serialize( $message ) ) ) );
	}
	
	/** == Préparation du message == **/
	public static function Prepare( $cid, $to, $opts = array(), $test = false )
	{
		// Contrôleurs de base de données
		$DbCampaign 	= \tify_db_get( 'emailing_campaign' );
		$DbSubscriber 	= \tify_db_get( 'emailing_subscriber' );
		
		// Récupération des attributs de la campagne
		$c = $DbCampaign->select()->row_by_id( $cid );
		
		// Pré-traitement
		$defaults 			= $c->campaign_message_options;
		$opts 				= wp_parse_args( $opts, $defaults );
		
		// Destinataire
		$from = '';
		if( ! empty( $opts['from_email'] ) ) :
			if( ! empty( $opts['from_name'] ) ) :
				$from = array( 'name' => $opts['from_name'], 'email' => $opts['from_email'] );
			else :
				$from = $opts['from_email'];
			endif;
		endif;		
		
		// Formatage du sujet de message
		$subject 	= self::getSubject( $cid );
		if( $test )
			$subject = "[TEST] ". $subject;
								
		// Variables d'environnement
		if( $test ) :
			$uuid = 'test-'. tify_generate_token(); set_transient( 'tyem_'. $uuid, $to, HOUR_IN_SECONDS );
		elseif( $u = $DbSubscriber->select()->row_by( 'email', $to ) ) :
			$uuid = $u->subscriber_uid;
		endif;
		$merge_vars = array(
			'ARCHIVE' 	=> add_query_arg( array( 'u' => $uuid, 'c' => $c->campaign_uid ), home_url( '/wistify/archive' ) ),
			'UNSUB'		=> add_query_arg( array( 'u' => $uuid, 'c' => $c->campaign_uid ), home_url( '/wistify/unsubscribe' ) )
		);	
		
		// Mots-clefs	
		$opts['tags'] = array( $c->campaign_uid, tify_excerpt( $c->campaign_title, array( 'max' => 45, 'teaser' => false ) ) );
			
		/// Convertion des valeurs boléennes
		foreach( $opts as $k => &$v ) :
			if( in_array( $k, array( 'important', 'track_opens', 'track_clicks', 'auto_text', 'auto_html', 'inline_css', 'url_strip_qs', 'preserve_recipients', 'view_content_link', 'merge' ) ) ) :
				$v = filter_var( $v, FILTER_VALIDATE_BOOLEAN );
			endif;
		endforeach;
			
		// Traitement du contenu du message						 
		$message = array( 
			'to'				=> $to,
			'from'				=> $from,
			'subject' 			=> $subject,				
			'html_head'			=> self::getHtmlHead( $cid ),
			'html_body_attrs'	=> self::getHtmlBodyAttrs( $cid ),
			'html_body_wrap'	=> "<div style=\"width:600px;margin:0 auto;\">%s</div>",
			'html'				=> self::getHtmlContent( $cid ),
			'merge_vars'		=> $merge_vars,
			'additionnal'		=> $opts
		);		
		
		return $message;
	}
	
	/** == Envoi == **/
	public static function Send( $message )
	{
		$mailer = new Mailer;
				
		if( ( Options::Get( 'tyem_send', 'engine' ) === 'smtp' ) && ( $engine_opts = Options::Get( 'tyem_send', 'smtp' ) ) ) :
			$message['engine'] = 'smtp'; $message['engine_opts'] = $engine_opts;
		endif;
		
		if( empty( $message['from'] ) && ( $from_email = Options::Get( 'tyem_contact_infos', 'contact_email' ) ) ) :
			if( $from_name = Options::Get( 'tyem_contact_infos', 'contact_name' ) ) :
				$message['from'] = array( 'email' => $from_email, 'name' => $from_name );
			else :
				$message['from'] = $from_email;
			endif;
		endif;
		
		$mailer->prepare( $message );

		return $mailer->send();	
	}
	
	/** == Récupération du sujet de message == **/
	public static function getSubject( $cid )
	{
		if( ( $options = \tify_db_get( 'emailing_campaign' )->select()->cell_by_id( $cid, 'message_options' ) ) && ! empty( $options['subject'] ) ) :
			$subject = $options['subject'];
		else :
			$subject = \tify_db_get( 'emailing_campaign' )->select()->cell_by_id( $cid, 'title' );
		endif;
		
		return wp_unslash( $subject );
	}
		
	/** == == **/
	public static function getHtmlOutput( $cid, $archive = true, $unsub = true )
	{
		$output  = "";
		$output .= self::getHtmlHead( $cid );
		$output .= self::getHtmlBody( $cid );
		$output .= self::getHtmlContent( $cid, $archive, $unsub );
		$output .= self::getHtmlFooter( $cid ); 
		
		$dom = new \DOMDocument( );
	    @$dom->loadHTML( $output );
			
		return $dom->saveHTML();		
	}	
	
	/** == == **/
	public static function getHtmlHead( $cid )
	{		
		return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">".
				"<html xmlns=\"http://www.w3.org/1999/xhtml\">".
					"<head>".
						"<meta content=\"text/html; charset=UTF-8\" http-equiv=\"Content-Type\">".
						"<meta content=\"width=device-width, initial-scale=1.0\" name=\"viewport\">".
						"<title>". self::getSubject( $cid ) ."</title>".
						"<style type=\"text/css\">". file_get_contents( dirname( __FILE__ ) . "/assets/reset.css" ) . file_get_contents( dirname( __FILE__ ) . "/assets/theme.css" ) ."</style>".
					"</head>";
	}
	
	/** == == **/
	public static function getHtmlBodyAttrs( $cid )
	{
		return "marginwidth=\"0\" marginheight=\"0\" style=\"margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#F2F2F2;height:100%!important;width:100%!important;\" offset=\"0\" topmargin=\"0\" leftmargin=\"0\"";
	}
	
	/** == == **/
	public static function getHtmlBody( $cid )
	{
		$body_attrs = self::getHtmlBodyAttrs( $cid );
		
		return "<body {$body_attrs}>";
	}
	
	/** == == **/
	public static function getHtmlContent( $cid, $archive = true, $unsub = true )
	{
		if( ! $html = \tify_db_get( 'emailing_campaign' )->select()->cell_by_id( $cid, 'content_html' ) )
			return;
		
		return self::getHtmlContentOutput( $html, $archive, $unsub );
	}
	
	/** == == **/
	public static function getHtmlContentOutput( $html, $archive = true, $unsub = true )
	{			
		$output = "";		
		$output .=	"<center>".
						"<table id=\"bodyTable\" style=\"border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust: 100%;margin:0;padding:0;background-color:#F2F2F2;width:100%!important;\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\">".
							"<tbody>".
								"<tr>".
									"<td id=\"bodyCell\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;margin:0;border-top:0;height:100% !important;width:100% !important;padding:0 !important\" valign=\"top\" align=\"center\">".
										"<!-- BEGIN TEMPLATE // -->";
		if( $archive )
			$output .= self::getHtmlArchiveArea( $html );		
		
		$output .= $html;
		
		if( $unsub )
			$output .= self::getHtmlUnsubArea( $html );
											
		$output .=						"<!-- // END TEMPLATE -->".
									"</td>".
								"</tr>".
							"</tbody>".
						"</table>".
					"</center>";
						
		return $output;
	}
	
	/** == == **/
	public static function getHtmlArchiveArea( $html )
	{
		if( ! preg_match( '/\*\|ARCHIVE\|\*/', $html, $matches ) )		
			return "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"0\" align=\"center\" style=\"border-collapse:collapse;mso-table-lspace: 0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;\">".
						"<tbody>".
							"<tr>".
								"<td style=\"padding-top: 9px;padding-right:18px;padding-bottom:9px;padding-left:18px;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;color:#606060;font-family:Helvetica;font-size:11px;line-height:125%;text-align:left;\">".
									"<div style=\"text-align: center;\">".
										"<a href=\"*|ARCHIVE|*\" style=\"font-size:11px;word-wrap:break-word;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;color:#606060;font-weight:normal;text-decoration:underline;\">".
						 					__( 'Visualiser ce mail dans votre navigateur internet', 'tify' ).
						 				"</a>".
						 			"</div>".
					 			"</td>".
					 		"</tr>".
						"</tbody>".
					"</table>";
	}
	
	/** == == **/
	public static function getHtmlUnsubArea( $html )
	{
		if( ! preg_match( '/\*\|UNSUB\|\*/', $html, $matches ) )		
			return "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"0\" align=\"center\" style=\"border-collapse: collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;\">".
						"<tbody>".
							"<tr>".
								"<td style=\"padding-top: 9px;padding-right:18px;padding-bottom:9px;padding-left:18px;mso-table-lspace:0pt;mso-table-rspace:0pt;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;color:#606060;font-family:Helvetica;font-size:11px;line-height:125%;text-align:left;\">".
									"<div style=\"text-align: center;\">".
										"<a href=\"*|UNSUB|*\" style=\"font-size:11px;word-wrap:break-word;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;color:#606060;font-weight:normal;text-decoration:underline;\">".
						 					__( 'Désinscription', 'tify' ).
						 				"</a>".
						 			"</div>".
					 			"</td>".
					 		"</tr>".
					 	"</tbody>".
					 "</table>";
	}

	/** == == **/
	public static function getHtmlFooter( $cid )
	{
		$output  = "";
		$output .= 	"</body>";
		$output .= "</html>";
		
		return $output;
	}
}