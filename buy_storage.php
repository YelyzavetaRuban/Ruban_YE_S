<?php
include "db_storage.php";

// Функція для отримання продуктів з наявністю
function getProductsInStock($link, $search_query) {
    $query = "SELECT * FROM `products_id` WHERE `amount` > 0";
    if ($search_query) {
        $query .= " AND (`name` LIKE '%$search_query%' OR `article` LIKE '%$search_query%' OR `barcode` LIKE '%$search_query%')";
    }

    $result_products = mysqli_query($link, $query);
    if (!$result_products) {
        die("Помилка з'єднання: " . mysqli_error($link));
    }
    return $result_products;
}

// Обробка запиту на створення продажу
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $products = $data['products'];
    $payment_type = mysqli_real_escape_string($link, $data['payment_type']);
    $total_amount = floatval($data['total_amount']);
    $cash_received = isset($data['cash_received']) ? floatval($data['cash_received']) : 'NULL';
    $change = $cash_received !== 'NULL' ? $cash_received - $total_amount : 'NULL';

    if (empty($products)) {
        echo json_encode(['success' => false, 'error' => 'Необхідно додати хоча б один товар для створення продажу.']);
        exit;
    }

    foreach ($products as $product) {
        $product_id = intval($product['id']);
        $quantity = intval($product['quantity']);

        // Перевірка наявності товару
        $query = "SELECT amount FROM products_id WHERE id = $product_id";
        $result = mysqli_query($link, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            if ($row['amount'] < $quantity) {
                echo json_encode(['success' => false, 'error' => 'Недостатньо товарів на складі для продукту ID: ' . $product_id]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }
    }

    // Вставка продажу
    $query = "INSERT INTO sales (total, payment_type, cash_received, `change`) VALUES ($total_amount, '$payment_type', $cash_received, $change)";
    $result = mysqli_query($link, $query);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
        exit;
    }
    $sale_id = mysqli_insert_id($link);

    foreach ($products as $product) {
        $product_id = intval($product['id']);
        $price = floatval($product['price']);
        $quantity = intval($product['quantity']);
        $total = $price * $quantity;

        // Вставка деталей продажу
        $query = "INSERT INTO sales_details (sale_id, product_id, price, quantity, total) VALUES ($sale_id, $product_id, $price, $quantity, $total)";
        $result = mysqli_query($link, $query);
        if (!$result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }

        // Оновлення залишків
        $query = "UPDATE products_id SET amount = amount - $quantity WHERE id = $product_id";
        $update_result = mysqli_query($link, $query);

        if (!$update_result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }
    }

    echo json_encode(['success' => true]);
    exit;
}


// Обробка запиту на отримання історії продажів
if (isset($_GET['action']) && $_GET['action'] === 'get_sales') {
    $query = "SELECT sales.*, GROUP_CONCAT(sales_details.product_id SEPARATOR ', ') AS product_ids, GROUP_CONCAT(sales_details.price SEPARATOR ', ') AS prices, GROUP_CONCAT(sales_details.quantity SEPARATOR ', ') AS quantities, GROUP_CONCAT(sales_details.total SEPARATOR ', ') AS totals 
              FROM sales 
              JOIN sales_details ON sales.id = sales_details.sale_id 
              GROUP BY sales.id 
              ORDER BY sales.date DESC";
    $result = mysqli_query($link, $query);

    $sales = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['payment_type'] = $row['payment_type'] === 'cash' ? 'Готівковий' : 'Безготівковий';
        $sales[] = $row;
    }

    echo json_encode(['sales' => $sales]);
    exit;
}

// Обробка запиту на пошук товарів
if (isset($_GET['action']) && $_GET['action'] === 'search_products') {
    $search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $result_products = getProductsInStock($link, $search_query);

    $products = [];
    while ($row = mysqli_fetch_assoc($result_products)) {
        $products[] = $row;
    }

    echo json_encode(['products' => $products]);
    exit;
}

