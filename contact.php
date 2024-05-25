<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" conrent="width=device-width, initial-scale =1.0">
    <title>BOOSL Контакти | Ruban E.</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="img/icon.png">
</head>

<body>
    <header>
        <a href="index.php"><h2 class="logo">BOOSL | Контакти </h2></a>

        <input type="checkbox" id="check">
        <label for="check" class="icons"> <ion-icon name="menu"></ion-icon></label>
        <div class="box">
       <nav class="navigation">
        <a href="#">Для кого</a>
        <a href="#">Можливості</a>
        <a href="contact.php">Контакти</a>
       </nav>
    </div>
    </header>

    <section class="contact">
        <h2>Є питання чи пропозиції?<br> Напиши нам!</h2>

        <form action="https://api.web3forms.com/submit" method="POST" class="form-ask">
            <input type="hidden" name="access_key" value="df978d0a-18ce-4568-b303-79d7d3ee6b80">
            <div class="input-ask-box">
                <div class="input-field field">
                    <input type="text" placeholder="Ваше ім'я" id="name" name="name"
                    class="item" autocomplete="off" required>
                </div>
                <div class="input-field field">
                    <input type="email" placeholder="Електронна пошта" id="email" name="email"
                    class="item" autocomplete="off" required>
                </div>
            </div>
            <div class="input-ask-box">
                <div class="input-field field">
                    <input type="text" placeholder="Номер телефону" id="phone" name="phone"
                    class="item" autocomplete="off">
                </div>
                <div class="input-field field">
                    <input type="text" placeholder="Тема" id="subject" name="subject"
                    class="item" autocomplete="off" required>
                </div>
            
            </div>

            <div class="textarea-field field">
                <textarea name="message" id="message" cols="30" rows="10"
                placeholder="Ваше повідомлення" class="item" 
                autocomplete="off" required></textarea>
            </div>
            <input type="hidden" name="redirect" value="https://web3forms.com/success">
            <button type="submitText" class="submitText">Відправити</button>
        </form>
    </section>

    <footer>
        <div class="footerContainers secondBlock">
            <div class="footerContainersBlocks" id="contacts">
                <h4>
                    Контакти
                </h4>
                <ul>
                    <li><a href="https://t.me/minignom" >Telegram</a></li>
                </ul>
            </div>
            <div class="footerContainersBlocks">
                <h4>
                    Зворотній зв'язок
                </h4>
                <ul>
                    <li><a href="./contact.php">Відгуки, питання і відповіді</a></li>
                </ul>
            </div>
            
            <div class="footerContainersBlocks">
                <h4>
                    Не забудьте підписатися<br>
                </h4>
                
                <a href="https://www.instagram.com/lisaveettaaa/">
                    <span class="icon"><ion-icon name="logo-instagram"></ion-icon></span>
                </a>
            </div>
            <div class="footerContainers firstBlock">
            <p>
                
                    2024 Рубан Є. С.   |    lrozzenberg@gmail.com<br>
                    
                </a>
            </p>
        </div>
        </div>
    </footer>              
    <script src="script.js"></script>
    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>