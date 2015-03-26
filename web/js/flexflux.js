$(document).ready(function() {

    $("#js_sidebar").click(function() {
        if(!$(this).hasClass('disabled'))
        {
            /*
            $("#js_sidebar").animate({
                width: 20
            }, 100, function() {
                $("#js_sidebar").removeAttr('style');
            });
            $(this).addClass('disabled');
            */
        }
        else
        {

            $("#js_sidebar").animate({
                width: 200
            }, 100, function() {
                // Animation complete.
                $(this).removeClass('disabled');
                $("#js_sidebar").removeAttr('style');
            });
        }
    });
});