// Обробка запиту на отримання деталей продажу
if (isset($_GET['action']) && $_GET['action'] === 'get_sale_details' && isset($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    $query = "SELECT s.date, s.total, s.payment_type, s.cash_received, s.change, sd.product_id, p.name as product_name, sd.price, sd.quantity, sd.total 
              FROM sales s
              JOIN sales_details sd ON s.id = sd.sale_id
              JOIN products_id p ON sd.product_id = p.id
              WHERE s.id = $sale_id";
    $result = mysqli_query($link, $query);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
        exit;
    }

    $sale_details = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sale_details[] = $row;
    }

    echo json_encode(['success' => true, 'sale' => $sale_details]);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOSL Продажі | Ruban E.</title>
    <link rel="stylesheet" href="style_main.css">
    <link rel="icon" type="image/png" href="img/icon.png">
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
                <li><a href="./report.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Звіти</span></a></li>
                <li><a href="buy_storage.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Продажі</span></a></li>
                <li><a href="./index_storage.php#tab-01"><div class="img_n"><img src="img/acc.jpg"></div><span>Склад</span></a></li>
                <li><a href="./index.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Вихід</span></a></li>
            </ul>
        </nav>
    </div>

    <div class="wrapper-storage-sale">
        <div class="search">
        <button onclick="showNewSaleForm()" class="btn-new-sale">Новий продаж</button>
        </div>
        <h2>Історія продажів</h2> <br>
        <div class="content-storage-sale">
            
            <div class="history" id="history">
                
                <table>
                    <thead>
                        <tr>
                            <th> </th>
                            <th>Дата</th>
                            <th>Сума</th>
                            <th>Тип оплати</th>
                        </tr>
                    </thead>
                    <tbody id="sales-history">
                        <!-- Динамічний контент з історією продажів-->
                    </tbody>
                </table>
            </div>
            <div class="new-sale" id="new-sale-form">
            <button onclick="showNewSaleForm()" class="exit-sale"><ion-icon name="arrow-back-outline"></ion-icon></button>
                <h2>Новий продаж</h2>
                <form id="sale-form">
                    <div class="form-group">
                        <label for="product-search">Пошук товару</label>
                        <input type="text" id="product-search" onkeyup="searchProduct()" placeholder="Введіть назву товару...">
                        <div class="product-list" id="product-list"></div>
                    </div>
                    <div class="selected-products" id="selected-products"></div>
                    <div class="form-group">
                        <label for="payment-type">Тип оплати</label>
                        <select id="payment-type" name="payment_type" required>
                            <option value="cash">Готівка</option>
                            <option value="card">Безготівковий</option>
                        </select>
                    </div>
                    <div class="form-group" id="cash-payment">
                        <label for="cash-received">Отримано готівки</label>
                        <input type="number" id="cash-received" name="cash_received" oninput="calculateChange()">
                        <label for="change">Решта</label>
                        <input type="number" id="change" name="change" readonly>
                    </div>
                    <button type="button" onclick="submitSale()" class="btn-sale-OK">ОК</button>
                </form>
            </div>

            <!-- Модальне вікно для деталей продажу -->
            <div class="sale-details" id="sale-details-form">
                <button onclick="closeSaleDetails()" class="exit-sale"><ion-icon name="arrow-back-outline"></ion-icon></button>
                <h2>Деталі продажу</h2>
                <div id="sale-details-content">
                    <!-- Динамічний контент з деталями продажу -->
                </div>
            </div>
        </div> <br>
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
    function showNewSaleForm() {
        const newSaleForm = document.getElementById('new-sale-form');
        const history = document.getElementById('history');
        if (newSaleForm.style.display === 'block') {
            newSaleForm.style.display = 'none';
        } else {
            newSaleForm.style.display = 'block';
        }
        updateCashReceived();
    }

    function closeSaleDetails() {
        document.getElementById('sale-details-form').style.display = 'none';
    }

    function showSaleDetails(saleId) {
        fetch(`./buy_storage.php?action=get_sale_details&sale_id=${saleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const saleDetailsContent = document.getElementById('sale-details-content');
                    saleDetailsContent.innerHTML = '';

                    const sale = data.sale[0];
                    const details = data.sale;
                    let paymentType = sale.payment_type === 'cash' ? 'Готівка' : 'Безготівковий розрахунок';

                    let detailsHtml = `
                        <br><p>Чек від: ${sale.date}</p><br>
                    `;

                    detailsHtml += `
                        <table>
                            <thead>
                                <tr>
                                    <th>Назва товару</th>
                                    <th>Ціна</th>
                                    <th>Кількість</th>
                                    <th>Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    details.forEach(item => {
                        detailsHtml += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.price}</td>
                                <td>${item.quantity}</td>
                                <td>${item.total}</td>
                            </tr>
                        `;
                    });

                    detailsHtml += `
                            </tbody>
                        </table>
                        <br> <p>Всього до сплати: ${sale.total} </p>
                        <p>Тип оплати: ${paymentType}</p>
                    `;
                    

                    if (sale.payment_type === 'cash') {
                        detailsHtml += `
                            <br><p>Отримано готівки: ${sale.cash_received}</p>
                            <p>Решта: ${sale.change}</p>
                        `;
                    }

                    saleDetailsContent.innerHTML = detailsHtml;
                    document.getElementById('sale-details-form').style.display = 'block';
                } else {
                    alert('Помилка при отриманні деталей продажу: ' + data.error);
                    Swal.fire({
                        icon: "error",
                        title: "Помилка при отриманні деталей продажу:" + data.error,
                        text: "Просимо звернутись до служби підтримки",           
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }

    document.getElementById('payment-type').addEventListener('change', function () {
        if (this.value === 'cash') {
            document.getElementById('cash-payment').style.display = 'block';
            updateCashReceived();
        } else {
            document.getElementById('cash-payment').style.display = 'none';
            document.getElementById('cash-received').value = '';
            document.getElementById('change').value = '';
        }
    });

    function calculateChange() {
        const cashReceived = parseFloat(document.getElementById('cash-received').value);
        const totalAmount = calculateTotalAmount();
        const change = cashReceived - totalAmount;
        document.getElementById('change').value = change > 0 ? change : 0;
    }

    function calculateTotalAmount() {
        const selectedProductsDiv = document.getElementById('selected-products');
        const productElements = selectedProductsDiv.getElementsByClassName('selected-product');
        let totalAmount = 0;

        for (let productElement of productElements) {
            const price = parseFloat(productElement.querySelector('.product-price').value);
            const quantity = parseInt(productElement.querySelector('.product-quantity').value);
            totalAmount += price * quantity;
        }

        return totalAmount;
    }

    function updateCashReceived() {
        const totalAmount = calculateTotalAmount();
        document.getElementById('cash-received').value = totalAmount;
    }

    function searchProduct() {
        const query = document.getElementById('product-search').value;

        fetch(`./buy_storage.php?action=search_products&search=${query}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Помилка підключення');
                }
                return response.json();
            })
            .then(data => {
                const productList = document.getElementById('product-list');
                productList.innerHTML = '';

                data.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'product-item';
                    productItem.textContent = product.name;
                    productItem.addEventListener('click', function() {
                        selectProduct(product);
                    });
                    productList.appendChild(productItem);
                });
                productList.classList.add('show');  // Додаємо клас show для відображення списку
            })
            .catch(error => console.error('Error:', error));
    }

    function selectProduct(product) {
    const selectedProducts = document.getElementById('selected-products');
    const productDiv = document.createElement('div');
    productDiv.className = 'selected-product';
    productDiv.innerHTML = `
        <span>${product.name} - 
            <input type="number" value="${product.price_sale}" step="0.01" min="0" class="product-price"> x 
            <input type="number" value="1" min="1" max="${product.amount}" class="product-quantity" oninput="validateQuantity(this, ${product.amount})"> шт.
            <span class="product-availability"> (В наявності: ${product.amount})</span>
        </span>
        <input type="hidden" class="product-id" value="${product.id}">
    `;
    selectedProducts.appendChild(productDiv);

    document.getElementById('product-list').innerHTML = '';  // Очистити список продуктів
    updateCashReceived();
}

