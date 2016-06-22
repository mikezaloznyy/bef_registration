<?php
/*
Plugin Name: BEF Registration
Plugin URI: http://www.thebusinessexcellenceforums.com/
Description: Registration and payment portal for the Business Excellence Forum and Awards website.
Version: 1.0
Author: Mike Zaloznyy 
Author URI: mike.zaloznyy@gmail.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: BEF Registration
*/

/* !0. TABLE OF CONTENTS */

/*
	
	1. HOOKS
	   1.1 - registers all our custom shortcodes
       1.2 - register custom admin column headers
       1.3 - register custom admin column data
       1.4 - register ajax actions
       1.5 - load external files to public website
       
	2. SHORTCODES
       2.1 - bef_register_shortcodes()
       2.2 - bef_form_shortcode()
        
	3. FILTERS
	   3.1 - bef_registrant_column_headers()
       3.2 - bef_registrant_column_data()
       3.2.2 - bef_register_custom_admin_titles()
       3.2.3 - bef_custom_admin_titles()
       3.3 - bef_event_column_headers()
       3.4 - bef_event_column_data()
       
	4. EXTERNAL SCRIPTS
       4.1 - bef_public_scripts()
        
	5. ACTIONS
       5.1 - bef_save_registration()
       5.2 - bef_save_registrant()
       5.3 - bef_add_registration()
       
	6. HELPERS
       6.1 - bef_registrant_has_registration()
       6.2 - bef_get_registrant_id()
       6.3 - bef_get_registrations()
       6.4 - bef_return_json()
       6.5 - bef_get_acf_key()
       6.6 - bef_get_registrant_data()
       
	7. CUSTOM POST TYPES
	
	8. ADMIN PAGES
	
	9. SETTINGS

*/

/* !1. HOOKS */

// 1.1
// hint: registers all our custom shortcodes on init
add_action('init', 'bef_register_shortcodes');


// 1.2
// hint: register custom admin column headers
add_filter('manage_edit-bef_registrant_columns','bef_registrant_column_headers');
add_filter('manage_edit-bef_event_columns','bef_event_column_headers');

// 1.3
// hint: register custom admin column data
add_filter('manage_bef_registrant_posts_custom_column','bef_registrant_column_data',1,2);
add_action(
    'admin_head-edit.php',
    'bef_register_custom_admin_titles'
);
add_filter('manage_bef_event_posts_custom_column', 'bef_event_column_data',1,2);

// 1.4
// hint: register ajax actions
add_action('wp_ajax_nopriv_bef_save_registration', 'bef_save_registration'); // regular website visitor
add_action('wp_ajax_bef_save_registration', 'bef_save_registration'); // admin user

// 1.5
// load external files to public website
add_action('wp_enqueue_scripts', 'bef_public_scripts');

/* !2. SHORTCODES */

// 2.1
// hint: registers all our custom shortcodes
function bef_register_shortcodes() {
	
	add_shortcode('bef_form', 'bef_form_shortcode');
	
}

