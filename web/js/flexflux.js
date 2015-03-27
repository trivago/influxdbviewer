$(document).ready(function() {

    var _sidebar = $('#js_sidebar');
    var _header = $('#js_sidebar header');
    var _section = $('.main-content');

    _header.click(function() {

        if(!_sidebar.hasClass('disabled'))
        {
            _sidebar.animate({
                width: 20
            }, 100, function() {
                _sidebar.removeAttr('style');
            });
            _sidebar.addClass('disabled');
            _section.removeClass('is-open')
        }
        else
        {
            _sidebar.animate({
                width: 250
            }, 100, function() {
                // Animation complete.
                _section.addClass('is-open')
                _sidebar.removeClass('disabled');
                _sidebar.removeAttr('style');
            });
        }
    });
});