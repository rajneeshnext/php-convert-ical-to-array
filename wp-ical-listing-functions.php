// Hook to add the meta box
function add_listing_meta_box() {
    add_meta_box(
        'listing_ical_link',           // ID of the meta box
        'iCal link',              // Title of the meta box
        'display_listing_ical_link_meta_box',   // Callback function
        'listing',             // Post type slug where the field should appear
        'normal',                    // Position ('normal', 'side', 'advanced')
        'high'                       // Priority
    );
}
add_action('add_meta_boxes', 'add_listing_meta_box');
// Callback to display the meta box
function display_listing_ical_link_meta_box($post) {
    // Retrieve current value of the field (if it exists)
    $custom_field_value = get_post_meta($post->ID, '_listing_ical_link', true);
    
    // Security nonce field
    wp_nonce_field('save_listing_ical_link_meta_box_data', 'listing_ical_link_meta_box_nonce');

    // Display the input field
    echo '<label for="custom_field">Listing iCal Link: </label>';
    echo '<input type="text" id="listing_ical_link" name="listing_ical_link" value="' . esc_attr($custom_field_value) . '" size="50" />';
}
// Hook to save custom field data
function save_listing_meta_box_data($post_id) {
    // Check if the nonce is set and valid
    if (!isset($_POST['listing_ical_link_meta_box_nonce']) || !wp_verify_nonce($_POST['listing_ical_link_meta_box_nonce'], 'save_listing_ical_link_meta_box_data')) {
        return;
    }

    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save or update the custom field data
    if (isset($_POST['listing_ical_link'])) {
        update_post_meta($post_id, '_listing_ical_link', sanitize_text_field($_POST['listing_ical_link']));
    }
}
add_action('save_post', 'save_listing_meta_box_data');

function icsToArray($paramUrl){
    $icsFile = file_get_contents($paramUrl);
    $icsData = explode("BEGIN:", $icsFile);
    foreach($icsData as $key => $value){
        $icsDatesMeta[$key] = explode("\n", $value);
    }
    foreach($icsDatesMeta as $key => $value){
        foreach($value as $subKey => $subValue){
            if($subValue != ""){
                if($key != 0 && $subKey == 0){
                    $icsDates[$key]["BEGIN"] = trim($subValue);
                }else{
                    $subValueArr = explode(":", $subValue, 2);
                    $icsDates[$key][$subValueArr[0]] = trim($subValueArr[1]);
                    $subValueKey = explode(";", $subValue, 2);
                    $icsDates[$key][$subValueKey[0]] = trim($subValueArr[1]);
                }
            }
        }
    }
    return $icsDates;
}

function checkListingAvailability($listing_id, $icspath = "https://www.airbnb.com/calendar/ical/xxxxxx.ics?s=xxxxxxxxxx"){
    $ics = icsToArray($icspath);
    $listings_cal = array();
    $i=0;
    foreach($ics as $a){
        if($a['BEGIN'] == "VEVENT"){
            $a['DTSTART'] = date_format(date_create_from_format('Ymd', $a['DTSTART']), 'Y-m-d');
            $a['DTEND'] = date_format(date_create_from_format('Ymd', $a['DTEND']), 'Y-m-d');
            $listings_cal[$i]['start_date'] = date("Y-m-d H:i:s", strtotime($a['DTSTART']));
            $listings_cal[$i]['end_date']  = date("Y-m-d H:i:s", strtotime($a['DTEND']));
            $listings_cal[$i]['summary'] = $a['SUMMARY'];
            $i++;
        }
    }
    //print_r($listings_cal);
    //exit();
    // Get Listings 
    global $wpdb;
    foreach($listings_cal as $listing_cal){
        if( $listing_cal['summary'] == "Reserved"){
            $table_name = $wpdb->prefix . 'bookings_calendar';
            $query = $wpdb->prepare(
                "SELECT date_start, date_end 
                FROM $table_name 
                WHERE listing_id = %d
                AND date_start IS NOT NULL AND date_start = '".$listing_cal['start_date']."'
                AND date_end IS NOT NULL AND date_end = '".$listing_cal['end_date']."'",
                $listing_id
            );            
            // Get the result row
            $result = $wpdb->get_row($query);            
            if ( $result ) {
            } else {
                // Data to insert
                $data = array(
                    'bookings_author' => '1',
                    'owner_id' => '1',
                    'listing_id' => $listing_id,
                    'date_start' => $listing_cal['start_date'],
                    'date_end' => $listing_cal['end_date'],
                    'comment' => '{"first_name":"AirBNB"}',
                    'status' => 'approved',
                    'type' => 'reservation',
                    'created' => date('Y-m-d H:i:s'),
                );
                // Data format
                $format = array(
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                );
                // Insert data into the table
                $wpdb->insert( $table_name, $data, $format );
                // Get the inserted ID (if needed)
                $insert_id = $wpdb->insert_id;
                if ( $insert_id ) {
                    //echo 'Data inserted successfully with ID: ' . $insert_id;
                } else {
                    //echo 'Data insertion failed.';
                }
            }
        }
    }
}
