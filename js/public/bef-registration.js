// wait until the page and jQuery have loaded before running the code below
jQuery(document).ready(function($){
	
	// setup our wp ajax URL
	var wpajax_url = document.location.protocol + '//' + document.location.host + '/wordpress-plugin-dev/wp-admin/admin-ajax.php';
	
	// email capture action url
	var email_capture_url = wpajax_url += '?action=bef_save_registration';
	
	/*$('form.bef-form').bind('submit',function(){
		
		// get the jquery form object
		$form = $(this);
		
		// setup our form data for our ajax post
		var form_data = $form.serialize();
		
		// submit our form data with ajax
		$.ajax({
			'method':'post',
			'url':email_capture_url,
			'data':form_data,
			'dataType':'json',
			'cache':false,
			'success': function( data, textStatus ) {
				if( data.status == 1 ) {
					// success
					// reset the form
					$form[0].reset();
					// notify the user of success
					alert(data.message);
				} else {
					// error
					// begin building our error message text
					var msg = data.message + '\r' + data.error + '\r';
					// loop over the errors
					$.each(data.errors,function(key,value){
						// append each error on a new line
						msg += '\r';
						msg += '- '+ value;
					});
					// notify the user of the error
					alert( msg );
				}
			},
			'error': function( jqXHR, textStatus, errorThrown ) {
				// ajax didn't work
			}
			
		});
		
		// stop the form from submitting normally
		return false;
		
	});*/
	
    $('#package-1').bind('change',function(){
        qty = $( "#package-1 option:selected" ).text();
        $('#package-1-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-1-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-1-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-1-names').append("<input type=\"text\" name=\"package-1-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-2').bind('change',function(){
        qty = $( "#package-2 option:selected" ).text();
        $('#package-2-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-2-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-2-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-2-names').append("<input type=\"text\" name=\"package-2-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-3').bind('change',function(){
        qty = $( "#package-3 option:selected" ).text();
        $('#package-3-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-3-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-3-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-3-names').append("<input type=\"text\" name=\"package-3-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-4').bind('change',function(){
        qty = $( "#package-4 option:selected" ).text();
        $('#package-4-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-4-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-4-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-4-names').append("<input type=\"text\" name=\"package-4-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-5').bind('change',function(){
        qty = $( "#package-5 option:selected" ).text();
        $('#package-5-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-5-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-5-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-5-names').append("<input type=\"text\" name=\"package-5-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-6').bind('change',function(){
        qty = $( "#package-6 option:selected" ).text();
        $('#package-6-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-6-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-6-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-6-names').append("<input type=\"text\" name=\"package-6-names[]\" />");
        }
        
        update_total();
    })
    
    $('#package-7').bind('change',function(){
        qty = $( "#package-7 option:selected" ).text();
        $('#package-7-names').empty();
        if(qty>0){
            if(qty==1){
                $('#package-7-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-7-names').append("<strong>Names:</strong>");
            }
        }
        for(count=0; count<qty; count++){
            $('#package-7-names').append("<input type=\"text\" name=\"package-7-names[]\" />");
        }
        
        update_total();
    })
    
    
    function update_total(){
        qty1 = $( "#package-1 option:selected" ).text();
        qty2 = $( "#package-2 option:selected" ).text();
        qty3 = $( "#package-3 option:selected" ).text();
        qty4 = $( "#package-4 option:selected" ).text();
        qty5 = $( "#package-5 option:selected" ).text();
        qty6 = $( "#package-6 option:selected" ).text();
        qty7 = $( "#package-7 option:selected" ).text();
        
        $total = 895 * qty1 + 
                 895 * qty2 +
                 150 * qty3 +
                 895 * qty4 +
                 150 * qty5 +
                 0 * qty6 +
                 1000 * qty7;

        $('#total-amount').empty();
        $('#total-amount').append("$" + $total.toFixed(2) + "<input type=\"hidden\" name=\"total-amount\" value=\"" + $total.toFixed(2) + "\">");
    
        return $total.toFixed(2);
    }
    
    $(window).bind("pageshow", function() {
        var form = $('form.bef-form'); 
        // let the browser natively reset defaults
        form[0].reset();
    });
});