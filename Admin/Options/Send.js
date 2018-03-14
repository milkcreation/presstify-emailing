jQuery(document).ready( function($){
	/** == ENVOI D'UN MAIL DE TEST POUR LA CAMPAGNE == */
	$( '#send-test-submit > button' ).click( function(e){
		e.preventDefault();
		
		// Déclaration des variables
		$this		= $(this);
		$closest 	= $this.closest('div');
		
		$( '#send-test-ok').removeClass( 'active' );
		
		// Activation du moniteur d'activité et bloquage de saisie
		$this.addClass( 'active' );
		$( 'input[type="text"]', $closest ).prop( 'readonly', true );
		
		$.ajax({
			url 		: tify_ajaxurl,
			data 		: 
			{ 
				action 			: 'tiFyPluginEmailingOptionsTestMessageSend',
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
	
	$( '#SendEngine > .TabNav > li > label > input[type="radio"]' ).change( function(){
		$( $(this).data( 'target' ) ).addClass( 'active' ).siblings().removeClass( 'active' );
	})
});