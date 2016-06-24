<?php

require 'vendor/autoload.php';
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
date_default_timezone_set('America/Los_Angeles');
define("AUTHORIZENET_LOG_FILE", "phplog");

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
       1.6 - Advanced Custom Fields Settings
       
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
       4.1 - include advanced-custom-fields
       4.2 - bef_public_scripts()
        
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
	   7.1 - registrants
       
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

// 1.6
// Advanced Custom Fields Settings
/*
add_filter('acf/settings/path', 'bef_acf_settings_path');
add_filter('acf/settings/dir', 'bef_acf_settings_dir');
add_filter('acf/settings/show_admin', 'bef_acf_show_admin');
//if( !defined('ACF_LITE') ) define('ACF_LITE',true); // turn off ACF plugin menu
*/

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
	
		<div class="bef" id="wrap-form-div">
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
                <!--<fieldset>
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
                
                </fieldset>-->
                
                <!-- Choose role and amount -->
                <fieldset>
                    <legend>Choose Products</legend>
                    <label>Packages and Quantities</label>
                    <p class="bef-input-container">
                        <table class="product-table">
                            <!-- package 1 -->
                            <tr>
                                <td width="47%">Licensed Coach: Full BEF Conference, Awards Gala, Coach Conference</td>
                                <td width="15%"><strong>$895.00</strong></td>
                                <td width="8%"><select name="package-1" id="package-1">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td width="30%">
                                    <div id="package-1-names">
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- package 2 -->
                            <tr>
                                <td>Licensed Coach: BEF Only</td>
                                <td><strong>$895.00</strong></td>
                                <td><select name="package-2" id="package-2">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-2-names">
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- package 3-->
                             <tr>
                                <td>Licensed Coach Social Ticket: Welcome Reception & Awards Gala</td>
                                <td><strong>$150.00</strong></td>
                                <td><select name="package-3" id="package-3">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-3-names">
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- package 4-->
                             <tr>
                                <td>Client/Other: Full BEF Conference & Awards Gala</td>
                                <td><strong>$895.00</strong></td>
                                <td><select name="package-4" id="package-4">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-4-names">
                                    </div>
                                </td>
                            </tr>
                            
                             <!-- package 5-->
                             <tr>
                                <td>Client/Other: Welcome Reception & Awards Gala</td>
                                <td><strong>$150.00</strong></td>
                                <td><select name="package-5" id="package-5">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-5-names">
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- package 6-->
                             <tr>
                                <td>Hall of Fame - FREE</td>
                                <td><strong>$0.00</strong></td>
                                <td><select name="package-6" id="package-6">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-6-names">
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- package 7-->
                             <tr>
                                <td>Sponsor Event (exhibit table for 2 days, logo visibility at event and website)</td>
                                <td><strong>$1000.00</strong></td>
                                <td><select name="package-7" id="package-7">';
                        for($count=0; $count<=25; $count++){
                            $output .= '<option value='.$count.'>'.$count.'</option>';
                        }                
                        $output .='</select>
                                </td>
                                <td>
                                    <div id="package-7-names">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4"><h5>Total amount: <span id="total-amount"></span></h5></td>
                            </tr>
                        </table>
                    </p>
                    
                    
                    <!--<p class="bef-input-container">
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
                    </p>-->
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
					<input type="submit" class="button" name="bef_submit" value="Register!" />
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
// Include ACF
/*
include_once( plugin_dir_path( __FILE__ ) .'lib/advanced-custom-fields/acf.php' );
*/

