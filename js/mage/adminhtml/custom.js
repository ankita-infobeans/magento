jQuery( document ).ready(function() {
    addvalidation();
    jQuery( "#has_copyright" ).change(function() {
        addvalidation();
    });
});

function addvalidation() {
    if (jQuery("#has_copyright :selected").text() == 'Yes') {
        jQuery('#icc_copyright_holder').addClass('required-entry');
    } else {
        jQuery('#icc_copyright_holder').removeClass('required-entry');
    }
}