// 2.2
// hint: returns a html string for a email capture form
function bef_form_shortcode( $args, $content="") {
	// get the event id
	$event_id = 0;
	if( isset($args['id']) ) $event_id = (int)$args['id'];
    
    // title
	$title = '';
	if( isset($args['title']) ) $title = (string)$args['title'];
    
	// setup our output variable - the form html 
	$output = '
	
		<div class="bef">
			<form id="bef_form" name="bef_form" class="bef-form" method="post" action="/wordpress-plugin-dev/wp-admin/admin-ajax.php?action=bef_save_registration">
                <input type="hidden" name="bef_event" value="'. $event_id .'">';
                
                if( strlen($title) ):
					$output .= '<h3 class="bef-title">'. $title .'</h3>';				
				endif;
    
                $output .= '<!-- Contact details: name, email, phone, etc. -->
			    <fieldset>
                    <legend>Contact Details:</legend>
                    <p class="bef-input-container">
                        <label>Your Name</label><br />
                        <input type="text" name="bef_fname" placeholder="First Name" />
                        <input type="text" name="bef_lname" placeholder="Last Name" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Company</label><br />
                        <input type="text" name="bef_company" placeholder="Company" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Business Phone</label><br />
                        <input type="tel" name="bef_business_phone" placeholder="Business Phone Number" />
                    </p>
                                        
                    <p class="bef-input-container">
                        <label>Mobile Phone</label><br />
                        <input type="tel" name="bef_mobile_phone" placeholder="Mobile Phone Number" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Your Email</label><br />
                        <input type="email" name="bef_email" placeholder="ex. you@email.com" />
                    </p>
                </fieldset>
                
                <!-- Registration details: number of attendees, attendee names, shirt sizes -->
                <fieldset>
                    <legend>Registration Details:</legend>
                    <p class="bef-input-container">
                        <label>Number of attendees</label><br />
                        <select name="bef_num_of_attendees">';
                for($count=1; $count<=10; $count++){
                     $output .= '<option value='.$count.'>'.$count.'</option>';
                }
                            
                $output .= '            
                        </select>
                    </p>
                    <p class="bef-input-container">
                        <label>Names of attendees</label><br/>';
                for($count=1; $count<=10; $count++){
                    $output .= '<input type="text" name="bef_attendee_names[]" placeholder="Attendee # ' . $count . ' name" />';
                }    
                    
                
                $output .= '</p><p class="bef-input-container">
                    <label>Shirt Size</label><br />
                    <select name="shirt_size">
                        <option value="XS">XS</option>
                        <option value="SM">SM</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="3X">3X</option>
                        <option value="4X">4X</option>
                    </select>
                
                </fieldset>
                
                <!-- Choose role and amount -->
                <fieldset>
                    <legend>Choose Product and Role:</legend>
                    <p class="bef-input-container">
                        <label>Product and Role</label>
                        <select name="package">
                            <option value="">Choose One:</option>
                            <option value="Licensed Coach|Full|895">Licensed Coach: Full BEF Conference, Awards Gala, Coach Conference - $895.00</option>
                            <option value="Licensed Coach|BEF Only|895">Licensed Coach: BEF Only - $895.00</option>
                            <option value="Licensed Coach|Social Ticket|150">Licensed Coach Social Ticket: Welcome Reception & Awards Gala - $150.00</option>
                            <option value="Client|Full|895">Client/Other: Full BEF Conference & Awards Gala - $895.00</option>
                            <option value="Client|Social Ticket|150">Client/Other: Welcome Reception & Awards Gala - $150.00</option>
                            <option value="Hall Of Fame|Hall Of Fame|0.00">Hall of Fame - FREE</option>
                            <option value="Sponsor|Sponsor|1000">Sponsor Event (exhibit table for 2 days, logo visibility at event and website) - $1000.00</option>
                        </select>
                    </p>
                </fieldset>
                
                <!-- Billing info: cc information etc -->
                <fieldset>
                    <legend>Billing Details:</legend>
                    <p class="bef-input-container">
                        <label>Billing Address</label>
                        <input type="text" name="bef_billing_address_1" placeholder="Billing Address 1" />
                        <input type="text" name="bef_billing_address_2" placeholder="Billing Address 2 (Apt #, Suite # etc.)" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Billing City</label>
                        <input type="text" name="bef_billing_city" placeholder="Billing City, e.g. Las Vegas" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Billing State</label>
                        <input type="text" name="bef_billing_state" placeholder="Billing State, e.g. NV" />
                    </p>
                </fieldset>
                
                <!-- Credit card details -->
                <fieldset>
                    <legend>Credit Card Details:</legend>
                    <p class="bef-input-container">
                        <label>Credit Card Number</label>
                        <input type="text" name="bef_cc_num" pattern="[0-9]{13,16}" placeholder="Credit Card number">
                    </p>
                    
                    <p class="bef-input-container">
                        <label>CC Expiration Month</label>
                        <select name="bef_cc_month">
                            <option value="01">01</option>
                            <option value="02">02</option>
                            <option value="03">03</option>
                            <option value="04">04</option>
                            <option value="05">05</option>
                            <option value="06">06</option>
                            <option value="07">07</option>
                            <option value="08">08</option>
                            <option value="09">09</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                        <br/>
                        <label>CC Expiration Year</label>
                        <select name="bef_cc_year">
                            <option value="2016">2016</option>
                            <option value="2017">2017</option>
                            <option value="2018">2018</option>
                            <option value="2019">2019</option>
                            <option value="2020">2020</option>
                            <option value="2021">2021</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                        </select>
                    </p>
                    
                    <p class="bef-input-container">
                        <label>CC Card Code</label>
                        <input type="text" name="bef_cc_code" placeholder="Credit Card Card Code">
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Split Payment?<br/>
                        <small>First payment charged today if split.<br/>
                        Recurring payments are charged on the first of the month.</small></label><br/>
                        <select name="bef_split_payment">
                            <option value="full_amount">Do not split - pay full amount</option>
                            <option value="2">Split into two monthly payments</option>
                            <option value="3">Split into three monthly payments</option>
                            <option value="4">Split into four monthly payments</option>
                        </select>
                    </p>
                </fieldset>
                ';
				
        
				// including content in our form html if content is passed into the function
				if( strlen($content) ):
				
					$output .= '<div class="bef-content">'. wpautop($content) .'</div>';
				
				endif;
				
				// completing our form html
				$output .= '</p><p class="bef-input-container">
					<input type="submit" name="bef_submit" value="Register!" />
				</p>
			</form>
		</div>
	';
	
	// return our results/html
	return $output;
	
}

