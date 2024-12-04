document.addEventListener('DOMContentLoaded', function () {
    console.log('acuvueAuth');
    var acuvueclick = document.querySelectorAll('[data-target="acuvueAuth"]');
    acuvueclick.forEach(function (value, key, parent) {
        value.addEventListener("click", function () {
            document.getElementById('myAcuvueAuth').classList.add('show');
        });
    });

});

function closeAcuvuePopup() {
    document.getElementById('myAcuvueAuth').classList.remove('show');
    if (!document.getElementById('myAcuvueAuth').querySelector('[type="submit"]')) {
        location.reload();
    }
}

function ajaxFormAcuvue(form, event) {
    event.preventDefault();
    let popup = document.getElementById('myAcuvueAuth'),
        ajaxHttp = new XMLHttpRequest();
    popup.querySelector('form').classList.add('load');
    ajaxHttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            popup.innerHTML = this.responseText;
            popup.classList.add('show');
        }
    };
    ajaxHttp.open("POST", "", true);
    ajaxHttp.send(new FormData(form));
}