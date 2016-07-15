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
        6.7 - charge_credit_card()
        6.8 - create_subscription()
        6.9 - is_phone_number()
        6.10 - is_cc_number()
 * 
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
                    <p class="required-fields">Required fields are indicated with an asterisk</p>
                    <fieldset>
                        <legend>Contact Details:</legend>
                        <p class="bef-input-container">
                            <label><span class="required-fields">*</span> Your Name</label><br />
                            <input type="text" name="bef_fname" placeholder="First Name" />
                            <input type="text" name="bef_lname" placeholder="Last Name" />
                        </p>
                    
                        <p class="bef-input-container">
                            <label><span class="required-fields">*</span> Company</label><br />
                            <input type="text" name="bef_company" placeholder="Company" />
                        </p>
                    
                        <p class="bef-input-container">
                            <label><span class="required-fields">*</span> Business Phone</label><br />
                            <input type="tel" name="bef_business_phone" placeholder="Digits only" />
                        </p>
                                        
                        <p class="bef-input-container">
                            <label><span class="required-fields">*</span> Mobile Phone</label><br />
                            <input type="tel" name="bef_mobile_phone" placeholder="Digits only" />
                        </p>

                        <p class="bef-input-container">
                            <label><span class="required-fields">*</span> Your Email</label><br />
                            <input type="email" name="bef_email" placeholder="ex. you@email.com" />
                        </p>
                        
                        <p class="bef-textarea">
                            <label>Who Is Your Coach?</label><br />
                            <textarea rows="4" cols="50" name="who_is_your_coach"></textarea>
                        </p>
                    </fieldset>
                
                    <!-- Registration details: number of attendees, attendee names, shirt sizes -->
                    <!-- Choose role and amount -->
                    <fieldset>
                        <legend>Choose Products</legend>
                        <label><span class="required-fields">*</span> Packages and Quantities</label>
                        
                        <p class="bef-input-container">
                            <div id="product-table-box">
                                <table class="product-table">
                                    <tr>
                                        <th width="15%">Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Attendees Names</th>
                                        <th>Shirt Sizes</th>
                                        <th>Dietary Restrictions</th>
                                    </tr>
                                    <!-- PACKAGE 1 -->
                                    <tr>
                                        <td>Client/Other: Full BEF Conference & Awards Gala</td>
                                        <td><strong>$895.00</strong></td>
                                        <td><select name="package-1" id="package-1">';
                                            for($count=0; $count<=25; $count++){
                                                $output .= '<option value='.$count.'>'.$count.'</option>';
                                            }                
                                        $output .='</select>
                                        </td>
                                        <td>
                                            <div id="package-1-names">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-1-shirts">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-1-diets">
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- PACKAGE 2 -->
                                    <tr>
                                        <td>Client/Other: Welcome Reception & Awards Gala</td>
                                        <td><strong>$300.00</strong></td>
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
                                        <td>
                                            <div id="package-2-shirts">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-2-diets">
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- PACKAGE 3 -->
                                    <tr>
                                        <td>Licensed Coach: Full BEF Conference, Awards Gala, Coach Conference</td>
                                        <td><strong>$895.00</strong></td>
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
                                        <td>
                                            <div id="package-3-shirts">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-3-diets">
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- PACKAGE 4 -->
                                    <tr>
                                        <td>Kids Package (11 years and younger / Awards Gala ONLY)</td>
                                        <td><strong>$100.00</strong></td>
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
                                        <td>
                                            <div id="package-4-shirts">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-4-diets">
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- PACKAGE 5 -->
                                    <tr>
                                        <td>Hall of Fame Coach / Contractual Agreement</td>
                                        <td><strong>$0.00</strong></td>
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
                                        <td>
                                            <div id="package-5-shirts">
                                            </div>
                                        </td>
                                        <td>
                                            <div id="package-5-diets">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6"><h5><strong>Total amount: <span id="total-amount"></span></strong></h5></td>
                                    </tr>
                                </table>
                            </div>
                        </p>
                    </fieldset>
                
                <!-- Billing info: cc information etc -->
                <fieldset>
                    <legend>Billing Details:</legend>
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> Billing Address</label>
                        <input type="text" name="bef_billing_address_1" placeholder="Billing Address 1" />
                        <input type="text" name="bef_billing_address_2" placeholder="Billing Address 2 (Apt #, Suite # etc.)" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> Billing City</label>
                        <input type="text" name="bef_billing_city" placeholder="Billing City, e.g. Las Vegas" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> Billing State</label>
                        <input type="text" name="bef_billing_state" placeholder="Billing State, e.g. NV" />
                    </p>
                    
                    <p class="bef-input-container">
                        <label>Billing Zip</label>
                        <input type="text" name="bef_billing_zip" placeholder="Zipcode e.g. 89148 (optional field)" />
                    </p>
                </fieldset>
                
                <!-- Credit card details -->
                <fieldset>
                    <legend>Credit Card Details:</legend>
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> Credit Card Number</label>
                        <input type="text" name="bef_cc_num" pattern="[0-9]{13,16}" placeholder="Credit Card number">
                    </p>
                    
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> CC Expiration Month</label>
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
                        <label><span class="required-fields">*</span> CC Expiration Year</label>
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
                        <label><span class="required-fields">*</span> CC Card Code</label>
                        <input type="text" name="bef_cc_code" placeholder="Credit Card Card Code">
                    </p>
                    
                    <p class="bef-input-container">
                        <label><span class="required-fields">*</span> Split Payment?<br/>
                        <small>First payment charged today if split.<br/>
                        </small></label><br/>
                        <select name="bef_split_payment" id="bef_split_payment">
                            <option value="full_amount">Do not split - pay full amount</option>
                            <option value="2">Split into two monthly payments</option>
                            <option value="3">Split into three monthly payments</option>
                            <option value="4">Split into four monthly payments</option>
                        </select>
                    </p>
                    
                    <p id="payment-schedule"></p>
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
			$output .= $fname .' '. $lname . '';
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
            'total-amount' => esc_attr( $_POST['total-amount'] ),
            'bef_billing_address_1' => esc_attr( $_POST['bef_billing_address_1'] ),
            'bef_billing_address_2' => esc_attr( $_POST['bef_billing_address_2'] ),
            'bef_billing_city' => esc_attr( $_POST['bef_billing_city'] ),
            'bef_billing_state' => esc_attr( $_POST['bef_billing_state'] ),
            'bef_billing_zip' => esc_attr( $_POST['bef_billing_zip'] ),
            'bef_cc_num' => esc_attr( $_POST['bef_cc_num'] ),
            'bef_cc_month' => esc_attr( $_POST['bef_cc_month'] ),
            'bef_cc_year' => esc_attr( $_POST['bef_cc_year'] ),
            'bef_cc_code' => esc_attr( $_POST['bef_cc_code'] ),
            'bef_split_payment' => esc_attr( $_POST['bef_split_payment'] ),
            'who_is_your_coach' => esc_attr( $_POST['who_is_your_coach'] ),
            'package-1-shirts' => array_map( 'esc_attr', $_POST['package-1-shirts'] ),
            'package-2-shirts' => array_map( 'esc_attr', $_POST['package-2-shirts'] ),
            'package-3-shirts' => array_map( 'esc_attr', $_POST['package-3-shirts'] ),
            'package-4-shirts' => array_map( 'esc_attr', $_POST['package-4-shirts'] ),
            'package-5-shirts' => array_map( 'esc_attr', $_POST['package-5-shirts'] ),
            'package-1-diets' => array_map( 'esc_attr', $_POST['package-1-diets'] ),
            'package-2-diets' => array_map( 'esc_attr', $_POST['package-2-diets'] ),
            'package-3-diets' => array_map( 'esc_attr', $_POST['package-3-diets'] ),
            'package-4-diets' => array_map( 'esc_attr', $_POST['package-4-diets'] ),
            'package-5-diets' => array_map( 'esc_attr', $_POST['package-5-diets'] ),
	);
       
        // setup our errors array
	$errors = array();
        
        // form validation
            /* Validation for all required fields */
            if( !strlen( $registrant_data['fname'] ) ) $errors['fname'] = 'First name is required.';
            if( !strlen( $registrant_data['lname'] ) ) $errors['lname'] = 'Last name is required.';
            if( !strlen( $registrant_data['company'] ) ) $errors['company'] = 'Company is required.';
            if( !strlen( $registrant_data['email'] ) ) $errors['email'] = 'Email address is required.';
            if( !strlen( $registrant_data['business_phone'] ) ) $errors['business_phone'] = 'Business phone is required.';
            if( !strlen( $registrant_data['mobile_phone'] ) ) $errors['mobile_phone'] = 'Mobile phone is required.';
            if( !strlen( $registrant_data['bef_billing_address_1'] ) ) $errors['bef_billing_address_1'] = 'Billing address 1 is required.';
            if( !strlen( $registrant_data['bef_billing_city'] ) ) $errors['bef_billing_city'] = 'Billing city is required.';
            if( !strlen( $registrant_data['bef_billing_state'] ) ) $errors['bef_billing_state'] = 'Billing state is required.';
            if( !strlen( $registrant_data['bef_cc_num'] ) ) $errors['bef_cc_num'] = 'Credit card number is required.';
            if( !strlen( $registrant_data['bef_cc_month'] ) ) $errors['bef_cc_month'] = 'Credit card month is required.';
            if( !strlen( $registrant_data['bef_cc_year'] ) ) $errors['bef_cc_year'] = 'Credit card year is required.';
            if( !strlen( $registrant_data['bef_cc_code'] ) ) $errors['bef_cc_code'] = 'Credit card code is required.';
            
            /* Other types of validation */
            if( strlen( $registrant_data['email'] ) && !is_email( $registrant_data['email'] ) ) $errors['email'] = 'Email address must be valid.';
            if(!is_phone_number($registrant_data['business_phone'])) $errors['business_phone-2'] = 'Business phone number is invalid';  
            if(!is_phone_number($registrant_data['mobile_phone'])) $errors['mobile_phone-2'] = 'Mobile phone number is invalid';  
            if(!is_cc_number($registrant_data['bef_cc_num'])) $errors['bef_cc_num-2'] = 'Credit card number is invalid';      

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
                /*if( bef_registrant_has_registration( $registrant_id, $event_id ) ):
                    // get event object
                    $event = get_post( $event_id );

                    // return detailed error
                    $result['message'] .= esc_attr( $registrant_data['email'] .' is already registered for '. $event->post_title .'.');

                else: */
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
                // endif;
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
		$registrant_id = null;
		//$registrant_id = bef_get_registrant_id( $registrant_data['email'] );
		
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
        $reg_date = date('Y-m-d H:i:s');
        $transaction_id = 'N/A';
        $subscription_id = 'N/A';
                
        if($registrant_data['bef_split_payment'] == 'full_amount'){
            // run credit card transaction
            $payment_status = charge_credit_card($registrant_data['total-amount'], 
                                           $registrant_data['bef_cc_num'],
                                           $registrant_data['bef_cc_month'].$registrant_data['bef_cc_year'],
                                           $registrant_data['bef_cc_code'],
                                           $transaction_id
                                          );
        }
        else {
            // run subscription
                // charge credit card for 1/nth of the amount
                $interval_length = (int)$registrant_data['bef_split_payment'];
                $partial_payment = $registrant_data['total-amount'] /  $interval_length;
              
                $payment_status = charge_credit_card($partial_payment, 
                                           $registrant_data['bef_cc_num'],
                                           $registrant_data['bef_cc_month'].$registrant_data['bef_cc_year'],
                                           $registrant_data['bef_cc_code'],
                                            $transaction_id
                                          );
                // establish subscription
                $pos = strpos($payment_status, 'ERROR');
                
                if( $pos === false){
                    $payment_status = create_subscription($partial_payment, 
                                           $registrant_data['bef_cc_num'],
                                           $registrant_data['bef_cc_month'].$registrant_data['bef_cc_year'],
                                           $registrant_data['bef_cc_code'],
                                           $interval_length - 1,
                                           $subscription_id
                                          );
                }
                
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
        
        update_field(bef_get_acf_key('total_amount'), $registrant_data['total-amount'], $registrant_id);
        update_field(bef_get_acf_key('billing_address_1'), $registrant_data['bef_billing_address_1'], $registrant_id);
        update_field(bef_get_acf_key('billing_address_2'), $registrant_data['bef_billing_address_2'], $registrant_id);
        update_field(bef_get_acf_key('billing_city'), $registrant_data['bef_billing_city'], $registrant_id);
        update_field(bef_get_acf_key('billing_state'), $registrant_data['bef_billing_state'], $registrant_id);
        update_field(bef_get_acf_key('billing_zip'), $registrant_data['bef_billing_zip'], $registrant_id);
        update_field(bef_get_acf_key('credit_card_last_four'), substr($registrant_data['bef_cc_num'], -4), $registrant_id); 
        update_field(bef_get_acf_key('split_payment'), $registrant_data['bef_split_payment'], $registrant_id);
        update_field(bef_get_acf_key('payment_status'), $payment_status, $registrant_id); 
        update_field(bef_get_acf_key('reg_date'), $reg_date, $registrant_id); 
        update_field(bef_get_acf_key('transaction_id'), $transaction_id, $registrant_id); 
        update_field(bef_get_acf_key('subscription_id'), $subscription_id, $registrant_id); 
        update_field(bef_get_acf_key('who_is_your_coach'), $registrant_data['who_is_your_coach'], $registrant_id); 
        
        update_field(bef_get_acf_key('package_1_shirt_sizes'), implode('|', $registrant_data['package-1-shirts']), $registrant_id);
        update_field(bef_get_acf_key('package_2_shirt_sizes'), implode('|', $registrant_data['package-2-shirts']), $registrant_id);
        update_field(bef_get_acf_key('package_3_shirt_sizes'), implode('|', $registrant_data['package-3-shirts']), $registrant_id);
        update_field(bef_get_acf_key('package_4_shirt_sizes'), implode('|', $registrant_data['package-4-shirts']), $registrant_id);
        update_field(bef_get_acf_key('package_5_shirt_sizes'), implode('|', $registrant_data['package-5-shirts']), $registrant_id);
        
        update_field(bef_get_acf_key('package_1_diets'), implode('|', $registrant_data['package-1-diets']), $registrant_id);
        update_field(bef_get_acf_key('package_2_diets'), implode('|', $registrant_data['package-2-diets']), $registrant_id);
        update_field(bef_get_acf_key('package_3_diets'), implode('|', $registrant_data['package-3-diets']), $registrant_id);
        update_field(bef_get_acf_key('package_4_diets'), implode('|', $registrant_data['package-4-diets']), $registrant_id);
        update_field(bef_get_acf_key('package_5_diets'), implode('|', $registrant_data['package-5-diets']), $registrant_id);
        
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
            $field_key = 'field_5787bba95db78';
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
        
        case 'billing_zip':
            $field_key = 'field_576c56bfe23aa';
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

        case 'reg_date':
            $field_key = 'field_576c5722062aa';
            break; 
        
        case 'transaction_id':
            $field_key = 'field_576c5722062bb';
            break; 
        
        case 'subscription_id':
            $field_key = 'field_576c5722062dd';
            break; 
        
        case 'who_is_your_coach':
            $field_key = 'field_5786c77b4ea92';
            break; 
        
        case 'package_1_diets':
            $field_key = 'field_5787ac60095a8';
            break; 
        
        case 'package_2_diets':
            $field_key = 'field_5787ac861889b';
            break; 
        
        case 'package_3_diets':
            $field_key = 'field_5787ac871889c';
            break; 
        
        case 'package_4_diets':
            $field_key = 'field_5787ac891889d';
            break; 
        
        case 'package_5_diets':
            $field_key = 'field_5787ac8a1889e';
            break; 
        
        case 'package_1_shirt_sizes':
            $field_key = 'field_57870fb3f47bf';
            break; 
        
        case 'package_2_shirt_sizes':
            $field_key = 'field_5787ab4c3122a';
            break; 
        
        case 'package_3_shirt_sizes':
            $field_key = 'field_5787ab613122b';
            break; 
        
        case 'package_4_shirt_sizes':
            $field_key = 'field_5787ab633122c';
            break; 
        
        case 'package_5_shirt_sizes':
            $field_key = 'field_5787ab643122d';
            break; 
        
        case 'bef_registrations':
            $field_key = 'field_5769caf3ceb93';
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
function charge_credit_card($amount, $cc_num, $cc_exp, $cc_code, &$transaction_id){
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
    $amount = number_format((float)$amount , 2, '.', ''); 
    $transactionRequestType->setAmount($amount);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);


    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId( $refId);
    $request->setTransactionRequest( $transactionRequestType);
    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    //$response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    
    if ($response != null){
        $tresponse = $response->getTransactionResponse();  
        if (($tresponse != null) && ($tresponse->getResponseCode()== \SampleCode\Constants::RESPONSE_OK) ) {
            $payment_status = $tresponse->getAuthCode() . " " . $tresponse->getTransId();
            $transaction_id = $tresponse->getTransId();
        }
        else {
            $payment_status = "ERROR: Charge Credit Card ERROR :  Invalid response\n";
        }
     }
     else {
        $payment_status = "ERROR: Charge Credit card Null response returned";
    }
    return $payment_status;
}

