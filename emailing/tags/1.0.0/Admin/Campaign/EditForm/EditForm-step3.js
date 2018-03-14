jQuery(document).ready( function($){
	/** == ETAPE #3 - Choix des destinataires == **/
	/*** === Recherche par autocompletion === ***/
	var name,
		type, 
		list = '#recipients-list', 
		total = 0;
		
	 $( '#recipient-search' ).on( "autocompleteselect", function( event, ui ) {
	 	event.preventDefault();
	
	 	$( list ).append( ui.item.selected );
		$( 'input', this ).val('');
		
		update_recipients_total();
	 });

	/** == Suppression d'un items == **/
	$(document).on('click', list +'> li > .remove', function(e){
		e.preventDefault();	
		$(this).parent().fadeOut( function(){
			$(this).remove();
			update_recipients_total();
		});
	});
	/** ==  Mise à jour du nombre de destinataires == **/
	function update_recipients_total(){
		total = 0;
		$( list +' > li' ).each( function(){
			total += parseInt( $(this).data( 'numbers') );
		});
		$( '#recipients-total > .value' ).html( total );
	}
});