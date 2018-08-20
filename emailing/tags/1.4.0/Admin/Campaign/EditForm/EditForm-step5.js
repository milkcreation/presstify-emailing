jQuery(document).ready( function($){
	/** == ENVOI D'UN MAIL DE TEST POUR LA CAMPAGNE == */
	$( '#send-test-submit > button' ).click( function(e){
		e.preventDefault();
		
		// Déclaration des variables
		$this		= $(this);
		$closest 	= $this.closest('div');
		
		// Activation du moniteur d'activité et bloquage de saisie
		$this.addClass( 'active' );
		$( '#send-test-ok').removeClass( 'active' );
		$( 'input[type="text"]', $closest ).prop( 'readonly', true );
		
		$.ajax({
			url 		: tify_ajaxurl,
			data 		: 
			{ 
				action 			: 'tiFyPluginEmailingCampaignTestMessageSend',
				campaign_id		: $( '#campaign_id' ).val(),
				email			: $( '#send-test-email', $closest ).val()
			}, 
			success		: function( resp )
			{				
				$( '#send-test-ok').addClass( 'active' );
			},
			complete 	: function(){
				// Désactivation du moniteur d'activité et débloquage de saisie
				$this.removeClass( 'active' );
				$( 'input[type="text"]', $closest ).prop( 'readonly', false );
			},
			type		: 'post',
			dataType	: 'json'
		});
	});
	
	/** == PREPARATION DES ENVOIS == **/
	/*** === Déclaration des variables === ***/
	/* = ARGUMENTS = */
	var campaign_id = 0, 	// Identifiant de la campagne
		/// Totaux
		total 		= 0,	// Nombre total d'abonnés à traité
		processed 	= 0,	// Nombre total d'abonnés traités
		enqueue		= 0,	// Nombre total d'abonnés dans la file
				 
		recipients 	= [],	// Abonnements à traiter
		types 		= [],	// Liste des types d'abonnement à traiter
		count 		= [],	// Nombre d'abonnés par type		
		per_page 	= 100,	// Nombre d'abonné par passe 
		paged 		= 1,	// Passe courante 
		type 		= 0,	// Type d'abonnement en cours de traitement
		
		/// Réponse
		emails		= [],	// Emails des abonnés à traiter
		/// Erreurs
		duplicate	= [],	// Doublons
		invalid		= [],	// Email invalide
		
		list_id 	= 0, 
		list_index 	= 0;	
				
	/*** === Eléments du DOM === ***/
	var	$progress 		= $( '#CampaignPrepareProgress' ),
		$progressbar 	= $( '#CampaignPrepareProgress .tify_control-progress-bar' ),
		$logs			= $( '#prepare #logs' ),
		$totals			= $( '#prepare #totals' ),
		$set_send		= $( '#programmation #set_send' );		
		
	/*** === Lancement de la préparation de la campagne === ***/			
	$( '#campaign-prepare' ).click( function(e){
		e.preventDefault();
		
		// Réinitialisation globale
		reset( true, true, true, true, true );
		
		// Récupération de l'id de la campagne		
		campaign_id = $( 'input#campaign_id' ).val();
		
		// Informations de progression		
		$progressbar.css( 'background-position', '0 0' );
		$progress.addClass( 'active' );
		
		CampaignPrepareLog();
	});
	
	/*** === Lancement de la préparation de la campagne === ***/
	function CampaignPrepareLog()
	{
		$.ajax({
			url 		: tify_ajaxurl,
			data 		: 
			{ 
				action 		: 'tiFyPluginEmailingCampaignPrepareLog', 
				campaign_id : campaign_id 
			}, 
			success		: function( resp )
			{
				recipients = resp.recipients;
				types = resp.types;
				count = resp.count;
				total = resp.total;
				$( ".expected .value", $totals ).text( total );
			
				CampaignPrepareRecipients();				
			},
			type		: 'post',
			dataType	: 'json'			
		});
	}
	
	/*** === Mise en file des destinataires en base === ***/
	function CampaignPrepareRecipients()
	{
		if( processed < total ){
			$progressbar.css( 'background-position', '-'+ parseInt( ( ( processed/total )*100 ) ) +'% 0' );
					
			switch( types[type] ){
				case 'wystify_subscriber' :
					var start =  (paged-1)*per_page, end = start+per_page,					
						subscriber_ids = recipients[types[type]].slice( start, end );
					
					if( subscriber_ids.length ){
						$.ajax({
							url 		: tify_ajaxurl,
							data 		: 
							{ 
								action 			: 'tiFyPluginEmailingCampaignPrepareRecipientsSubscriber', 
								campaign_id 	: campaign_id,
								subscriber_ids	: subscriber_ids				
							}, 
							success		: function( resp )
							{
								// Traitement des valeurs de retour
								processed 	+= resp.processed;
								enqueue 	+= resp.enqueue;
								
								// Mise à jour du total des emails traités
								$( ".processed .value", $totals ).text( enqueue );
								
								// Mise en cache des erreurs de traitement
								if( resp.errors ){
									if( resp.errors.duplicate_message )
										$.each( resp.errors.duplicate_message, function(u,v){ duplicate.push(v); });
									if( resp.errors.invalid_email )
										$.each( resp.errors.invalid_email, function(u,v){ invalid.push(v); });
								}
								
								if( ! resp.processed ){
									paged = 1;
									type ++;
								} else {
									paged ++;
								}					
								
								CampaignPrepareRecipients();
							},
							type		: 'post',
							dataType	: 'json'			
						});
					} else {
						paged = 1;
						type ++;
						
						CampaignPrepareRecipients();
					}					
					break;
				case 'wystify_mailing_list' :
					var list_id = recipients[types[type]][list_index];
					if( list_id ){
						$.ajax({
							url 		: tify_ajaxurl,
							data 		: 
							{ 
								action 		: 'tiFyPluginEmailingCampaignPrepareRecipientsMailingList', 
								campaign_id : campaign_id,
								list_id		: list_id,
								paged 		: paged,
								per_page 	: per_page					
							}, 
							success		: function( resp )
							{
								// Traitement des valeurs de retour
								processed 	+= resp.processed;
								enqueue 	+= resp.enqueue;
								
								// Mise à jour du total des emails traités
								$( ".processed .value", $totals ).text( enqueue );
								
								// Mise en cache des erreurs de traitement
								if( resp.errors ){
									if( resp.errors.duplicate_message )
										$.each( resp.errors.duplicate_message, function(u,v){ duplicate.push(v); });
									if( resp.errors.invalid_email )
										$.each( resp.errors.invalid_email, function(u,v){ invalid.push(v); });
								}								
								
								if( ! resp.processed ){
									paged = 1;
									list_index++;
									if( ! recipients[types[type]][list_index] )
										type ++;
								} else {
									paged ++;
								}					
								
								CampaignPrepareRecipients();
							},
							type		: 'post',
							dataType	: 'json'			
						});
					} else {
						paged = 1;
						type ++;
						
						CampaignPrepareRecipients();
					}	
					break;
			}	
		} else {
			$progressbar.css( 'background-position', '-'+ parseInt( ( ( processed/total )*100 ) ) +'% 0' );
			// Rapport de préparation
			/// Doublons
			if( duplicate ){
				$( ".duplicates", $logs ).show();
				$( ".duplicates .total", $logs ).text( duplicate.length );
				$.each( duplicate, function( u, v ){
					$( ".duplicates > ul", $logs ).append( '<li>'+ v +'</li>' );	
				});
			}
			/// Emails invalides
			if( invalid ){
				$( ".invalids", $logs ).show();
				$( ".invalids .total", $logs ).text( invalid.length );
				$.each( invalid, function( u, v ){
					$( ".invalids > ul", $logs ).append( '<li>'+ v +'</li>' );	
				});
			}
						
			// Total des emails traités
			$( ".processed .value", $totals ).text( enqueue );
			
			// Mise à jour du status de la campagne
			$.ajax({ 
				url 		: tify_ajaxurl, 
				data 		: 
				{ 
					action 		: 'tiFyPluginEmailingCampaignPrepareStatusReady', 
					campaign_id : campaign_id, 
					enqueue 	: enqueue 
				},
				success		: function( resp )
				{
					$progressbar.css( 'background-position', '-100% 0' );
					if( resp.success ){
						$set_send.addClass( 'active' ).find('input[type="checkbox"]').prop( 'disabled', false );
						$( '#campaign_status' ).val( 'ready' );
					}
					
				},
				complete	: function()
				{
					// Réintialisation
					$progress.removeClass( 'active' );
					reset( true, true, false, false, false );
				},
				type		: 'post',
				dataType	: 'json'
			});										
		}	
	}
	
	/*** === Réinitialisation des éléments === ***/
	function reset( vars, progress, logs, totals, set_send ){
		// Argumments
		if( vars === true )
		{
			campaign_id = 0, 	// Identifiant de la campagne
			/// Totaux
			total 		= 0,	// Nombre total d'abonnés à traité
			processed 	= 0,	// Nombre total d'abonnés traités
			enqueue		= 0,	// Nombre total d'abonnés dans la file
					 
			recipients 	= [],	// Abonnements à traiter
			types 		= [],	// Liste des types d'abonnement à traiter
			count 		= [],	// Nombre d'abonnés par type		
			per_page 	= 100,	// Nombre d'abonné par passe 
			paged 		= 1,	// Passe courante 
			type 		= 0,	// Type d'abonnement en cours de traitement
			
			/// Réponse
			emails		= [],	// Emails des abonnés à traiter
			duplicates	= [],	// Doublons
			
			list_id 	= 0, 
			list_index 	= 0;
		}
		
		// Barre de progression
		if( progress === true )
		{
			$progress.removeClass( 'active' );
			$( '.progress-bar', $progress ).css( 'width', 0 );
			$( '.text-bar .current', $progress ).text('');
			$( '.text-bar .total', $progress ).text('');
			$( 'infos', $progress ).text('');
		}
		
		// Logs
		if( logs === true )
		{
			$( '> *', $logs ).hide();
			$( '.total', $logs ).each( function(){ $(this).text(''); });
			$( 'ul', $logs ).empty();
		}
		
		// Totaux
		if( totals === true )
		{
			$( '.value', $totals ).each( function(){ $(this).text(''); });
		}
		
		// 
		if( set_send === true )
		{
			$set_send.removeClass( 'active' ).find( 'input[type="checkbox"]' ).prop( 'disabled', 'disabled' ).prop( 'checked', false );
		}
	}
});