<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOSL Звіти | Ruban E.</title>
    <link rel="stylesheet" href="style_main.css">
    <link rel="icon" type="image/png" href="img/icon.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <a href="index.php"><h2 class="logo">BOOSL</h2></a>
        <input type="checkbox" id="check">
        <label for="check" class="icons"><ion-icon name="menu"></ion-icon></label>
        <div class="box">
            <nav class="navigation">
                <a href="#">Для кого</a>
                <a href="#">Можливості</a>
                <a href="./contact.php">Контакти</a>
            </nav>
        </div>
    </header>

    <div class="menuVertical-storage">
        <nav id="menuVertical">
            <ul>
                <li><a href="main.php"><div class="img_acc"><img src="img/acc.jpg"></div><span></span></a></li>
                <li><a href="report.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Звіти</span></a></li>
                <li><a href="buy_storage.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Продажі</span></a></li>
                <li><a href="./index_storage.php#tab-01"><div class="img_n"><img src="img/acc.jpg"></div><span>Склад</span></a></li>
                <li><a href="./index.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Вихід</span></a></li>
            </ul>
        </nav>
    </div>

    <div class="report-container">
        <div class="report-text">
            <h1>Дохід за місяць</h1>
            <form method="GET" action="report.php">
                <label for="month">Виберіть місяць:</label>
                <select id="month" name="month" style="background: #f0f0fb">
                    <?php
                    $months = ['Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'];
                    $current_month = date('n');
                    foreach ($months as $index => $month) {
                        $selected = ($index + 1 == $current_month) ? 'selected' : '';
                        echo "<option value='" . ($index + 1) . "' $selected>$month</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="choose-month">Показати</button>
            </form>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Місяць</th>
                        <th>Витрачено на закупку (грн)</th>
                        <th>Сумарно продано (грн)</th>
                        <th>Дохід (грн)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include "db_storage.php";

                    $selected_month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;

                    // Отримати дані про продажі та деталі продажів за обраний місяць
                    $query = "
                        SELECT 
                            MONTH(s.date) AS month,
                            SUM(p.price_purchase * sd.quantity) AS total_purchase,
                            SUM(sd.price * sd.quantity) AS total_sale,
                            SUM(sd.price * sd.quantity - p.price_purchase * sd.quantity) AS profit
                        FROM sales s
                        JOIN sales_details sd ON s.id = sd.sale_id
                        JOIN products_id p ON sd.product_id = p.id
                        WHERE MONTH(s.date) = $selected_month
                        GROUP BY MONTH(s.date)
                        ORDER BY MONTH(s.date)
                    ";

                    $result = mysqli_query($link, $query);

                    $data = mysqli_fetch_assoc($result);

                    echo "<tr>
                            <td>{$months[$selected_month - 1]}</td>
                            <td>" . number_format($data['total_purchase'], 2) . "</td>
                            <td>" . number_format($data['total_sale'], 2) . "</td>
                            <td>" . number_format($data['profit'], 2) . "</td>
                        </tr>";

                    // Отримати дані про продажі та деталі продажів для всіх місяців для діаграми
                    $query_for_chart = "
                        SELECT 
                            MONTH(s.date) AS month,
                            SUM(sd.price * sd.quantity - p.price_purchase * sd.quantity) AS profit
                        FROM sales s
                        JOIN sales_details sd ON s.id = sd.sale_id
                        JOIN products_id p ON sd.product_id = p.id
                        GROUP BY MONTH(s.date)
                        ORDER BY MONTH(s.date)
                    ";

                    $result_for_chart = mysqli_query($link, $query_for_chart);

                    $monthly_profits = [];
                    while ($row = mysqli_fetch_assoc($result_for_chart)) {
                        $monthly_profits[$row['month']] = $row['profit'];
                    }

                    // Заповнити відсутні місяці нулями
                    for ($i = 1; $i <= 12; $i++) {
                        if (!isset($monthly_profits[$i])) {
                            $monthly_profits[$i] = 0;
                        }
                    }

                    // Сортування дані для діаграми за правильним порядком місяців
                    ksort($monthly_profits);

                    mysqli_close($link);
                    ?>
                </tbody>
            </table>
        </div>
        <div class="report-chart">
            <canvas id="incomeChart"></canvas>
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
                    <span class="icon"><ion-icon name="logo-instagram"></ion-icon></span>
                </a>
            </div>
            <div class="footerContainers firstBlock">
                <p>2024 Рубан Є. С. | lrozzenberg@gmail.com<br></p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('incomeChart').getContext('2d');
        const chartData = {
            labels: ['Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'],
            datasets: [{
                label: 'Чистий прибуток (грн)',
                data: <?php echo json_encode(array_values($monthly_profits)); ?>,
                backgroundColor: 'rgba(51, 96, 135, 0.7)',
                borderColor: 'rgba(33, 61, 85, 1)',
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
    </script>
    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
