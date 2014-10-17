jQuery(document).ready(function($) {
    $('.add-contact').click(function(e) {
        e.preventDefault();
        $('.hidden-form').slideDown();
    });
});