function validateQuantity(input, availableAmount) {
    if (parseInt(input.value) > availableAmount) {
        input.style.color = 'red';
    } else {
        input.style.color = 'black';
    }
    updateCashReceived();
}

function submitSale() {
    const selectedProductsDiv = document.getElementById('selected-products');
    const productElements = selectedProductsDiv.getElementsByClassName('selected-product');
    const products = [];
    let hasError = false;

    for (let productElement of productElements) {
        const id = parseInt(productElement.querySelector('.product-id').value);
        const price = parseFloat(productElement.querySelector('.product-price').value);
        const quantityInput = productElement.querySelector('.product-quantity');
        const quantity = parseInt(quantityInput.value);
        const availableAmount = parseInt(quantityInput.getAttribute('max'));

        if (quantity > availableAmount) {
            hasError = true;
            quantityInput.style.color = 'red';
        } else {
            quantityInput.style.color = 'black';
        }

        products.push({ id, price, quantity });
    }

    if (products.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Попередження!",
            text: "Будь ласка, додайте хоча б один товар до продажу.",           
        });
        return;
    }

    if (hasError) {
        Swal.fire({
            icon: "error",
            title: "Помилка!",
            text: "Недостатньо товарів на складі.",           
        });
        return;
    }

    const paymentType = document.getElementById('payment-type').value;
    const totalAmount = calculateTotalAmount();
    const cashReceived = paymentType === 'cash' ? parseFloat(document.getElementById('cash-received').value) : null;

    fetch('./buy_storage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            products,
            payment_type: paymentType,
            total_amount: totalAmount,
            cash_received: cashReceived,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
            text: "Продаж успішно створено!",
            icon: "success"
            });
            updateSalesHistory();
            updateProductsStock();  // Оновлення залишків
            document.getElementById('new-sale-form').style.display = 'none';  
        } else {
            Swal.fire({
            icon: "error",
            title: "Помилка при створенні продажу:" + data.error,
            text: "Просимо звернутися з даною помилкою до служби підтримки",           
        });
        }
    })
    .catch(error => console.error('Error:', error));
}

    function updateSalesHistory() {
        fetch('buy_storage.php?action=get_sales')
        .then(response => response.json())
        .then(data => {
            const salesHistory = document.getElementById('sales-history');
            salesHistory.innerHTML = '';
            data.sales.forEach(sale => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><button onclick="showSaleDetails(${sale.id})">?</button></td>
                    <td>${sale.date}</td>
                    <td>${sale.totals}</td>
                    <td>${sale.payment_type}</td>
                `;
                salesHistory.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
    }

    function updateProductsStock() {
        const query = '';
        fetch(`./buy_storage.php?action=search_products&search=${query}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Помилка підключення при оновленні товарів');
                }
                return response.json();
            })
            .then(data => {
                // Оновлення відображення товарів на складі
                const productList = document.getElementById('product-list');
                productList.innerHTML = '';

                data.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'product-item';
                    productItem.textContent = product.name;
                    productList.appendChild(productItem);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateSalesHistory();
        updateProductsStock();  // Оновлює залишки при завантаженні сторінки
    });
</script>

    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
