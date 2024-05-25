<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOSL | Ruban E.</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="img/icon.png">
</head>

<body>
    <header>
        <a href="index.php"><h2 class="logo">BOOSL</h2></a>

        <input type="checkbox" id="check">
        <label for="check" class="icons"><ion-icon name="menu" id="menu-icon"></ion-icon></label>
        <div class="box">
            <nav class="navigation">
                <a href="#">Для кого</a>
                <a href="#">Можливості</a>
                <a href="contact.php">Контакти</a>
            </nav>
            <div class="btn-nav">
                <button class="btnLogin-popup">Вхід</button>
            </div>
        </div>
    </header>

    <div class="wrapper">
        <span class="icon-close"><ion-icon name="close"></ion-icon></span>

        <div class="form-box login">
            <h2>Увійти в акаунт</h2>
            <form id="login-form">
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail-open"></ion-icon></span>
                    <input type="text" id="login" required>
                    <label>Логін або електронна пошта</label>
                </div>
                <div class="input-box">
                    
                    <input type="password" id="password" required>
                    <label>Пароль</label>
                    <span class="icon toggle-password"><ion-icon name="eye-outline"></ion-icon></span>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox">Запам'ятати мене</label>
                    <a href="#">Забули пароль?</a>
                </div>
                <button type="submit" class="btn">Авторизуватися</button>
                <div class="login-register">
                    <p>Немає акаунту? <a href="#" class="register-link">Зареєструйтеся</a></p>
                </div>
                <p id="error-message" style="color: red; display: none;">Неправильний логін або пароль</p>
            </form>
        </div>

        <div class="form-box register">
            <h2>Реєстрація</h2>
            <form action="#">
                <div class="input-box">
                    <span class="icon"><ion-icon name="person"></ion-icon></span>
                    <input type="text"  required>
                    <label>Логін</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail-open"></ion-icon></span>
                    <input type="text" required>
                    <label>Електронна пошта</label>
                </div>
                <div class="input-box">
                    
                    <input type="password-2" id="password-2" required>
                    <label>Пароль</label>
                    <span class="icon toggle-password"><ion-icon name="eye-outline"></ion-icon></span>
                </div>
                <button type="submit" class="btn" onclick='location.href="report.php"'>Зареєструватися</button>
                <div class="login-register">
                    <p>Вже є акаунт? <a href="#" class="login-link">Авторизуватися</a></p>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="footerContainers secondBlock">
            <div class="footerContainersBlocks" id="contacts">
                <h4>Контакти</h4>
                <ul>
                    <li><a href="https://t.me/minignom">Telegram</a></li>
                </ul>
            </div>
            <div class="footerContainersBlocks">
                <h4>Зворотній зв'язок</h4>
                <ul>
                    <li><a href="./contact.php">Відгуки, питання і відповіді</a></li>
                </ul>
            </div>
            
            <div class="footerContainersBlocks">
                <h4>Не забудьте підписатися<br></h4>
                <a href="https://www.instagram.com/lisaveettaaa/">
                    <span class="icon-inst"><ion-icon name="logo-instagram"></ion-icon></span>
                </a>
            </div>
            <div class="footerContainers firstBlock">
                <p>2024 Рубан Є. С. | lrozzenberg@gmail.com<br></p>
            </div>
        </div>
    </footer>     

    <script src="script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>