// 6.8
// hint: create_subscription(), returns Authorize.net response
function create_subscription($amount, $cc_num, $cc_exp, $cc_code, $interval_length, &$subscription_id){
    // Common Set Up for API Credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName(\SampleCode\Constants::MERCHANT_LOGIN_ID);
    $merchantAuthentication->setTransactionKey(\SampleCode\Constants::MERCHANT_TRANSACTION_KEY);
    
    $refId = 'ref' . time();
    
     // Subscription Type Info
    $subscription = new AnetAPI\ARBSubscriptionType();
    $subscription->setName("BEF Payment Subscription");
    
    $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
    $interval->setLength(12);
    $interval->setUnit("months");
    
    $paymentSchedule = new AnetAPI\PaymentScheduleType();
    $paymentSchedule->setInterval($interval);
    $paymentSchedule->setStartDate(new DateTime(date('Y-m-d', strtotime('first day next month'))));
    $paymentSchedule->setTotalOccurrences($interval_length);
    $paymentSchedule->setTrialOccurrences("0");
                                          
    $subscription->setPaymentSchedule($paymentSchedule);
    $amount = number_format((float)$amount , 2, '.', '');  
    $subscription->setAmount($amount);
    $subscription->setTrialAmount("0");                                      

    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($cc_num);
    $creditCard->setExpirationDate($cc_exp);
    //$creditCard->setCardCode($cc_code);
    
    $payment = new AnetAPI\PaymentType();
    $payment->setCreditCard($creditCard);

    $subscription->setPayment($payment);
    
    $billTo = new AnetAPI\NameAndAddressType();
    $billTo->setFirstName("BEF");
    $billTo->setLastName("Attendee");
    
    $subscription->setBillTo($billTo);

    $request = new AnetAPI\ARBCreateSubscriptionRequest();
    $request->setmerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setSubscription($subscription);
    $controller = new AnetController\ARBCreateSubscriptionController($request);

    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);   
 //   $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
    {
        $subscription_id = $response->getSubscriptionId();
        $payment_status = "SUCCESS: Subscription ID : " . $response->getSubscriptionId() . "\n";
     }
    else
    {
        $payment_status = "ERROR :  Invalid response\n";
        $errorMessages = $response->getMessages()->getMessage();
        $payment_status .= "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
    }

    return $payment_status;
}