/* !3. FILTERS */

// 3.1
function bef_registrant_column_headers( $columns ) {
	
	// creating custom column header data
	$columns = array(
		'cb'=>'<input type="checkbox" />',
		'title'=>__('Registrant Name'),
		'email'=>__('Email Address'),	
	);
	
	// returning new columns
	return $columns;
}

// 3.2
function bef_registrant_column_data( $column, $post_id ) {
	
	// setup our return text
	$output = '';
	
	switch( $column ) {
		
		case 'title':
			// get the custom name data
			$fname = get_field('bef_fname', $post_id );
			$lname = get_field('bef_lname', $post_id );
			$output .= $fname .' '. $lname;
			break;
		case 'email':
			// get the custom email data
			$email = get_field('bef_email', $post_id );
			$output .= $email;
			break;
		
	}
	
	// echo the output
	echo $output;
	
}

// 3.2.2
// hint: registers special custom admin title columns
function bef_register_custom_admin_titles() {
    add_filter(
        'the_title',
        'bef_custom_admin_titles',
        99,
        2
    );
}

// 3.2.3
// hint: handles custom admin title "title" column data for post types without titles
function bef_custom_admin_titles( $title, $post_id ) {
   
    global $post;
	
    $output = $title;
   
    if( isset($post->post_type) ):
                switch( $post->post_type ) {
                        case 'bef_registrant':
	                            $fname = get_field('bef_fname', $post_id );
	                            $lname = get_field('bef_lname', $post_id );
	                            $output = $fname .' '. $lname;
	                            break;
                }
        endif;
   
    return $output;
}

// 3.3
function bef_event_column_headers( $columns ) {
	
	// creating custom column header data
	$columns = array(
		'cb'=>'<input type="checkbox" />',
		'title'=>__('Event Name'),	
        'shortcode'=>__('Shortcode'),
	);
	
	// returning new columns
	return $columns;
	
}

// 3.4
function bef_event_column_data( $column, $post_id ) {
	
	// setup our return text
	$output = '';
	
	switch( $column ) {
		
		case 'shortcode':
			$output .= '[bef_form id="'. $post_id .'"]';
			break;
		
	}
	
	// echo the output
	echo $output;
	
}

/* !4. EXTERNAL SCRIPTS */
// 4.1
// hint: loads external files into PUBLIC website
function bef_public_scripts() {
	
	// register scripts with WordPress's internal library
	wp_register_script('bef-registration-js-public', plugins_url('/js/public/bef-registration.js',__FILE__), array('jquery'),'',true);
	wp_register_style('bef-registration-css-public', plugins_url('/css/public/bef-registration.css',__FILE__));
    
	// add to que of scripts that get loaded into every page
	wp_enqueue_script('bef-registration-js-public');
	wp_enqueue_style('bef-registration-css-public');
}

/* !5. ACTIONS */

