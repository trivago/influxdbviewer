$(document).ready(function() {

    var _sidebar = $('#js_sidebar');
    var _header = $('#js_sidebar header');

    _header.click(function() {

        if(!_sidebar.hasClass('disabled'))
        {
            _sidebar.animate({
                width: 20
            }, 100, function() {
                _sidebar.removeAttr('style');
            });
            _sidebar.addClass('disabled');
        }
        else
        {
            _sidebar.animate({
                width: 250
            }, 100, function() {
                // Animation complete.
                _sidebar.removeClass('disabled');
                _sidebar.removeAttr('style');
            });
        }
    });
});