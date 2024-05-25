const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');

const btnPopup = document.querySelector('.btnLogin-popup');
const iconClose = document.querySelector('.icon-close');

registerLink.addEventListener('click', () => {
    wrapper.classList.add('active');
});

loginLink.addEventListener('click', () => {
    wrapper.classList.remove('active');
});

btnPopup.addEventListener('click', () => {
    wrapper.classList.add('active-popup');

    var checkElement = document.getElementById("check");
    if (checkElement) {
        checkElement.checked = false;
    }
});

iconClose.addEventListener('click', () => {
    wrapper.classList.remove('active-popup');
});

document.getElementById('login-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const login = document.getElementById('login').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('error-message');

    if (login === 'admin' && password === '12345') {
        window.location.href = 'report.php';
    } else {
        errorMessage.style.display = 'block';
    }
});

const togglePassword = document.querySelector('.toggle-password');
togglePassword.addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const passwordFieldType = passwordField.getAttribute('type');
    if (passwordFieldType === 'password') {
        passwordField.setAttribute('type', 'text');
        togglePassword.innerHTML = '<ion-icon name="eye-off-outline"></ion-icon>';
    } else {
        passwordField.setAttribute('type', 'password');
        togglePassword.innerHTML = '<ion-icon name="eye-outline"></ion-icon>';
    }
});
