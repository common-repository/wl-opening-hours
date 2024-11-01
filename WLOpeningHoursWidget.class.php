<?php
/*
   This class is for the opening hour widgets

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


class WLOpeningHoursWidget extends WP_Widget {
   public function __construct() {
        // widget actual processes
        parent::__construct(
            'wl_opening_hours_widget', // Base ID
            __( 'Opening Hours', 'wl-opening-hours' ), // Name
            array( 'description' => __( 'Show Opening Hours', 'telenorgroup' ), ) // Args
        );
  }

  public function widget( $args, $instance ) {
     $hours = WLOpeningHours::instance();
     $title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );
     $venues= $instance['venues'];
     $view = $instance['view'];
     $postid = $instance['postid'];

     
     echo $args['before_widget'];
     echo "<div class='wl-opening-hours-display'>";
     if (!empty($title)) echo "<h2>" . htmlspecialchars($title); echo "</h2>";
     echo $hours->show_opening_hours($postid,$venues,$view);
     echo "</div>";
     echo $args['after_widget'];

  }

   public function form( $instance ) {
        $title = !empty( $instance['title'] ) ? $instance['title'] : __( 'Title', 'wl-opening-hours' );
        $venues = !empty( $instance['venues'] ) ? $instance['venues'] : array();
        $postid = ! empty( $instance['postid'] ) ? $instance['postid'] : 0;
        $view = ! empty( $instance['view'] ) ? $instance['view'] : 'tabbed';
        $allvenues = get_terms('wl_venue', array('hide_empty'=>false));
        if (empty($venues && !$postid)) $venues[] = 'ALLVENUES';
        $standardviews = array('tabbed'=>__('Tabbed','wl-opening-hours'),'accordion'=>__('Accordion','wl-opening-hours'), 'list'=>__('List','wl-opening-hours'));
        if ($view && !array_key_exists($view,$standardviews)) $standardviews[$view]=$view;
        $views = apply_filters('wl_opening_hours_views',$standardviews);

?>
<div class='wl-opening-hours-config'>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:', 'wl-opening-hours' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
<h2><?php _e('Single opening hour entry','wl-opening-hours'); ?><h2>
<p>
 <?php 
$dropdownargs= array('show_option_none'=>__('None','wl-opening-hours'),
                     'post_type'=>'wl_opening_hours', 'class'=>'wl_opening_hours_selector',
                     'depth'=>1,'selected'=>$postid,'echo'=>true,'name'=>$this->get_field_name('postid'), id=>$this->get_field_id('postid'));

wp_dropdown_pages($dropdownargs);
?>
 
</p>
        <h2><?php _e('Or show for these venues:', 'wl-opening-hours'); ?></h2>
        <p>
<table style="width:100%">
 <tbody>
  <tr>
      <td><?php _e('All venues','wl-opening-hours');?>:</td>
      <td><input class='wl-venue-checkbox all' id="<?php echo $this->get_field_id('venues');?>-ALLVENUES" type=checkbox value='ALLVENUES' <?php if (in_array('ALLVENUES',$venues)) echo " checked "; ?> name="<?php echo $this->get_field_name('venues'); ?>[]"></td>
  </tr>
<?php foreach ($allvenues as $venue): ?>
  <tr>
      <td><?php echo $venue->name; ?>:</td>
      <td><input class='wl-venue-checkbox others' id="<?php echo $this->get_field_id('venues');?>-<?php echo $venue->slug; ?>" <input type=checkbox value='<?php echo $venue->slug;?>' <?php if (in_array($venue->slug,$venues)) echo " checked "; ?> name="<?php echo $this->get_field_name('venues'); ?>[]"></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
        </p>

<h2><?php _e('If showing several sets of opening hours, use these views','wl-opening-hours');?></h2>
<?php foreach($views as $k=>$v) : ?>
<label for="<?php echo $this->get_field_id('view');?>-<?php echo $k;?>"><?php echo htmlspecialchars($v); ?></label>
<input <?php if ($view==$k) echo " checked "; ?>  type=radio name="<?php echo $this->get_field_name('view'); ?>" id="<?php echo $this->get_field_id('view');?>-<?php echo $k;?>"
       class="wl-opening-hours-view" value="<?php echo esc_attr($k);?>">
<?php endforeach; ?>
<p>

</p>

</div>
        
<script>
 jQuery('#<?php echo $this->get_field_id('postid');?>').change(function () {
   wl_opening_setup_widget(jQuery(this).closest('.widget'));
 });
 jQuery('#<?php echo $this->get_field_id('venues');?>-ALLVENUES').closest('.widget').find('.wl-venue-checkbox').change(function () {
   if (jQuery(this).hasClass('all')) {
   } else {
    var otherschecked = jQuery(this).closest('.widget').find('.wl-venue-checkbox.others:checked').length != 0;
    jQuery(this).closest('.widget').find('.wl-venue-checkbox.all').prop('checked',!otherschecked);
   }
 });
</script> 
        <?php
   }


}
