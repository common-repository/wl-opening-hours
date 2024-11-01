<?php
/*
   This class is for hooks and plugin managent, and is instantiated as a singleton and set globally as $WLOpeningHours. IOK 2018-02-07
   For WP-specific interactions.

   This file is part of the WordPress plugin WL Opening Hours
   Copyright (C) 2018 WP Hosting AS

   WL Opening Hours is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   WL Opening Hours is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.


 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


class WLOpeningHours {
    private static $instance = null;
    function __construct() {
    }
    public static function instance()  {
        if (!static::$instance) static::$instance = new WLOpeningHours();
        return static::$instance;
    }
    public function init () {
        add_filter('the_content', array($this, 'the_content'));
        add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
        $this->add_shortcodes();
        $this->add_custom_post_types();
        $this->add_custom_taxonomies();
    }

    public function admin_init () {
        register_setting('wl_opening_hours_options','wl_opening_hours_options', array($this,'validate'));
        add_action('save_post', array($this, 'save_post'), 10, 3);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this,'admin_enqueue_scripts'));


	// This is for mapping Venues to the custom post type on 'save-post'. We need to map the taxonomies from ids to slugs here, since the wl_venue taxonomy isn't hierarchical.
	// It has to be done here in admin-init as it has to happen earlier than save-post. The input will be sanitized in save post, so there is no value in checking
	// nonces and sanitizing this, but just to be safe we to this here too. IOK 2019-08-19
	$postid = isset($_POST['post_ID']) ? sanitize_key($_POST['post_ID']) : 0 ;

	if ($postid && current_user_can('edit_post',$postid) && wp_verify_nonce($_POST['wl-hours-nonce'], 'wl-hours') 
            && isset( $_POST['tax_input'] ) && is_array( $_POST['tax_input'] ) && isset($_POST['tax_input']['wl_venue']) ) {
                $venues = array_map('sanitize_key', $_POST['tax_input']['wl_venue']);
                $terms = array();
		foreach( $venues as $termid) {
                   if (!$termid) continue;
                   $terma = get_term($termid,'wl_venue', ARRAY_A);
                   $terms[] = $terma['slug'];
		}
                $taxinput['wl_venue'] = $terms;
	        // Add the mapped slugs back to the input field so WP can assign the taxonomy to the post . IOK 2019-08-19
		$_POST['tax_input']['wl_venue'] = $terms;
	}

    }


    public function admin_menu () {
//        add_options_page('WL Opening Hours', 'WL Opening Hours', 'manage_options', 'wl_opening_hours_options',array($this,'toolpage'));
    }

    // Scripts used in the backend
    public function admin_enqueue_scripts($hook) {
       wp_enqueue_script('wl-opening-hours-admin',plugins_url('js/wl-opening-hours-admin.js',__FILE__),array('jquery'),filemtime(dirname(__FILE__) . "/js/wl-opening-hours-admin.js"), 'true');
    }


    // This is for preview of the post type only.
    public function the_content($content) {
        global $post;
        if ($post->post_type != 'wl_opening_hours') return $content;
        return "<div>". do_shortcode('[show-opening-hours id=' . $post->ID . ']') . '</div>';
    }

    public function get_template ($view,$data) {
        if (!$data) return;
         if (count($data) == 1) $view = 'list';
         $path = locate_template("wl-opening-hours/$view.php");
         if (empty($path)) {
           $path = plugin_dir_path(__FILE__) . "templates/$view.php";
         }
         return apply_filters('wl-opening-hours-template-path',$path,$view,$data);
    }

    public function add_meta_boxes () {
        global $post;
        if (!$post) return;
        if ($post->ID) {
            add_meta_box('wl_opening_hours_shortcodes', __('Shortcodes', 'wl-opening-hours'), array($this,'opening_hours_shortcode_metabox'),  'wl_opening_hours', 'side','default');
        }
        add_meta_box('wl_opening_hours_hours', __('Hours', 'wl-opening-hours'), array($this,'hours_metabox'),  'wl_opening_hours', 'normal','default');
        add_meta_box('wl_opening_hours_description', __('Description/Explanation', 'wl-opening-hours'), array($this,'description_metabox'),  'wl_opening_hours', 'normal','default');
    }

    public function get_shortcodes($post) {
        $venues = wp_get_post_terms($post->ID, 'wl_venue', array('fields'=>'slugs'));
        $codes = array();
        $codes[] = "[show-opening-hours id=" . $post->ID . "]";
        if (!empty($venues)) {
            if (count($venues) == 1) {
             $codes[]="[show-opening-hours venue='".$venues[0]."']";
            } else {
             $codes[]="[show-opening-hours venues='".join(',',$venues)."']";
            }
        }
        return $codes;
    }

    public function add_custom_post_status_list() {
       global $post;
       $complete = '';
       $label = '';
       $expired = 0;
       if (!$post) return;
       if($post->post_type != 'wl_opening_hours') return;
       if ($post->post_status == 'wl_hours_expired') {
         $expired = 1; 
         $complete = ' selected=\"selected\" ';
         $label = __('Expired', 'wl-opening-hours');
       }
?>
<script>
      jQuery(document).ready(function($){
           
           jQuery("select#post_status").append("<option value='wl_hours_expired' <?php echo $complete; ?>><?php echo __('Expired','wl-opening-hours'); ?> </option>");
           if (<?php echo $expired; ?>) jQuery("#post-status-display").html('<?php echo $label; ?>');
      });
      </script>
<?php
    }

    public function opening_hours_shortcode_metabox () {
        global $post;
        $shorts = $this->get_shortcodes($post);
        if (!$shorts) return;
        print "<div>";
        print "<p>" . __("Use these shortcodes to use this opening hour element in your pages:",'wl-opening-hours');
        foreach($shorts as $short) {
            print "<code>$short</code><br>";
        }
        print "</p>";
        print "<p>".__("You can use the argument 'view' with values 'list', 'tabbed' or 'accordion'", 'wl-opening-hours') . "</p>";
        print "<p>";
        print __("If you are using this in a template, use ", 'wl-opening-hours');
        print "<code>do_shortcode('[show-opening-hours ...]')</code>";
        print "</p></div>";
    }


    public function description_metabox() {
        global $post;
        $description = get_post_meta($post->ID, 'wl_hours_description', true);
?>        <div id='post-wl-hours-description'>
           <?php wp_nonce_field( 'wl-hours-description',  'wl-hours-description-nonce' ); ?>
           <p><?php _e('Add a description/explanation here if needed - it will be shown in expanded views'); ?></p>
           <textarea name='wl_hours_description' style="width:100%;height:3em" placeholder="<?php _e('Venue is manned only during..'); ?>"><?php echo sanitize_textarea_field($description); ?></textarea>
          </div>
<?php
    }

    public function hours_metabox() {
        global $post;
        $hours = get_post_meta($post->ID, 'wl_hours', true);
        $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');

        ?><div id='post-wl-hours'>
        <?php wp_nonce_field( 'wl-hours',  'wl-hours-nonce' ); ?>
        <div class='opening-hours-entry'>
         <table class='form-table opening-hours-table' style="width:100%">
	  <thead>
            <tr><th> <?php _e('Day','wl-opening-hours'); ?></th><th><?php _e('Morning hours','wl-opening-hours'); ?></th><th><?php _e('Afternoon hours','wl-opening-hours'); ?></th><th><?php _e('Asterisk','wl-opening-hours'); ?></th><th>&nbsp;</th></tr>
          <thead>
          <tbody>
<?php $i=0;foreach ($days as $day): $i++?>
	   <tr>
             <th><?php _e(ucwords($day),'wl-opening-hours');?></th> 
	     <td><input name='wl_hours[<?php echo $day; ?>][morning][from]' value='<?php echo esc_attr(@$hours[$day]['morning']['from']); ?>' type="time">-<input value='<?php echo esc_attr(@$hours[$day]['morning']['to']); ?>' name='wl_hours[<?php echo $day;?>][morning][to]'  type="time" ></td> 
	     <td><input name='wl_hours[<?php echo $day; ?>][afternoon][from]'  value='<?php echo esc_attr(@$hours[$day]['afternoon']['from']); ?>' type="time">-<input value='<?php echo esc_attr(@$hours[$day]['afternoon']['to']); ?>' name='wl_hours[<?php echo $day; ?>][afternoon][to]'  type="time" ></td> 
	     <td><input name='wl_hours[<?php echo $day; ?>][asterisk]' <?php echo @$hours[$day]['asterisk'] ? 'checked' : '' ?> value=yes type=checkbox></td>
	     <td>
                <a class='zeroit' href="javascript:void();">[<?php _e('Remove','wl-opening-hours'); ?>]</a>
                 <?php if ($i>1):?> <a class='copyprev' href="javascript:void();">[<?php _e('Copy previous line','wl-opening-hours'); ?>]</a><?php endif; ?>
             </td>
           </tr>
<?php    endforeach; ?>
          </tbody>
         </table>
        </div>
        </div>
<script>
	jQuery(document).ready(function () {
		jQuery('table.opening-hours-table a.zeroit').click(function (e) {
			e.preventDefault();
			var thisrow = jQuery(this).closest('tr').find('input');
                        jQuery.each(thisrow, function (index,el) {
                          if (el.type == 'checkbox') el.checked= false; else el.value='';
                        });
                        
               
                });
		jQuery('table.opening-hours-table a.copyprev').click(function (e) {
			e.preventDefault();
			var thisrow = jQuery(this).closest('tr')
			var prev = thisrow.prev('tr').find('input');
			var theseinputs = thisrow.find('input');
			jQuery.each(prev, function (index, other) {
			  var mine = theseinputs.get(index);
			  mine.value = other.value;
			  if (other.checked) mine.checked=true; else mine.checked=false;
			});
		});
	});
</script>
<?php
    }


    public function wp_enqueue_scripts() {
        wp_enqueue_script('wl-opening-hours',plugins_url('js/wl-opening-hours.js',__FILE__),array('jquery'),filemtime(dirname(__FILE__) . "/js/wl-opening-hours.js"), 'true');
        wp_enqueue_style('wl-opening-hours',plugins_url('css/wl-opening-hours.css',__FILE__),array(),filemtime(dirname(__FILE__) . "/css/wl-opening-hours.css"));
    }

    public function add_shortcodes() {
         add_shortcode('show-opening-hours', array($this,'hours_shortcode'));
    }
    public function add_custom_post_types(){
        $this->register_post_status();
        $this->register_wl_opening_hours();
    }

    public function plugins_loaded() {
        $ok = load_plugin_textdomain('wl-opening-hours', false, basename( dirname( __FILE__ ) ) . "/languages");
        add_action('admin_head', array($this, 'add_custom_post_status_list'));
    }

    // Validate or format form input for options
    public function validate ($input) {
        $valid = array();
        foreach ($input as $key => $value) {
            switch ($key) {
                default:
                    $valid[$key] = $value;
            }
        }
        return $valid; 
    }
    public function activate () {
        $default = array();
        add_option('wl_opening_hours_options',$default,false);
    }
    public function uninstall() {
        delete_option('wl_opening_hours_options');
    }
    public function toolpage () {
        if (!is_admin() || !current_user_can('manage_options')) {
            die(__("Insufficient privileges",'wl-opening-hours'));
        }
        $options = get_option('wl_opening_hours_options');
        ?>
            <div class='wrap'>
            <h2><?php _e('WL Opening Hours Options', 'wl-opening-hours'); ?></h2>
            <form action='options.php' method='post'>
            <?php settings_fields('wl_opening_hours_options'); ?>
            <table class="form-table" style="width:100%">
            <tr valign="top"><th scope="row"><?php _e('WL Opening Hours Something', 'wl-opening-hours'); ?></th>
            <td><input value='<?php echo sanitize_text_field($options['example']); ?>' type="text" style="width:100%" name="wl_opening_hours_options[example]"  /></td>
            <td> <?php _e('Example Options setting', 'wl-opening-hours'); ?> </td>
            </tr>
            </table>
            <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes','wl-opening-hours') ?>" />
            </p>
            </form>

            </div>
            <?php
    }

    protected function register_post_status () {
        $expiredstatus = array(
                              'label'                     => _x( 'Expired', 'wl-opening-hours'),
                              'post_type'                 => array('wl_opening_hours'),
                              'public'                    => false,
                              'exclude_from_search'       => false,
                              'show_in_metabox_dropdown' => true,
                              'show_in_inline_dropdown'  => true,
                              'show_in_admin_all_list'    => true,
                              'show_in_admin_status_list' => true,
                              'label_count'               => _n_noop('Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'wl-opening-hours' ),

) ;

        register_post_status('wl_hours_expired', $expiredstatus);

    }

    protected function register_wl_opening_hours() {
        $labels = array(
                'name'                  => _x( 'WL Opening Hours', 'Post Type General Name', 'wl-opening-hours' ),
                'singular_name'         => _x( 'WL Opening Hours', 'Post Type Singular Name', 'wl-opening-hours' ),
                'menu_name'             => __( 'WL Opening Hours', 'wl-opening-hours' ),
                'name_admin_bar'        => __( 'WL Opening Hours', 'wl-opening-hours' ),
                'archives'              => __( 'Item Archives', 'wl-opening-hours' ),
                'attributes'            => __( 'Item Attributes', 'wl-opening-hours' ),
                'parent_item_colon'     => __( 'Parent Item:', 'wl-opening-hours' ),
                'all_items'             => __( 'All Items', 'wl-opening-hours' ),
                'add_new_item'          => __( 'Add New Item', 'wl-opening-hours' ),
                'add_new'               => __( 'Add New', 'wl-opening-hours' ),
                'new_item'              => __( 'New Item', 'wl-opening-hours' ),
                'edit_item'             => __( 'Edit Item', 'wl-opening-hours' ),
                'update_item'           => __( 'Update Item', 'wl-opening-hours' ),
                'view_item'             => __( 'View Item', 'wl-opening-hours' ),
                'view_items'            => __( 'View Items', 'wl-opening-hours' ),
                'search_items'          => __( 'Search Item', 'wl-opening-hours' ),
                'not_found'             => __( 'Not found', 'wl-opening-hours' ),
                'not_found_in_trash'    => __( 'Not found in Trash', 'wl-opening-hours' ),
                'featured_image'        => __( 'Featured Image', 'wl-opening-hours' ),
                'set_featured_image'    => __( 'Set featured image', 'wl-opening-hours' ),
                'remove_featured_image' => __( 'Remove featured image', 'wl-opening-hours' ),
                'use_featured_image'    => __( 'Use as featured image', 'wl-opening-hours' ),
                'insert_into_item'      => __( 'Insert into item', 'wl-opening-hours' ),
                'uploaded_to_this_item' => __( 'Uploaded to this item', 'wl-opening-hours' ),
                'items_list'            => __( 'Items list', 'wl-opening-hours' ),
                'items_list_navigation' => __( 'Items list navigation', 'wl-opening-hours' ),
                'filter_items_list'     => __( 'Filter items list', 'wl-opening-hours' ),
                );
        $args = array(
                'label'                 => __( 'WL Opening Hours', 'wl-opening-hours' ),
                'description'           => __( 'A set of opening hours for a venue', 'wl-opening-hours' ),
                'labels'                => $labels,
                'supports'              => array( 'title'),
                'taxonomies'            => array( ),
                'hierarchical'          => true, // Not really, but we want 'dropdown pages'
                'public'                => true,
                'show_ui'               => true,
                'show_in_menu'          => true,
                'menu_position'         => 25,
                'show_in_admin_bar'     => true,
                'show_in_nav_menus'     => true,
                'can_export'            => true,
                'has_archive'           => false,
                'exclude_from_search'   => true,
                'publicly_queryable'    => true,
                'rewrite'               => false,
                'capability_type'       => 'page',
                'show_in_rest'          => false,
                );
        register_post_type( 'wl_opening_hours', $args );

    }

    function add_custom_taxonomies() {
        $labels = array(
                'name'                       => _x( 'Venues', 'Taxonomy General Name', 'wl-opening-hours' ),
                'singular_name'              => _x( 'Venue', 'Taxonomy Singular Name', 'wl-opening-hours' ),
                'menu_name'                  => __( 'Venues', 'wl-opening-hours' ),
                'all_items'                  => __( 'All venues', 'wl-opening-hours' ),
                'parent_item'                => __( 'Parent Item', 'wl-opening-hours' ),
                'parent_item_colon'          => __( 'Parent Item:', 'wl-opening-hours' ),
                'new_item_name'              => __( 'New venue', 'wl-opening-hours' ),
                'add_new_item'               => __( 'Add new venue', 'wl-opening-hours' ),
                'edit_item'                  => __( 'Edit venue', 'wl-opening-hours' ),
                'update_item'                => __( 'Update venue', 'wl-opening-hours' ),
                'view_item'                  => __( 'View venue', 'wl-opening-hours' ),
                'separate_items_with_commas' => __( 'Separate venues with commas', 'wl-opening-hours' ),
                'add_or_remove_items'        => __( 'Add or remove venues', 'wl-opening-hours' ),
                'choose_from_most_used'      => __( 'Choose from the most used', 'wl-opening-hours' ),
                'popular_items'              => __( 'Popular venues', 'wl-opening-hours' ),
                'search_items'               => __( 'Search venues', 'wl-opening-hours' ),
                'not_found'                  => __( 'Not Found', 'wl-opening-hours' ),
                'no_terms'                   => __( 'No venues', 'wl-opening-hours' ),
                'items_list'                 => __( 'Venues list', 'wl-opening-hours' ),
                'items_list_navigation'      => __( 'Venues list navigation', 'wl-opening-hours' ),
                );
        $args = array(
                'labels'                     => $labels,
                'hierarchical'               => false,
                'meta_box_cb'                => 'post_categories_meta_box',
                'public'                     => true,
                'show_ui'                    => true,
                'show_admin_column'          => true,
                'show_in_nav_menus'          => false,
                'show_tagcloud'              => false,
                'rewrite'                    => false,
                'show_in_rest'               => false,
                );
        register_taxonomy( 'wl_venue', array( 'wl_opening_hours' ), $args );
    }

    public function add_admin_notice($notice, $type='info') {
        add_action('admin_notices', function ()  use ($notice,$type)  {
                echo "<div class='notice notice-$type is-dismissible'><p>$notice</p>";
                echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>" . __('Dismiss this message') . "</span></button>";
                echo "</div>";
                });
    }
 
    public function widgets_init () {
       if (class_exists('WLOpeningHoursWidget')) register_widget( 'WLOpeningHoursWidget' );
    }

    // From the custom post type, get the opening hours digested
    protected function create_post_hours_data($post) {
        if (!is_object($post) ||  $post->post_type != 'wl_opening_hours') return;
        $hours = get_post_meta($post->ID, 'wl_hours',true);
        $description = get_post_meta($post->ID, 'wl_hours_description', true);
        $afternoon=0;
        foreach ($hours as $day=>$data) {
          if (!empty($data['afternoon']['from'])) {
            $afternoon=1; break;
          }
        }
        return array('hours'=>$hours,'description'=>$description,'post'=>$post, 'has_afternoon'=>$afternoon);
    }


    // Make sure these words appear in translation tools
    public function translation_helper () {
       _e('monday', 'wl-opening-hours');
       _e('tuesday', 'wl-opening-hours');
       _e('wednesday', 'wl-opening-hours');
       _e('thursday', 'wl-opening-hours');
       _e('friday', 'wl-opening-hours');
       _e('saturday', 'wl-opening-hours');
       _e('sunday', 'wl-opening-hours');
       _e('morning', 'wl-opening-hours');
       _e('afternoon', 'wl-opening-hours');
       _e('asterisk', 'wl-opening-hours');
       _e('description', 'wl-opening-hours');
       _e('All venues','wl-opening-hours');
    }

    // Get the data for all venues as an array. Used by shortcodes and widgets.
    public function get_opening_hour_data($id=0,$venues=array()) {
        if (!$id && empty($venues)) return;
        $result = array();
        if ($id) {
           $post = get_post($id);
           $entry = $this->create_post_hours_data($post);
           if (!$entry) return;
           $result['ALLVENUES'] = $entry;
        } elseif (!empty($venues)) {
          foreach($venues as $v) {
             $v = is_numeric($v) ? intval($v) : $v;
             $type = is_int($v) ? 'term_id' : 'slug';
             $tax = get_term_by($type, $v, 'wl_venue');
             if (!$tax) continue;
             $taxentry = array();
             $taxname = $tax->name;
             $taxqueryargs = array('post_type'=>'wl_opening_hours', 
                                   'posts_per_page'=>1, 
                                   'tax_query'=>array('relation'=>'OR',array('taxonomy'=>'wl_venue', 'field'=>$type,'terms'=>$v)),
                                   'post_status'=>'publish',
                                   'orderby'=>'date','order'=>'DESC');
             // Most recent published post is what this is.
             $posts = get_posts($taxqueryargs);
             $post = !empty($posts) ? $posts[0] : null; 
             if ($post) {
                $entry = $this->create_post_hours_data($post);
                if ($entry) {
                   $result[$taxname] = $entry;
                }
             }
          }
        }
        if ($result) return $result;
        return "";
    }

    public function hours_shortcode($attributes, $content="") {
        $atts = shortcode_atts(array('id'=>0, 'venue'=>'','venues'=>'','view'=>'list'),$attributes,'show-opening-hours');
        $postid = $atts['id'];
        $view = $atts['view'];
        $venues = array();
        if ($atts['venue']) $venues[]=trim($atts['venue']);
        if ($atts['venues']) {
          $others = explode(",",$atts['venues']);
          foreach ($others as $other) $venues[]= trim($other);
        }
        $venues = array_unique($venues);
        return $this->show_opening_hours($postid,$venues,$view);
    }

    public function show_opening_hours($postid,$venues,$view='list') {
       if (!$postid && empty($venues)) $venues = array('ALLVENUES');
       if (!$postid && !empty($venues) && in_array('ALLVENUES',$venues)) {
          $allvenues = get_terms('wl_venue', array('hide_empty'=>false));
          $venues = array();
          foreach($allvenues as $v) $venues[] = $v->slug;
       }

       $opening_hours = $this->get_opening_hour_data($postid,$venues);
       $template = $this->get_template($view, $opening_hours);
       ob_start();
       if (is_file($template)) include($template);
       $hours = ob_get_clean();
       return apply_filters('wl_opening_hours_view', $hours, $opening_hours, $view);
    }

    public function save_post($postid,$post,$update) {
        if ($post->post_type != 'wl_opening_hours') return;
        if ( ! current_user_can( 'edit_post', $postid )) {
            return $postid;
        }

        if (isset( $_POST['wl_hours']) && ! wp_verify_nonce($_POST['wl-hours-nonce'], 'wl-hours')) {
            return $postid;
        }
        if (isset( $_POST['wl_hours_description']) && ! wp_verify_nonce($_POST['wl-hours-description-nonce'], 'wl-hours-description')) {
            return $postid;
        }
        if (isset( $_POST['wl_hours_description'])) {
            $desc = sanitize_text_field($_POST['wl_hours_description']);
            update_post_meta($postid,'wl_hours_description',$desc);
        }

        // This should be done by WP itself, but apparently some combination of plugins can break it.  IOK 2019-10-17
        if (isset($_POST['tax_input'])) {
          $tax = $_POST['tax_input'];
          if (isset($tax['wl_venue'])) { 
             $ok = wp_set_post_terms($postid, $tax['wl_venue'], 'wl_venue', false);
          }
        }

        if (isset( $_POST['wl_hours'])) {
		$weekday = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday','saturday','sunday');
		$hours = array();
		foreach($weekday as $day) {
			$hours[$day] = array();
			$hours[$day]['morning'] =array();
			$hours[$day]['afternoon'] =array();
			$hours[$day]['asterisk'] = isset($_POST['wl_hours'][$day]['asterisk']) ? sanitize_text_field($_POST['wl_hours'][$day]['asterisk']) : '';
			$hours[$day]['morning']['from'] = sanitize_text_field($_POST['wl_hours'][$day]['morning']['from']);
			$hours[$day]['morning']['to'] = sanitize_text_field($_POST['wl_hours'][$day]['morning']['to']);
			$hours[$day]['afternoon']['from'] = sanitize_text_field($_POST['wl_hours'][$day]['afternoon']['from']);
			$hours[$day]['afternoon']['to'] = sanitize_text_field($_POST['wl_hours'][$day]['afternoon']['to']);
		}
                update_post_meta($postid,'wl_hours',$hours);
        }
    }

    public function footer () {
    }

}
