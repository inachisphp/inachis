window.Inachis.Style = {
    init() {
        document.querySelectorAll('.material-icons').forEach(el => {
            if (el.textContent === 'check_box') {
                el.classList.add('checkbox__checked');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.Inachis.Style.init();
});