// 6.9
// hint: is_phone_number(), returns bool
function is_phone_number($phone){
    //$pattern = '/^(?:\(\+?44\)\s?|\+?44 ?)?(?:0|\(0\))?\s?(?:(?:1\d{3}|7[1-9]\d{2}|20\s?[78])\s?\d\s?\d{2}[ -]?\d{3}|2\d{2}\s?\d{3}[ -]?\d{4})$/';
    $pattern = '/^[0-9]/';
    if( !preg_match( $pattern, $phone ) )
    {
        return false;
    }
    return true;
}

// 6.10
// hint: is_cc_number(), returns bool
function is_cc_number($value){
    $pattern = "/^([34|37]{2})([0-9]{13})$/"; //American Express
    if (preg_match($pattern, $value)) return true;
    
    $pattern = "/^([6011]{4})([0-9]{12})$/"; //Discover Card
    if (preg_match($pattern, $value)) return true;
    
    $pattern = "/^([51|52|53|54|55]{2})([0-9]{14})$/"; //Mastercard
    if (preg_match($pattern, $value)) return true;
    
    $pattern = "/^([4]{1})([0-9]{12,15})$/"; //Visa
    if (preg_match($pattern, $value)) return true;
    
    return false;
}
/* !7. CUSTOM POST TYPES */

// 7.1
// registrants
//include_once( plugin_dir_path( __FILE__ ) . 'cpt/bef-registrant.php');
/* !8. ADMIN PAGES */




/* !9. SETTINGS */