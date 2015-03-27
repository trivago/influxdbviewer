$(document).ready(function() {

    var _menubutton = $('.menu-toggle');
    var _section = $('.main-content');

    _menubutton.click(function() {
        $(this).toggleClass('is-active');
        _section.toggleClass('is-open');
    });
});