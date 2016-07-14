// wait until the page and jQuery have loaded before running the code below
jQuery(document).ready(function($){
	
	// setup our wp ajax URL
	var wpajax_url = document.location.protocol + '//' + document.location.host + '/wordpress-plugin-dev/wp-admin/admin-ajax.php';
	
	// email capture action url
	var email_capture_url = wpajax_url += '?action=bef_save_registration';
	
	$('form.bef-form').bind('submit',function(){
		
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
		
	});
	
    $('#package-1').bind('change',function(){
        qty = $( "#package-1 option:selected" ).text();
        $('#package-1-names').empty();
        $('#package-1-shirts').empty();
        $('#package-1-diets').empty();
        /*if(qty>0){
            if(qty==1){
                $('#package-1-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-1-names').append("<strong>Names:</strong>");
            }
        }*/
        for(count=0; count<qty; count++){
            $('#package-1-names').append("<input type=\"text\" name=\"package-1-names[]\" />");
            $('#package-1-shirts').append("\
                <select name=\"package-1-shirts[]\">\n\
                    <option value=\"\"></option>\n\
                    <option value=\"XS\">X-Small</option>\n\
                    <option value=\"SM\">Small</option>\n\
                    <option value=\"M\">Medium</option>\n\
                    <option value=\"L\">Large</option>\n\
                    <option value=\"XL\">X-Large</option>\n\
                    <option value=\"XXL\">XX-Large</option>\n\
                    <option value=\"3XL\">3X-Large</option>\n\
                    <option value=\"4XL\">4X-Large</option>\n\
                </select>");
            $('#package-1-diets').append("<input type=\"text\" name=\"package-1-diets[]\" value=\"\" placeholder=\"Input dietary restrictions\" />");
        }
        
        update_total();
    })
    
    $('#package-2').bind('change',function(){
        qty = $( "#package-2 option:selected" ).text();
        $('#package-2-names').empty();
        $('#package-2-shirts').empty();
        $('#package-2-diets').empty();
        /*if(qty>0){
            if(qty==1){
                $('#package-2-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-2-names').append("<strong>Names:</strong>");
            }
        }*/
        for(count=0; count<qty; count++){
            $('#package-2-names').append("<input type=\"text\" name=\"package-2-names[]\" />");
            $('#package-2-shirts').append("\
                <select name=\"package-2-shirts[]\">\n\
                    <option value=\"\"></option>\n\
                    <option value=\"XS\">X-Small</option>\n\
                    <option value=\"SM\">Small</option>\n\
                    <option value=\"M\">Medium</option>\n\
                    <option value=\"L\">Large</option>\n\
                    <option value=\"XL\">X-Large</option>\n\
                    <option value=\"XXL\">XX-Large</option>\n\
                    <option value=\"3XL\">3X-Large</option>\n\
                    <option value=\"4XL\">4X-Large</option>\n\
                </select>");
            $('#package-2-diets').append("<input type=\"text\" name=\"package-2-diets[]\" value=\"\" placeholder=\"Input dietary restrictions\" />");
        }
        
        update_total();
    })
    
    $('#package-3').bind('change',function(){
        qty = $( "#package-3 option:selected" ).text();
        $('#package-3-names').empty();
        $('#package-3-shirts').empty();
        $('#package-3-diets').empty();
        /*if(qty>0){
            if(qty==1){
                $('#package-3-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-3-names').append("<strong>Names:</strong>");
            }
        }*/
        for(count=0; count<qty; count++){
            $('#package-3-names').append("<input type=\"text\" name=\"package-3-names[]\" />");
            $('#package-3-shirts').append("\
                <select name=\"package-3-shirts[]\">\n\
                    <option value=\"\"></option>\n\
                    <option value=\"XS\">X-Small</option>\n\
                    <option value=\"SM\">Small</option>\n\
                    <option value=\"M\">Medium</option>\n\
                    <option value=\"L\">Large</option>\n\
                    <option value=\"XL\">X-Large</option>\n\
                    <option value=\"XXL\">XX-Large</option>\n\
                    <option value=\"3XL\">3X-Large</option>\n\
                    <option value=\"4XL\">4X-Large</option>\n\
                </select>");
            $('#package-3-diets').append("<input type=\"text\" name=\"package-3-diets[]\" value=\"\" placeholder=\"Input dietary restrictions\" />");
        }
        
        update_total();
    })
    
    $('#package-4').bind('change',function(){
        qty = $( "#package-4 option:selected" ).text();
        $('#package-4-names').empty();
        $('#package-4-shirts').empty();
        $('#package-4-diets').empty();
        /*if(qty>0){
            if(qty==1){
                $('#package-4-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-4-names').append("<strong>Names:</strong>");
            }
        }*/
        for(count=0; count<qty; count++){
            $('#package-4-names').append("<input type=\"text\" name=\"package-4-names[]\" />");
            $('#package-4-shirts').append("\
                <select name=\"package-4-shirts[]\">\n\
                    <option value=\"\"></option>\n\
                    <option value=\"XS\">X-Small</option>\n\
                    <option value=\"SM\">Small</option>\n\
                    <option value=\"M\">Medium</option>\n\
                    <option value=\"L\">Large</option>\n\
                    <option value=\"XL\">X-Large</option>\n\
                    <option value=\"XXL\">XX-Large</option>\n\
                    <option value=\"3XL\">3X-Large</option>\n\
                    <option value=\"4XL\">4X-Large</option>\n\
                </select>");
            $('#package-4-diets').append("<input type=\"text\" name=\"package-4-diets[]\" value=\"\" placeholder=\"Input dietary restrictions\" />");
        }
        
        update_total();
    })
    
    $('#package-5').bind('change',function(){
        qty = $( "#package-5 option:selected" ).text();
        $('#package-5-names').empty();
        $('#package-5-shirts').empty();
        $('#package-5-diets').empty();
        /*if(qty>0){
            if(qty==1){
                $('#package-5-names').append("<strong>Name:</strong>");
            }
            else{
                $('#package-5-names').append("<strong>Names:</strong>");
            }
        }*/
        for(count=0; count<qty; count++){
            $('#package-5-names').append("<input type=\"text\" name=\"package-5-names[]\" />");
            $('#package-5-shirts').append("\
                <select name=\"package-5-shirts[]\">\n\
                    <option value=\"\"></option>\n\
                    <option value=\"XS\">X-Small</option>\n\
                    <option value=\"SM\">Small</option>\n\
                    <option value=\"M\">Medium</option>\n\
                    <option value=\"L\">Large</option>\n\
                    <option value=\"XL\">X-Large</option>\n\
                    <option value=\"XXL\">XX-Large</option>\n\
                    <option value=\"3XL\">3X-Large</option>\n\
                    <option value=\"4XL\">4X-Large</option>\n\
                </select>");
            $('#package-5-diets').append("<input type=\"text\" name=\"package-5-diets[]\" value=\"\" placeholder=\"Input dietary restrictions\" />");
        }
        
        update_total();
    })
     
    function update_total(){
        qty1 = $( "#package-1 option:selected" ).text();
        qty2 = $( "#package-2 option:selected" ).text();
        qty3 = $( "#package-3 option:selected" ).text();
        qty4 = $( "#package-4 option:selected" ).text();
        qty5 = $( "#package-5 option:selected" ).text();
     
        $total = 895 * qty1 + 
                 300 * qty2 +
                 895 * qty3 +
                 100 * qty4 +
                 0 * qty5;

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