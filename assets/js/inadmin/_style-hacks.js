window.Inachis.Style = {
    _init() {
        $('.material-icons').filter((i, el) => $(el).text() === 'check_box').addClass('checkbox__checked');
    }
};

$(document).ready(() => {
    window.Inachis.Style._init();
});
