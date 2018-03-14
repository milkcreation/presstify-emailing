jQuery(document).ready( function($){
	var wisify_message_update_xhr;
	$('body').on( 'click', '.report_update', function(e){
		e.preventDefault();
		
		var report_id = $(this).data( 'report_id' );
		var ajax_nonce = $(this).data( 'ajax_nonce' );
		var $closest = $(this).closest( 'tr' );
		$closest.css( { 'position' : 'relative' } );
		$( ' > td:first', $closest ).append( '<div class="overlay" style="position:absolute; top:0; right:0; left:0; bottom:0; background-color:rgba( 255, 255, 255, 0.5 ); z-index:1;"><span class="spinner" style="position:absolute; top:50%; right:5px; margin-top:-10px; z-index:2; visibility:visible;"></span></div>' );
		wisify_message_update_xhr = $.ajax({ 
			url 		: tify_ajaxurl,
			data		: { action : 'wistify_report_update', report_id : report_id, _ajax_nonce : ajax_nonce },
			type		: 'post',
			success		: function( resp ){
				$closest.replaceWith( resp );
				$('[data-toggle="popover"]').popover();
			},
			dataType	: 'html'
		});
	});
	$('[data-toggle="popover"]').popover();
});
