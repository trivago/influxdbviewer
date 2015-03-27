$(document).ready(function() {

    //Ladda.bind( 'input[type=submit]', { timeout: 2000 } );

    var _sidebar = $('#js_sidebar');
    var _header = $('#js_sidebar header');
    var _section = $('.main-content');

    _header.click(function() {

        if(!_sidebar.hasClass('disabled'))
        {
            _sidebar.addClass('disabled');
            _section.removeClass('is-open')
        }
        else
        {
            _section.addClass('is-open')
            _sidebar.removeClass('disabled');
        }
    });
});