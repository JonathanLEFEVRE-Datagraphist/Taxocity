<?php
/**
 * Plugin Name: Taxocity
 * Plugin URL: #
 * Description: This plugins features an extra field in the post editing windows that makes possible to link the post to a city. This plugin uses two different shortcodes, [taxocity] will display the city's name associated to a post or page on your website. The second : [get_current_weather woeid = '' tempscale = '']  
 * Version: 1.0
 * Author: Jonathan LEFEVRE for Netmediaeurope
 * License: GPL2 Licence 
 */

function add_custom_taxonomies() {
	// Add new "Locations" taxonomy to Posts
	register_taxonomy('location', 'post', array(
		// Hierarchical taxonomy (like categories)
		'hierarchical' => true,
		// This array of options controls the labels displayed in the WordPress Admin UI
		'labels' => array(
			'name' => _x( 'Locations', 'taxonomy general name' ),
			'singular_name' => _x( 'Location', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Locations' ),
			'all_items' => __( 'All Locations' ),
			'parent_item' => __( 'Parent Location' ),
			'parent_item_colon' => __( 'Parent Location:' ),
			'edit_item' => __( 'Edit Location' ),
			'update_item' => __( 'Update Location' ),
			'add_new_item' => __( 'Add New Location' ),
			'new_item_name' => __( 'New Location Name' ),
			'menu_name' => __( 'Locations' ),
		),
		// Control the slugs used for this taxonomy
		'rewrite' => array(
			'slug' => 'locations', // This controls the base slug that will display before each term
			'with_front' => false, // Don't display the category base before "/locations/"
			'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
		),
	));
}
add_action( 'init', 'add_custom_taxonomies', 0 );


/** ------------------------------------------------------------------
A City Shortcode to display location's names in post & pages. Try it  : [Taxocity]
*/

  function display_location_tax(){
    global $post;
    // some custom taxonomies:
    $taxonomies = array( 
                        "location"=>"City | " 
                  );
    $out = "";
    foreach ($taxonomies as $tax => $taxname) {     
        $out .= "";
        $out .= $taxname;
        // get the terms related to post
        $terms = get_the_terms( $post->ID, $tax );
        if ( !empty( $terms ) ) {
            foreach ( $terms as $term )
                $out .= '<a href="' .get_term_link($term->slug, $tax) .'">'.$term->name.'</a> ';
        }
        $out .= "";
    }
    $out .= "";
    return $out;
    }

function taxocity_shortcode(){
   add_shortcode('taxocity', 'display_location_tax');
}

add_action( 'init', 'taxocity_shortcode');

/** --------------------------------------------------------------
BONUS : A Weather shortcode for dynamic display of weather in city
based on Ross Elliot's work at http://wp.tutsplus.com/tutorials/create-a-weather-conditions-plugin-using-yahoo-and-simplexml/
*/

// Register style sheet.
add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );

/**
 * Register style sheet.
 */
function register_plugin_styles() {
	wp_register_style( 'cityweather', plugins_url( 'taxocity/css/classic.css' ) );
	wp_enqueue_style( 'cityweather' );
}

function get_current_weather_template_tag($woeid = '', $tempscale = 'c'){  
  
  echo get_current_weather_display($woeid, $tempscale);  
  
}

//The shortcode to use in posts and pages
add_shortcode('get_current_weather', 'get_current_weather_shortcode');

function get_current_weather_shortcode($atts){  
  
  $args = shortcode_atts(array('woeid' => '', 'tempscale' => 'c'), $atts );  
       
  $args['tempscale'] = ($args['tempscale']=='c') ? 'c' : 'f';  
    
  return get_current_weather_display($args['woeid'], $args['tempscale']);  
  
}

//Display the weather of the city
function get_current_weather_display($woeid, $tempscale){  
  
  $weather_panel = '<div class = "gcw_weather_panel">';  
      
  if($weather = get_current_weather_data($woeid, $tempscale)){  
    
    $weather_panel .= '<span>' . $weather['city'] . '</span>';          
    $weather_panel .= '<span>' . $weather['temp'] . ' ' . strtoupper($tempscale) . '</span>';  
    $weather_panel .= '<img src = "' . $weather['icon_url'] . '" />';  
    $weather_panel .= '<span>' . $weather['conditions'] . '</span>';  
  
  }else{//no weather data  
    
    $weather_panel .= '<span>No weather data!';  
      
  }  
  
  $weather_panel .= '</div>';  
        
  return $weather_panel;    
}


function get_current_weather_data($woeid, $tempscale){  
  
  $query_url = 'http://weather.yahooapis.com/forecastrss?w=' . $woeid . '&u=' . $tempscale;  
    
  if($xml = simplexml_load_file($query_url)){  
        
    $error = strpos(strtolower($xml->channel->description), 'error');//server response but no weather data for woeid  
      
  }else{  
      
    $error = TRUE;//no response from weather server  
      
  }  
    
  if(!$error){  
    
    $weather['city'] = $xml->channel->children('yweather', TRUE)->location->attributes()->city;  
    $weather['temp'] = $xml->channel->item->children('yweather', TRUE)->condition->attributes()->temp;    
    $weather['conditions'] = $xml->channel->item->children('yweather', TRUE)->condition->attributes()->text;  
    
    $description = $xml->channel->item->description;  
      
    $imgpattern = '/src="(.*?)"/i';  
    preg_match($imgpattern, $description, $matches);  
  
    $weather['icon_url']= $matches[1];  
      
    return $weather;  
 }  
    
  return 0;  
  }
  
  
?>