window.Inachis.Style = {
    _init: function () {
        $('.material-icons').filter(function() {
            return $(this).text() === 'check_box';
        }).addClass('checkbox__checked');
    }
};

$(document).ready(function () {
    window.Inachis.Style._init();
});
