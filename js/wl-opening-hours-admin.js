/*
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


function wl_opening_setup_widget(widget) {
 var val = widget.find('.wl_opening_hours_selector').val();
 if (val) {
   widget.find('.wl-venue-checkbox').prop('disabled',true);
 } else {
   widget.find('.wl-venue-checkbox').prop('disabled',false);
 }
}

console.log("WL Opening Hours admin scripts loaded");
