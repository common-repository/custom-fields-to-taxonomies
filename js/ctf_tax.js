jQuery(document).ready(function($){
	
	$('#ctf_key').change(function() {
		$('#start_convert').attr('disabled','disabled');
		$('#post_ids').val('');
		$('#post_ids_completed').val('');
		$('#ctf_tax_convert .spinner').addClass('is-active');
		$('#ctf_tax_convert .log').html();
		
		if( $(this).val() != '' ) {
			$.ajax({
				type : 'POST',
				data : {
				   'action' : 'ctf_get_posts_count',
				   'ctf_key' :  $(this).val()
				},
				url : ctf.ajax_url,
				success : function (result) {
				   $('.meta_key_count').addClass('show');
				   $('#post_count').html(result);
				   $('#start_convert').removeAttr('disabled');
				   $('#ctf_tax_convert .spinner').removeClass('is-active');
				},
				error: function (xhr, ajaxOptions, thrownError) {
					$('#ctf_tax_convert .log').html( xhr.responseText );
				}
			}).fail( function(jqXHR, textStatus, errorThrown ){
				alert("Got some error: " + errorThrown);
				ctf_clear();
			});

		}
	});
	
	$('#ctf_tax_convert').submit(function(e){
		e.preventDefault();
		_form = $(this);
		_form.find('.spinner').addClass('is-active');
		_form.find('#ctf_offset').attr('disabled','disabled');
		_form.find('#ctf_key').attr('disabled','disabled');
		_form.find('#ctf_tax').attr('disabled','disabled');
		_form.find('#ctf_separate').attr('disabled','disabled');
		_form.find('#start_convert').attr('disabled','disabled');
		
		if( _form.find('#ctf_key').val() != '' && _form.find('#ctf_tax').val() != '' ) {
			$.ajax({
				type : 'POST',
				data : {
				   'action' : 'ctf_get_post_ids',
				   'ctf_key' :  _form.find('#ctf_key').val()
				},
				url : ctf.ajax_url,
				success : function (result){				   
				   $('#post_ids').val( result );
				   ctf_ajax_converting();
				},
				error: function (xhr, ajaxOptions, thrownError) {
					$('#ctf_tax_convert .log').html( xhr.responseText );
				}
			}).fail( function(jqXHR, textStatus, errorThrown ){
				alert("Got some error: " + errorThrown);
				ctf_clear();
			});
		}
	});
	
	$('.ctf_continue_convert').click(function(e) {
		e.preventDefault();
		var _text = $(this).text();
		_num = _text.substring(0, _text.indexOf("/"));
		$('#ctf_key').val( $(this).attr('data-key') ).trigger('change');
		$('#ctf_tax').val( $(this).attr('data-tax') );
		$('#ctf_offset').val( _num );
	});
	
});

function ctf_ajax_converting() {
	$ = jQuery;
	var _key = $('#ctf_key').val();
	var _tax = $('#ctf_tax').val();
	var _separate = $('#ctf_separate').val();
	var _ids = $('#post_ids').val().split(',');
	var _count_completed = $('#post_count_completed').val() != '' ? parseInt( $('#post_count_completed').val() ) : parseInt( $('#ctf_offset').val() );
	var _start = _count_completed;
	
	if( _ids.length > _count_completed ) {
		
		_post_ids = _ids.slice( _start, _start + 10);
		_count_completed = _start + _post_ids.length;
		
		$.ajax({
			type : 'POST',
			data : {
			   'action' : 'ctf_convert_ctf',
			   'ctf_key' :  _key,
			   'ctf_tax' :  _tax,
			   'ctf_separate' :  _separate,
			   'post_ids' :  _post_ids.join(","),
			   'number_completed' :  _count_completed,
			   'count' :  _ids.length,
			},
			url : ctf.ajax_url,
			success : function (result){
				if( _count_completed < _ids.length ) {
					$('#ctf_tax_convert .log').html( 'Posts Completed: '+ _count_completed + '/' + _ids.length );
					$('#post_count_completed').val( _count_completed );
					ctf_ajax_converting();
				}
				else {
					$.ajax({
						type : 'POST',
						data : {
						   'action' : 'ctf_update_history',
						   'ctf_key' :  _key,
						   'ctf_tax' :  _tax,
						   'ctf_separate' :  _separate,
						   'post_ids' :  _post_ids.join(","),
						   'number_completed' :  _count_completed,
						   'count' :  _ids.length,
						},
						url : ctf.ajax_url,
						success : function (result){
							$('#ctf_tax_convert .log').html( 'Done!!' );
							ctf_clear();
						}
					});
				}
			},
			error: function (xhr, ajaxOptions, thrownError) {
				$('#ctf_tax_convert .log').html( xhr.responseText );
			}
		}).fail( function(jqXHR, textStatus, errorThrown ){
			alert("Got some error: " + errorThrown);
			ctf_clear();
		});
	}
	 
}

function ctf_clear() {
	$ = jQuery;
	$('#ctf_key').val('');
	$('#ctf_tax_convert .spinner').removeClass('is-active');
	$('#ctf_key').removeAttr('disabled');
	$('#ctf_tax').removeAttr('disabled');
	$('#start_convert').removeAttr('disabled');
	$('#ctf_separate').removeAttr('disabled');
}