// 5.1
// hint: saves bef registration data to an existing or new BEF registrant
function bef_save_registration() {
	
	// setup default result data
	$result = array(
		'status' => 0,
		'message' => 'Registration was not saved. ',
        'error'=>'',
		'errors'=>array()
	);
	
	try {
		
		// get event_id
		$event_id = (int)$_POST['bef_event'];
	
		// prepare registrant data
		$registrant_data = array(
			'fname'=> esc_attr( $_POST['bef_fname'] ),
			'lname'=> esc_attr( $_POST['bef_lname'] ),
			'email'=> esc_attr( $_POST['bef_email'] ),
		);
		
        // setup our errors array
		$errors = array();
        
        // form validation
		if( !strlen( $registrant_data['fname'] ) ) $errors['fname'] = 'First name is required.';
        if( !strlen( $registrant_data['lname'] ) ) $errors['lname'] = 'Last name is required.';
		if( !strlen( $registrant_data['email'] ) ) $errors['email'] = 'Email address is required.';
		if( strlen( $registrant_data['email'] ) && !is_email( $registrant_data['email'] ) ) $errors['email'] = 'Email address must be valid.';
        
        // IF there are errors
		if( count($errors) ):
		
			// append errors to result structure for later use
			$result['error'] = 'Some fields are still required. ';
			$result['errors'] = $errors;
        
        else:
        // IF there are no errors, proceed...
            // attempt to create/save registrant
            $registrant_id = bef_save_registrant( $registrant_data );

            // IF registrant was saved successfully $registrant_id will be greater than 0
            if( $registrant_id ):

                // IF $registrant_id already signed up for this event
                if( bef_registrant_has_registration( $registrant_id, $event_id ) ):

                    // get event object
                    $event = get_post( $event_id );

                    // return detailed error
                    $result['message'] .= esc_attr( $registrant_data['email'] .' is already registered for '. $event->post_title .'.');

                else: 

                    // save new registration
                    $registration_saved = bef_add_registration( $registrant_id, $event_id );

                    // IF registration was saved successfully
                    if( $registration_saved ):

                        // registration saved!
                        $result['status']=1;
                        $result['message']='Registration saved';

                    else: 			
						// return detailed error
						$result['error'] = 'Unable to save subscription.';
        
                    endif;
                endif;
            endif;
		endif;
		
	} catch ( Exception $e ) {
		
	}
	
	// return result as json
	bef_return_json($result);
	
}

// 5.2
// hint: creates a new registrant or updates an existing one
function bef_save_registrant( $registrant_data ) {
	
	// setup default registrant id
	// 0 means the registrant was not saved
	$registrant_id = 0;
	
	try {
		
		$registrant_id = bef_get_registrant_id( $registrant_data['email'] );
		
		// IF the registrant does not already exists...
		if( !$registrant_id ):
		
			// add new registrant to database	
			$registrant_id = wp_insert_post( 
				array(
					'post_type'=>'bef_registrant',
					'post_title'=>$registrant_data['fname'] .' '. $registrant_data['lname'],
					'post_status'=>'publish',
				), 
				true
			);
		
		endif;
		
		// add/update custom meta data
		update_field(bef_get_acf_key('bef_fname'), $registrant_data['fname'], $registrant_id);
		update_field(bef_get_acf_key('bef_lname'), $registrant_data['lname'], $registrant_id);
		update_field(bef_get_acf_key('bef_email'), $registrant_data['email'], $registrant_id);
		
	} catch( Exception $e ) {
		
		// a php error occurred
		
	}
	
	// return registrant_id
	return $registrant_id;
	
}

// 5.3
// hint: adds event to registrant registrations
function bef_add_registration( $registrant_id, $event_id ) {
	
	// setup default return value
	$registration_saved = false;
	
	// IF the registration does NOT have the current event registration
	if( !bef_registrant_has_registration( $registrant_id, $event_id ) ):
	
		// get registrations and append new $event_id
		$registrations = bef_get_registrations( $registrant_id );
		$registrations[]=$event_id;
		
		// update bef_registrations
		update_field( bef_get_acf_key('bef_registrations'), $registrations, $registrant_id ); //@ToDo
		
		// registrations updated!
		$registration_saved = true;
	
	endif;
	
	// return result
	return $registration_saved;
	
}

