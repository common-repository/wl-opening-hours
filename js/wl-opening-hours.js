jQuery(document).ready(function () {
 /* Tab handling */
 jQuery('.wl-opening-hours.tabbed .tabs .wl-opening-hours-tab').click(function (e) {
    e.preventDefault();
    var tablist = jQuery(this).closest('.tabs');
    var index = tablist.find('.wl-opening-hours-tab').index(this);
    tablist.find('.wl-opening-hours-tab').removeClass('active');
    jQuery(this).addClass('active');
    var displays = tablist.closest('.wl-opening-hours.tabbed').find('.tabdisplay');
    displays.removeClass('active');
    displays.eq(index).addClass('active');
    
 })
 /* Accordion handling */
 jQuery('.wl-opening-hours.accordion .accordiondisplay').click(function (e) {
    e.preventDefault();
    //jQuery(this).closest('.accordiondisplay').toggleClass('active');
    jQuery(this).toggleClass('active');
 });

});