// 4.2
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
    /*echo "<pre>";
    print_r($_POST);
    die();*/
    
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
			'fname' => esc_attr( $_POST['bef_fname'] ),
			'lname' => esc_attr( $_POST['bef_lname'] ),
            'company' => esc_attr( $_POST['bef_company'] ),
			'email' => esc_attr( $_POST['bef_email'] ),
            'business_phone' => esc_attr( $_POST['bef_business_phone'] ),
            'mobile_phone' => esc_attr( $_POST['bef_mobile_phone'] ),
            'package-1' => esc_attr($_POST['package-1']),
            'package-1-names' => array_map( 'esc_attr', $_POST['package-1-names'] ),
            'package-2' => esc_attr( $_POST['package-2'] ),
            'package-2-names' => array_map( 'esc_attr', $_POST['package-2-names'] ),
            'package-3' => esc_attr( $_POST['package-3'] ),
            'package-3-names' =>array_map( 'esc_attr', $_POST['package-3-names'] ),
            'package-4' => esc_attr( $_POST['package-4'] ),
            'package-4-names' => array_map( 'esc_attr', $_POST['package-4-names'] ),
            'package-5' => esc_attr( $_POST['package-5'] ),
            'package-5-names' => array_map( 'esc_attr', $_POST['package-5-names'] ),
            'package-6' => esc_attr( $_POST['package-6'] ),
            'package-6-names' => array_map( 'esc_attr', $_POST['package-6-names'] ),
            'package-7' => esc_attr( $_POST['package-7'] ),
            'package-7-names' => array_map( 'esc_attr', $_POST['package-7-names'] ),
            'total-amount' => esc_attr( $_POST['total-amount'] ),
            'bef_billing_address_1' => esc_attr( $_POST['bef_billing_address_1'] ),
            'bef_billing_address_2' => esc_attr( $_POST['bef_billing_address_2'] ),
            'bef_billing_city' => esc_attr( $_POST['bef_billing_city'] ),
            'bef_billing_state' => esc_attr( $_POST['bef_billing_state'] ),
            'bef_cc_num' => esc_attr( $_POST['bef_cc_num'] ),
            'bef_cc_month' => esc_attr( $_POST['bef_cc_month'] ),
            'bef_cc_year' => esc_attr( $_POST['bef_cc_year'] ),
            'bef_cc_code' => esc_attr( $_POST['bef_cc_code'] ),
            'bef_split_payment' => esc_attr( $_POST['bef_split_payment'] ),
		);
       
        //echo "<pre>";
       // print_r($registrant_data);
       // die();
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
		
        // run payment
        $payment_status = 'payment declined';
        if($registrant_data['bef_split_payment'] == 'full_amount'){
            // run credit card transaction
            $response = charge_credit_card($registrant_data['total-amount'], 
                                           $registrant_data['bef_cc_num'],
                                           $registrant_data['bef_cc_month'].$registrant_data['bef_cc_year'],
                                           $registrant_data['bef_cc_code']
                                          );
            if ($response != null){
                $tresponse = $response->getTransactionResponse();  
                if (($tresponse != null) && ($tresponse->getResponseCode()== \SampleCode\Constants::RESPONSE_OK) ) {
                    $payment_status = $tresponse->getAuthCode() . " " . $tresponse->getTransId();
                }
                else {
                    $payment_status = "Charge Credit Card ERROR :  Invalid response\n";
                }
            }
            else {
                $payment_status = "Charge Credit card Null response returned";
            }
        }
        else {
            // run subscription
        }
        
		// add/update custom meta data
		update_field(bef_get_acf_key('bef_fname'), $registrant_data['fname'], $registrant_id);
		update_field(bef_get_acf_key('bef_lname'), $registrant_data['lname'], $registrant_id);
		update_field(bef_get_acf_key('bef_email'), $registrant_data['email'], $registrant_id);
		
        update_field(bef_get_acf_key('bef_company'), $registrant_data['company'], $registrant_id);
        update_field(bef_get_acf_key('bef_business_phone'), $registrant_data['business_phone'], $registrant_id);
        update_field(bef_get_acf_key('bef_mobile_phone'), $registrant_data['mobile_phone'], $registrant_id);
        update_field(bef_get_acf_key('package_1_quantity'), $registrant_data['package-1'], $registrant_id);
        update_field(bef_get_acf_key('package_1_names'), implode('|', $registrant_data['package-1-names']), $registrant_id);
        update_field(bef_get_acf_key('package_2_quantity'), $registrant_data['package-2'], $registrant_id);
        update_field(bef_get_acf_key('package_2_names'), implode('|', $registrant_data['package-2-names']), $registrant_id);
        update_field(bef_get_acf_key('package_3_quantity'), $registrant_data['package-3'], $registrant_id);
        update_field(bef_get_acf_key('package_3_names'), implode('|', $registrant_data['package-3-names']), $registrant_id);
        update_field(bef_get_acf_key('package_4_quantity'), $registrant_data['package-4'], $registrant_id);
        update_field(bef_get_acf_key('package_4_names'), implode('|', $registrant_data['package-4-names']), $registrant_id);
        update_field(bef_get_acf_key('package_5_quantity'), $registrant_data['package-5'], $registrant_id);
        update_field(bef_get_acf_key('package_5_names'), implode('|', $registrant_data['package-5-names']), $registrant_id);
        update_field(bef_get_acf_key('package_6_quantity'), $registrant_data['package-6'], $registrant_id);
        update_field(bef_get_acf_key('package_6_names'), implode('|', $registrant_data['package-6-names']), $registrant_id);
        update_field(bef_get_acf_key('package_7_quantity'), $registrant_data['package-7'], $registrant_id);
        update_field(bef_get_acf_key('package_7_names'), implode('|', $registrant_data['package-7-names']), $registrant_id);
        
        update_field(bef_get_acf_key('total_amount'), $registrant_data['total-amount'], $registrant_id);
        update_field(bef_get_acf_key('billing_address_1'), $registrant_data['bef_billing_address_1'], $registrant_id);
        update_field(bef_get_acf_key('billing_address_2'), $registrant_data['bef_billing_address_2'], $registrant_id);
        update_field(bef_get_acf_key('billing_city'), $registrant_data['bef_billing_city'], $registrant_id);
        update_field(bef_get_acf_key('billing_state'), $registrant_data['bef_billing_state'], $registrant_id);
        update_field(bef_get_acf_key('credit_card_last_four'), '1567', $registrant_id); // PLACEHOLDER
        update_field(bef_get_acf_key('split_payment'), $registrant_data['bef_split_payment'], $registrant_id);
        update_field(bef_get_acf_key('payment_status'), $payment_status, $registrant_id); // PLACEHOLDER

        
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
            
        case 'bef_company':
			$field_key = 'field_5769ccf861eae';
			break;
            
        case 'bef_business_phone':
			$field_key = 'field_5769cd14251d9';
			break;
            
        case 'bef_mobile_phone':
			$field_key = 'field_5769cd50251da';
			break;
            
        case 'package_1_quantity':
			$field_key = 'field_576c55c1ece87';
			break;
            
		case 'package_1_names':
			$field_key = 'field_576c54a689e39';
			break;
		
        case 'package_2_quantity':
			$field_key = 'field_576c55db3271f';
			break;
            
		case 'package_2_names':
			$field_key = 'field_576c54e16da77';
			break;  
            
        case 'package_3_quantity':
			$field_key = 'field_576c55f1f8005';
			break;
            
		case 'package_3_names':
			$field_key = 'field_576c54f230b0b';
			break;  
            
        case 'package_4_quantity':
			$field_key = 'field_576c56052f615';
			break;
            
		case 'package_4_names':
			$field_key = 'field_576c5500d5716';
			break;  
            
        case 'package_5_quantity':
			$field_key = 'field_576c56181b5fc';
			break;
            
		case 'package_5_names':
			$field_key = 'field_576c551ee7fd4';
			break;  
            
        case 'package_6_quantity':
			$field_key = 'field_576c56323350f';
			break;
            
		case 'package_6_names':
			$field_key = 'field_576c5536873be';
			break;  
            
        case 'package_7_quantity':
			$field_key = 'field_576c563f0e245';
			break;
            
		case 'package_7_names':
			$field_key = 'field_576c554f03901';
			break;  
            
        case 'total_amount':
			$field_key = 'field_576c557c2f805';
			break;  
            
        case 'billing_address_1':
			$field_key = 'field_576c565c8cf8a';
			break;  
            
        case 'billing_address_2':
			$field_key = 'field_576c56834c170';
			break;  
            
        case 'billing_city':
			$field_key = 'field_576c56a05e4b8';
			break;  
            
        case 'billing_state':
			$field_key = 'field_576c56bfe23ff';
			break;  
            
        case 'credit_card_last_four':
			$field_key = 'field_576c56d77e610';
			break;  
            
        case 'split_payment':
			$field_key = 'field_576c56f5085d1';
			break;  
            
        case 'payment_status':
			$field_key = 'field_576c5722062c0';
			break;  
            
        default: break;
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
// 6.7
// hint: charge_credit_card(), returns Authorize.net response
function charge_credit_card($amount, $cc_num, $cc_exp, $cc_code){
    // Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName(\SampleCode\Constants::MERCHANT_LOGIN_ID);
    $merchantAuthentication->setTransactionKey(\SampleCode\Constants::MERCHANT_TRANSACTION_KEY);
    $refId = 'ref' . time();
    
    // Create the payment data for a credit card
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($cc_num);
    $creditCard->setExpirationDate($cc_exp);
    $creditCard->setCardCode($cc_code);
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setCreditCard($creditCard);

    $order = new AnetAPI\OrderType();
    $order->setDescription("BEF Registration");
    
    //create a transaction
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
    $transactionRequestType->setAmount($amount);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);


    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId( $refId);
    $request->setTransactionRequest( $transactionRequestType);
    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    
    return $response;
}

/* !7. CUSTOM POST TYPES */

// 7.1
// registrants
/*
include_once( plugin_dir_path( __FILE__ ) . 'cpt/bef-registrant.php');
*/
/* !8. ADMIN PAGES */




/* !9. SETTINGS */