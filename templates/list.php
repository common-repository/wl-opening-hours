<?php
/*
   Template for list views of opening hours

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

$today = intval(date('N'));

?>
<div class="wl-opening-hours list">
<?php foreach ($opening_hours as $where=>$data): $i=0; ?>
 <h3>
<?php if ($where != 'ALLVENUES'): ?>
<?php print esc_html($where); ?>
<?php else: ?>
<?php print get_the_title($data['post']); ?>
<?php endif; ?>
 </h3>
  <table class='wl-opening-hours'>
   <tbody>
 <?php foreach ($data['hours'] as $day=>$entry): $i++; ?>
    <tr class='wl-day-entry<?php if ($today==$i) echo ' current ';?>'>
      <td class='day'><?php echo trim(ucfirst(__($day,'wl-opening-hours'))); ?>
          <?php if ($entry['asterisk'] == 'yes'):?> <span class='asterisk'>*</span><?php endif;?>
      </td>
      <td class='morning'><?php echo esc_html($entry['morning']['from']);?>-<?php echo esc_html($entry['morning']['to']);?></td>
<?php if ($data['has_afternoon']): ?>
      <td class='afternoon'>
        <?php if ($entry['afternoon']['from']):?>
           <?php echo esc_html($entry['afternoon']['from']);?>-<?php echo esc_html($entry['afternoon']['to']);?>
        <?php endif; ?>
      </td>
<?php endif; ?>
   </tr>
 <?php endforeach; ?>
  </tbody>
</table>
 <div class="description"><p><?php echo esc_html($data['description']); ?></p></div>
<?php endforeach; ?>
</div>
