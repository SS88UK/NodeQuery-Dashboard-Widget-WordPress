function NQ_ss88_dobars()
{
	jQuery('#ss88_NodeQuery .nq-bar-percentage[data-percentage]').each(function () {
	var progress = jQuery(this);
	var whatp = jQuery(this).attr('data-percentage');
	
	if(whatp=='U')
	{
		progress.text('UNLIM');
	}
	else
	{
	
		var percentage = Math.ceil(jQuery(this).attr('data-percentage'));
		jQuery({countNum: 0}).animate({countNum: percentage}, {
		duration: 2000,
		easing:'linear',
		step: function() {
							var pct = Math.floor(this.countNum) + '%';
							progress.text(pct) && progress.siblings().children().css('width',pct);
							if(this.countNum>79) { progress.parent().find('.bar').css('background-color', '#d03a3a'); }
						}
		});
	}
	});
}

function NQ_hookSubmitform() {
jQuery('.ss88_vw_form_nq').submit(function(e) {
	
	e.preventDefault();
	
	jQuery('#ss88_NodeQuery .ss88_spinner').show();
	
	var th = jQuery(this);
	
	var posting = jQuery.post( th.attr('action'), { action: 'ss88_NodeQuery_widget_ajax', nonce: jQuery('input[name=ss88_NodeQuery_widget_nonce]').val(), apikey: jQuery('input[name=ss88_NodeQuery_widget_key]').val() } );
	
	jQuery('.ss88_vw_form_nq input[type=submit]').attr('disabled', true);
	
	posting.done(function( data ) {
		
		jQuery('#ss88_NodeQuery .welcome-panel-content').html(data);
		jQuery('.ss88_vw_form_nq input[type=submit]').attr('disabled', false);
		jQuery('#ss88_NodeQuery .ss88_spinner').hide();
		jQuery("#ss88_NodeQuery.welcome-panel").addClass("loaded");
		NQ_ss88_dobars();
		
	});
	
});
}

jQuery(document).ready(function(){

jQuery('#welcome-panel').after(jQuery('#ss88_NodeQuery').show());
NQ_hookSubmitform();
NQ_ss88_dobars();

});