/* !6. HELPERS */
// 6.1
// hint: returns true or false
function bef_registrant_has_registration( $registrant_id, $event_id ) {
	
	// setup default return value
	$has_event = false;
	
	// get registrant
	$registrant = get_post($registrant_id);
	
	// get event registrations
	$registrations = bef_get_registrations( $registrant_id );
	
	// check registrations for $event_id
	if( in_array($event_id, $registrations) ):
	
		// found the $event_id in $registrations
		// this registrant is already registered to this event
		$has_event = true;
	
	else:
	
		// did not find $event_id in $registrations
		// this registrant is not yet registered to this event
	
	endif;
	
	return $has_event;
	
}

// 6.2
// hint: retrieves a registrant_id from an email address
function bef_get_registrant_id( $email ) {
	
	$registrant_id = 0;
	
	try {
	
		// check if registrant already exists
		$registrant_query = new WP_Query( 
			array(
				'post_type'		=>	'bef_registrant',
				'posts_per_page' => 1,
				'meta_key' => 'bef_email',
				'meta_query' => array(
				    array(
				        'key' => 'bef_email',
				        'value' => $email,  // or whatever it is you're using here
				        'compare' => '=',
				    ),
				),
			)
		);
		
		// IF the registrant exists...
		if( $registrant_query->have_posts() ):
		
			// get the registrant_id
			$registrant_query->the_post();
			$registrant_id = get_the_ID();
			
		endif;
	
	} catch( Exception $e ) {
		
		// a php error occurred
		
	}
		
	// reset the Wordpress post object
	wp_reset_query();
	
	return (int)$registrant_id;
	
}

// 6.3
// hint: returns an array of event_id's
function bef_get_registrations( $registrant_id ) {
	
	$registrations = array();
	
	// get registrations (returns array of event objects)
	$events = get_field( bef_get_acf_key('bef_registrations'), $registrant_id ); //@ToDo - check the acf_key
	
	// IF $events returns something
	if( $events ):
	
		// IF $events is an array and there is one or more items
		if( is_array($events) && count($events) ):
			// build registrations: array of event id's
			foreach( $events as &$event):
				$registrations[]= (int)$event->ID;
			endforeach;
		elseif( is_numeric($events) ):
			// single result returned
			$registrations[]= $events;
		endif;
	
	endif;
	
	return (array)$registrations;
	
}

// 6.4
function bef_return_json( $php_array ) {
	
	// encode result as json string
	$json_result = json_encode( $php_array );
	
	// return result
	die( $json_result );
	
	// stop all other processing 
	exit;
	
}

//6.5
// hint: gets the unique act field key from the field name
function bef_get_acf_key( $field_name ) {
	
	$field_key = $field_name;
	
	switch( $field_name ) {
		
		case 'bef_fname':
			$field_key = 'field_5769c88e7c962';
			break;
		case 'bef_lname':
			$field_key = 'field_5769c8dd7c963';
			break;
		case 'bef_email':
			$field_key = 'field_5769cacdceb92';
			break;
		case 'bef_registrations':
			$field_key = 'field_5769caf3ceb93';
			break;
		
	}
	
	return $field_key;
	
}

// 6.6
// hint: returns an array of registrant data including registrations
function bef_get_registrant_data( $registrant_id ) {
	
	// setup registrant_data
	$registrant_data = array();
	
	// get registrant object
	$registrant = get_post( $registrant_id );
	
	// IF registrant object is valid
	if( isset($registrant->post_type) && $registrant->post_type == 'bef_registrant' ):
	
		$fname = get_field( bef_get_acf_key('bef_fname'), $registrant_id);
		$lname = get_field( bef_get_acf_key('bef_lname'), $registrant_id);
	
		// build registrant_data for return
		$registrant_data = array(
			'name'=> $fname .' '. $lname,
			'fname'=>$fname,
			'lname'=>$lname,
			'email'=>get_field( bef_get_acf_key('bef_email'), $registrant_id),
			'registrations'=>bef_get_registrations( $registrant_id )
		);
		
	
	endif;
	
	// return registrant_data
	return $registrant_data;
	
}

/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */