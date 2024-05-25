<?php
include "db_storage.php";

// Отримуємо параметр категорії з URL, якщо він є
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

// Функція для отримання продуктів
function getProducts($link, $category_id, $search_query, $onlyAvailable = false) {
    $query = "SELECT * FROM `products_id`";
    $conditions = [];
    if ($category_id > 0) {
        $conditions[] = "`category_id` = $category_id";
    }
    if ($search_query) {
        $conditions[] = "(`name` LIKE '%$search_query%' OR `article` LIKE '%$search_query%' OR `barcode` LIKE '%$search_query%' OR `price_purchase` LIKE '%$search_query%' OR `price_sale` LIKE '%$search_query%' OR `amount` LIKE '%$search_query%')";
    }
    if ($onlyAvailable) {
        $conditions[] = "`amount` > 0";
    }
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $result_products = mysqli_query($link, $query);
    if (!$result_products) {
        die("Помилка з'єднання: " . mysqli_error($link));
    }
    return $result_products;
}

// Функція для отримання постачальників
function getSuppliers($link) {
    $query = "SELECT * FROM `suppliers`";
    $result_suppliers = mysqli_query($link, $query);
    if (!$result_suppliers) {
        die("Помилка з'єднання: " . mysqli_error($link));
    }
    return $result_suppliers;
}

// Якщо це AJAX запит, повертаємо тільки результати пошуку
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $onlyAvailable = $tab === 'tab-04'; // Фільтруємо тільки доступні товари для списання
    $result_products = getProducts($link, $category_id, $search_query, $onlyAvailable);
    while ($row = mysqli_fetch_assoc($result_products)) {
        if ($tab === 'tab-02') {
            $zeroAmountClass = $row['amount'] == 0 ? ' class="zero-amount"' : '';
            echo "<tr$zeroAmountClass>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['article'] . "</td>";
            echo "<td>" . $row['barcode'] . "</td>";
            echo "<td>" . $row['price_purchase'] . "</td>";
            echo "<td>" . $row['price_sale'] . "</td>";
            echo "<td>" . $row['amount'] . "</td>";
            echo "</tr>";
        }
        if ($tab === 'tab-01') {
            echo "<tr>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['article'] . "</td>";
            echo "<td>" . $row['barcode'] . "</td>";
            echo "<td>" . $row['price_purchase'] . "</td>";
            echo "<td>" . $row['price_sale'] . "</td>";
            echo "</tr>";
        }
    }
    exit;
}

// Обробка запиту на додавання товару
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        // Отримання даних з форми
        $category_id = intval($_POST['category_id']);
        $new_category_name = isset($_POST['new_category_name']) ? mysqli_real_escape_string($link, $_POST['new_category_name']) : '';
        $name = mysqli_real_escape_string($link, $_POST['name']);
        $article = mysqli_real_escape_string($link, $_POST['article']);
        $barcode = mysqli_real_escape_string($link, $_POST['barcode']);
        $price_purchase = floatval($_POST['price_purchase']);
        $price_sale = floatval($_POST['price_sale']);
        $amount = 0; // Кількість товару за замовчуванням

        // Якщо нова категорія, додати її
        if ($category_id === 0 && !empty($new_category_name)) {
            $query = "INSERT INTO categories_bd (category_name) VALUES ('$new_category_name')";
            $result = mysqli_query($link, $query);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
                exit;
            }
            $category_id = mysqli_insert_id($link);
        }

        // Додавання нового продукту
        $query = "INSERT INTO products_id (category_id, name, article, barcode, price_purchase, price_sale, amount) VALUES ($category_id, '$name', '$article', '$barcode', $price_purchase, $price_sale, $amount)";
        $result = mysqli_query($link, $query);
        if (!$result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Обробка запиту на створення оприбуткування
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_receiving') {
    try {
        $supplier_id = intval($_POST['supplier_id']);
        $new_supplier_name = isset($_POST['new_supplier_name']) ? mysqli_real_escape_string($link, $_POST['new_supplier_name']) : '';
        $invoice_number = mysqli_real_escape_string($link, $_POST['invoice_number']);
        $date = mysqli_real_escape_string($link, $_POST['date']);
        $total_quantity = intval($_POST['total_quantity']);
        $total_price = floatval($_POST['total_price']);
        $comment = mysqli_real_escape_string($link, $_POST['comment']);
        $products = json_decode($_POST['products'], true); // JSON array of product details

        // Додавання нового постачальника, якщо його немає
        if ($supplier_id === 0 && !empty($new_supplier_name)) {
            $query = "INSERT INTO suppliers (name) VALUES ('$new_supplier_name')";
            $result = mysqli_query($link, $query);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
                exit;
            }
            $supplier_id = mysqli_insert_id($link);
            if (!$supplier_id) {
                echo json_encode(['success' => false, 'error' => 'Не вдалося отримати ID нового постачальника']);
                exit;
            }
        }

        // Вставка оприбуткування
        $query = "INSERT INTO receiving (supplier_id, invoice_number, date, total_quantity, total_price, comment) VALUES ($supplier_id, '$invoice_number', '$date', $total_quantity, $total_price, '$comment')";
        $result = mysqli_query($link, $query);
        if (!$result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }
        $receiving_id = mysqli_insert_id($link);

        // Вставка деталей оприбуткування
        foreach ($products as $product) {
            $product_id = intval($product['id']);
            $price = floatval($product['price']);
            $quantity = intval($product['quantity']);

            $query = "INSERT INTO receiving_details (receiving_id, product_id, price, quantity) VALUES ($receiving_id, $product_id, $price, $quantity)";
            $result = mysqli_query($link, $query);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
                exit;
            }

            // Оновлення залишків
            $query = "UPDATE products_id SET amount = amount + $quantity, price_purchase = $price WHERE id = $product_id";
            $update_result = mysqli_query($link, $query);

            if (!$update_result) {
                echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
                exit;
            }
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Обробка запиту на отримання історії оприбуткувань
if (isset($_GET['action']) && $_GET['action'] === 'get_receiving_history') {
    $query = "
        SELECT r.*, s.name AS supplier_name 
        FROM receiving r 
        JOIN suppliers s ON r.supplier_id = s.id 
        ORDER BY r.date DESC
    ";
    $result = mysqli_query($link, $query);

    $receiving = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $receiving[] = $row;
    }

    echo json_encode(['receiving' => $receiving]);
    exit;
}

// Обробка запиту на пошук товарів
if (isset($_GET['action']) && $_GET['action'] === 'search_products') {
    $search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $onlyAvailable = isset($_GET['only_available']) && $_GET['only_available'] == '1';
    $result_products = getProducts($link, $category_id, $search_query, $onlyAvailable);

    $products = [];
    while ($row = mysqli_fetch_assoc($result_products)) {
        $products[] = $row;
    }

    echo json_encode(['products' => $products]);
    exit;
}

// реалізація додавання нового постачальника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier') {
    $supplier_name = mysqli_real_escape_string($link, $_POST['supplier_name']);
    $insert_supplier_query = "INSERT INTO suppliers (name) VALUES ('$supplier_name')";
    if (mysqli_query($link, $insert_supplier_query)) {
        echo json_encode(['success' => true, 'new_supplier_id' => mysqli_insert_id($link)]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
    }
    exit;
}

// Обробка запиту на отримання деталей оприбуткування
if (isset($_GET['action']) && $_GET['action'] === 'get_receiving_details' && isset($_GET['receiving_id'])) {
    $receiving_id = intval($_GET['receiving_id']);
    $query = "
        SELECT r.*, s.name AS supplier_name 
        FROM receiving r 
        JOIN suppliers s ON r.supplier_id = s.id 
        WHERE r.id = $receiving_id
    ";
    $result = mysqli_query($link, $query);
    $receiving = mysqli_fetch_assoc($result);

    $query_products = "
        SELECT p.name, rd.price, rd.quantity, (rd.price * rd.quantity) AS total
        FROM receiving_details rd
        JOIN products_id p ON rd.product_id = p.id
        WHERE rd.receiving_id = $receiving_id
    ";
    $result_products = mysqli_query($link, $query_products);
    $products = [];
    $total_quantity = 0;
    $total_sum = 0;
    while ($row = mysqli_fetch_assoc($result_products)) {
        $products[] = $row;
        $total_quantity += $row['quantity'];
        $total_sum += $row['total'];
    }

    $receiving['products'] = $products;
    $receiving['total_quantity'] = $total_quantity;
    $receiving['total_sum'] = $total_sum;
    echo json_encode($receiving);
    exit;
}

// Отримання списання
function getWriteOffs($link) {
    $query = "
        SELECT w.*, GROUP_CONCAT(CONCAT(p.name, ' (x', wd.quantity, ')') SEPARATOR ', ') AS products
        FROM write_offs w
        JOIN write_off_details wd ON w.id = wd.write_off_id
        JOIN products_id p ON wd.product_id = p.id
        GROUP BY w.id
        ORDER BY w.date DESC
    ";
    $result = mysqli_query($link, $query);
    if (!$result) {
        die("Помилка з'єднання: " . mysqli_error($link));
    }
    return $result;
}

// Обробка запиту на створення списання
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_write_off') {
    $comment = mysqli_real_escape_string($link, $_POST['comment']);
    $products = json_decode($_POST['products'], true); // JSON масив детальної інформації про продукти
    
    foreach ($products as $product) { // Перевірка кількості товарів
        $product_id = intval($product['id']);
        $quantity = intval($product['quantity']);
        $query = "SELECT amount FROM products_id WHERE id = $product_id";
        $result = mysqli_query($link, $query);
        if (!$result || mysqli_num_rows($result) == 0) {
            echo json_encode(['success' => false, 'error' => 'Товар не знайдено']);
            exit;
        }
        $row = mysqli_fetch_assoc($result);
        if ($row['amount'] < $quantity) {
            echo json_encode(['success' => false, 'error' => 'Недостатньо товарів на складі']);
            exit;
        }
    }
    // Вставка списання
    $query = "INSERT INTO write_offs (write_off_number, date, comment) VALUES (NULL, NOW(), '$comment')";
    $result = mysqli_query($link, $query);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
        exit;
    }
    $write_off_id = mysqli_insert_id($link);
    $query = "UPDATE write_offs SET write_off_number = $write_off_id WHERE id = $write_off_id"; // Призначення write_off_number
    mysqli_query($link, $query);
    // Вставка деталей списання
    foreach ($products as $product) {
        $product_id = intval($product['id']);
        $quantity = intval($product['quantity']);

        $query = "INSERT INTO write_off_details (write_off_id, product_id, quantity) VALUES ($write_off_id, $product_id, $quantity)";
        $result = mysqli_query($link, $query);
        if (!$result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }        
        $query = "UPDATE products_id SET amount = amount - $quantity WHERE id = $product_id";// Оновлення залишків
        $update_result = mysqli_query($link, $query);
        if (!$update_result) {
            echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
            exit;
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Обробка запиту на отримання історії списань
if (isset($_GET['action']) && $_GET['action'] === 'get_write_off_history') {
    $query = "
        SELECT w.write_off_number, w.date, w.comment
        FROM write_offs w
        ORDER BY w.date DESC
    ";
    $result = mysqli_query($link, $query);

    $write_offs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $write_offs[] = $row;
    }

    echo json_encode(['write_offs' => $write_offs]);
    exit;
}
// Обробка запиту на отримання деталей списання
if (isset($_GET['action']) && $_GET['action'] === 'get_write_off_details' && isset($_GET['write_off_id'])) {
    $write_off_id = intval($_GET['write_off_id']);
    $query = "
        SELECT w.write_off_number, w.date, w.comment, GROUP_CONCAT(CONCAT(p.name, ' (x', wd.quantity, ')') SEPARATOR ', ') AS products
        FROM write_offs w
        JOIN write_off_details wd ON w.id = wd.write_off_id
        JOIN products_id p ON wd.product_id = p.id
        WHERE w.id = $write_off_id
        GROUP BY w.id
    ";
    $result = mysqli_query($link, $query);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
        exit;
    }
    $write_off = mysqli_fetch_assoc($result);
    echo json_encode($write_off);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOOSL Склад | Ruban E.</title>
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
                <a href="contact.php">Контакти</a>
            </nav>
        </div>
    </header>
    
    <div class="menuVertical-storage">
        <nav id="menuVertical">
            <ul>
                <li><a href="main.php"><div class="img_acc"><img src="img/acc.jpg"></div><span></span></a></li>
                <li><a href="./report.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Звіти</span></a></li>
                <li><a href="buy_storage.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Продажі</span></a></li>
                <li><a href="index_storage.php#tab-01"><div class="img_n"><img src="img/acc.jpg"></div><span>Склад</span></a></li>
                <li><a href="./index.php"><div class="img_n"><img src="img/acc.jpg"></div><span>Вихід</span></a></li>
            </ul>
        </nav>
    </div>

    <div class="wrapper-storage">
        <div class="content-storage">


            <div class="tabs-storage">
                <nav class="tabs-items">
                    <a href="#tab-01" class="tabs-item"><span>Товари</span></a>
                    <a href="#tab-02" class="tabs-item active-tab"><span>Залишки</span></a>
                    <a href="#tab-03" class="tabs-item"><span>Оприбуткування</span></a>
                    <a href="#tab-04" class="tabs-item"><span>Списання</span></a>                    
                </nav>
                <div class="tabs-body">
                    <div id="tab-01" class="tabs-block">
                        <div class="search">
                            <button onclick="showAddProductForm()" class="btn-add-product">Додати товар</button>
                            <input type="text" class="inputsearch" id="search" placeholder="Пошук..." onkeyup="searchProducts('tab-01')">                        
                        </div>
                        <div id="add-product-form-container" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="hideAddProductForm()">&times;</span>
                                <form id="add-product-form">
                                    <div>
                                        <label for="category">Категорія:</label>
                                        <select id="category" name="category_id" required>
                                            <option value="">Виберіть категорію</option>
                                            <option value="new">Створити нову категорію</option>
                                            <?php 
                                            $result_categories = mysqli_query($link, "SELECT * FROM `categories_bd`");
                                            while ($row = mysqli_fetch_assoc($result_categories)) {
                                                echo "<option value='" . $row['id'] . "'>" . $row['category_name'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div id="new-category-container" style="display:none;">
                                        <label for="new-category-name">Нова категорія:</label>
                                        <input type="text" id="new-category-name" name="new_category_name">
                                    </div>
                                    <div>
                                        <label for="name">Назва товару:</label>
                                        <input type="text" id="name" name="name" required>
                                    </div>
                                    <div>
                                        <label for="article">Артикул:</label>
                                        <input type="number" id="article" name="article" required>
                                    </div>
                                    <div>
                                        <label for="barcode">Штрих-код:</label>
                                        <input type="text" id="barcode" name="barcode" required>
                                    </div>
                                    <div>
                                        <label for="price_purchase">Ціна закупівлі:</label>
                                        <input type="number" step="0.01" id="price_purchase" name="price_purchase" required oninput="validateNumberInput(this)">
                                    </div>
                                    <div>
                                        <label for="price_sale">Ціна продажу:</label>
                                        <input type="number" step="0.01" id="price_sale" name="price_sale" required oninput="validateNumberInput(this)">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn-add-product-2">Додати товар</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="items">
                            <div class="categories">
                                <p>Категорії</p>
                                <ul id="categories-list">
                                    <li class="active"><a href="index_storage.php">Всі товари</a></li>
                                    <?php 
                                    $result_categories = mysqli_query($link, "SELECT * FROM `categories_bd`");

                                    while ($row = mysqli_fetch_assoc($result_categories)) {
                                        echo "<li>";
                                        echo "<a href='index_storage.php?category_id=" . $row['id'] . "'>" . $row['category_name'] . "</a>";
                                        echo "</li>";
                                    }
                                    ?>
                                </ul>
                            </div>

                            <div class="menu">
                                <div class="table-container">
                                    <table>
                                        <thead class="table-header">
                                            <tr>
                                                <th>Найменування</th>
                                                <th>Артикль</th>
                                                <th>Штрих-код</th>
                                                <th>Ціна закупівлі</th>
                                                <th>Ціна продажу</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-body" id="product-table-body">
                                            <?php 
                                            $result_products = getProducts($link, $category_id, $search_query);
                                            while ($row = mysqli_fetch_assoc($result_products)) {
                                                echo "<tr>";
                                                echo "<td>" . $row['name'] . "</td>";
                                                echo "<td>" . $row['article'] . "</td>";
                                                echo "<td>" . $row['barcode'] . "</td>";
                                                echo "<td>" . $row['price_purchase'] . "</td>";
                                                echo "<td>" . $row['price_sale'] . "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-02" class="tabs-block">
                    <div class="search"> 
                        <input type="text" class="inputsearch" id="search-2" placeholder="Пошук..." onkeyup="searchProducts('tab-02')">
                    </div>
                        <div class="menu">
                            <div class="table-container">
                                <table>
                                    <thead class="table-header">
                                        <tr>
                                            <th>Найменування</th>
                                            <th>Артикль</th>
                                            <th>Штрих-код</th>
                                            <th>Ціна закупівлі</th>
                                            <th>Ціна продажу</th>
                                            <th>Кількість</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-body" id="product-table-body-2">
                                    <?php 
                                        $result_products = getProducts($link, $category_id, $search_query);
                                        while ($row = mysqli_fetch_assoc($result_products)) {
                                            $zeroAmountClass = $row['amount'] == 0 ? 'zero-amount' : '';
                                            echo "<tr class='$zeroAmountClass'>";
                                            echo "<td>" . $row['name'] . "</td>";
                                            echo "<td>" . $row['article'] . "</td>";
                                            echo "<td>" . $row['barcode'] . "</td>";
                                            echo "<td>" . $row['price_purchase'] . "</td>";
                                            echo "<td>" . $row['price_sale'] . "</td>";
                                            echo "<td>" . $row['amount'] . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>  
                        </div>
                    </div>
                    <div id="tab-03" class="tabs-block">
                        <div class="search"> 
                        <button onclick="showAddReceivingForm()" class="btn-add-receiving">Додати оприбуткування</button>
                        </div>
                        <div id="add-receiving-form-container" class="modal-receiving">
                        <div class="modal-content-receiving">
                            <span class="close-receiving" onclick="hideAddReceivingForm()">
                                <ion-icon name="arrow-back-outline"></ion-icon>
                            </span>
                            <form id="add-receiving-form">
                                <h2>Створення оприбуткування</h2>
                                <br>
                                <div class="form-group">
                                    <label for="supplier">Постачальник:</label>
                                    <select id="supplier" name="supplier_id" required>
                                        <option value="">Виберіть постачальника</option>
                                        <option value="new_supplier">Створити нового постачальника</option>
                                        <?php 
                                        $result_suppliers = getSuppliers($link);
                                        while ($row = mysqli_fetch_assoc($result_suppliers)) {
                                            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div id="new-supplier-container" class="form-group" style="display:none;">
                                    <label for="new-supplier-name">Новий постачальник:</label>
                                    <input type="text" id="new-supplier-name" name="new_supplier_name">
                                </div>
                                <div class="form-row">
                                    <div class="form-group small">
                                        <label for="invoice_number">Номер накладної:</label>
                                        <input type="text" id="invoice_number" name="invoice_number" required>
                                    </div>
                                    <div class="form-group small">
                                        <label for="date">Дата:</label>
                                        <input type="date" id="date" name="date" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="product-search">Пошук товару:</label>
                                    <input type="text" id="product-search" onkeyup="searchProduct()">
                                    <div class="product-list" id="product-list"></div>
                                </div>
                                <div class="selected-products" id="selected-products"></div>
                                <div class="form-row">
                                    <div class="form-group small">
                                        <label for="total_quantity">Сумарна кількість:</label>
                                        <input type="number" id="total_quantity" name="total_quantity" readonly class="no-bg">
                                    </div>
                                    <div class="form-group small">
                                        <label for="total_price">Сумарна ціна закупівлі:</label>
                                        <input type="number" id="total_price" name="total_price" readonly class="no-bg">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="comment">Коментар:</label>
                                    <textarea id="comment" name="comment"></textarea>
                                </div>
                                <button type="submit" class="btn-add-receiving-2">Додати оприбуткування</button>
                            </form>
                        </div>
                    </div>


                        <div id="receiving-history">
                            <h2 style="padding-bottom: 1rem;">Історія оприбуткування</h2>
                            <table>
                                <thead>
                                    <tr>                                        
                                        <th>Постачальник</th>
                                        <th>Номер накладної</th>
                                        <th>Дата</th>
                                        <th>Сумарна кількість</th>
                                        <th>Сумарна ціна</th>
                                        <th>Коментар</th>
                                        <th>Деталі </th>
                                    </tr>
                                </thead>
                                <tbody id="receiving-history-body">
                                    <!-- Динамічний контент з історією оприбуткувань -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="receiving-details-modal" class="modal-receiving">
                        <div class="modal-content-receiving">
                            <span class="close-receiving" onclick="hideReceivingDetails()"><ion-icon name="arrow-back-outline"></ion-icon></span>
                            <div id="receiving-details-content"></div>
                        </div>
                    </div>

                    <div id="tab-04" class="tabs-block">

                        <div class="search">
                            <button onclick="showAddWriteOffForm()" class="btn-add-write-off">Створити списання</button>
                        </div>
                        <div id="add-write-off-form-container" class="modal-write-off">
                            <div class="modal-content-write-off">
                                <span class="close-write-off" onclick="hideAddWriteOffForm()"><ion-icon name="arrow-back-outline"></ion-icon></span>
                                <form id="add-write-off-form">
                                    <h2>Створення списання</h2><br>
                                    <div class="form-group">
                                        <label for="product-search-write-off">Пошук товару:</label>
                                        <input type="text" id="product-search-write-off" onkeyup="searchProductWriteOff()">
                                        <div class="product-list" id="product-list-write-off"></div>
                                    </div>
                                    <div class="selected-products" id="selected-products-write-off"></div>
                                    <div class="form-group">
                                        <label for="comment">Коментар:</label>
                                        <textarea id="comment" name="comment"></textarea>
                                    </div>
                                    <button type="submit" class="btn-add-write-off-2">Додати списання</button>
                                </form>
                            </div>
                        </div>
                        <div id="write-off-history">
                            <h2 style="padding-bottom: 1rem;">Історія списання</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Номер списання</th>
                                        <th>Дата</th>
                                        <th>Коментар</th>
                                        <th>Деталі</th>
                                    </tr>
                                </thead>
                                <tbody id="write-off-history-body">
                                    <!-- Динамічний контент з історією списань -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="write-off-details-modal" class="modal-write-off">
                        <div class="modal-content-write-off">
                            <span class="close-write-off" onclick="hideWriteOffDetails()"><ion-icon name="arrow-back-outline"></ion-icon></span>
                            <div id="write-off-details-content" ></div>
                        </div>
                    </div>


                </div>
            </div>
            
        </div>
    </div>
    
    <footer>
        <div class="footerContainers secondBlock">
            <div class="footerContainersBlocks" id="contacts">
                <h4>Контакти</h4>
                <ul>
                    <li><a href="https://t.me/minignom" >Telegram</a></li>
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
                <p>
                    2024 Рубан Є. С.   |    lrozzenberg@gmail.com<br>
                </p>
            </div>
        </div>
    </footer>    

    <script>
    document.getElementById('category').addEventListener('change', function() {
        const newCategoryContainer = document.getElementById('new-category-container');
        if (this.value === 'new') {
            newCategoryContainer.style.display = 'block';
        } else {
            newCategoryContainer.style.display = 'none';
        }
    });

    document.getElementById('supplier').addEventListener('change', function() {
        const newSupplierContainer = document.getElementById('new-supplier-container');
        if (this.value === 'new_supplier') {
            newSupplierContainer.style.display = 'block';
        } else {
            newSupplierContainer.style.display = 'none';
        }
    });

    document.getElementById('add-product-form').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        fetch('index_storage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Змінити на text()
        .then(text => {
            try {
                const data = JSON.parse(text); // Спробувати розібрати JSON
                if (data.success) {
                    alert('Товар успішно додано!');
                    searchProducts('tab-01'); // Оновити таблицю товарів
                    updateCategories(); // Оновити список категорій
                    document.getElementById('add-product-form-container').style.display = 'none'; // Сховати форму
                    this.reset(); // Очистити форму
                } else {
                    alert('Помилка при додаванні товару: ' + data.error);
                }
            } catch (e) {
                console.error('Помилка при зчитуванні JSON:', e);
                console.error('Відповідь:', text); // Вивести відповідь у консоль
                Swal.fire({
                    icon: "error",
                    title: "Помилка обробки відповіді сервера.",
                    text: "Просимо звернутися з даною помилкою до служби підтримки",           
                });
            }
        })
        .catch(error => console.error('Error:', error));
    });

    document.getElementById('add-receiving-form').addEventListener('submit', function(event) {
        event.preventDefault();

        const supplierId = document.getElementById('supplier').value;
        if (supplierId === 'new_supplier') {
            const newSupplierName = document.getElementById('new-supplier-name').value;
            const formData = new FormData();
            formData.append('action', 'add_supplier');
            formData.append('supplier_name', newSupplierName);

            fetch('index_storage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Оновити постачальника у формі оприбуткування
                    const supplierSelect = document.getElementById('supplier');
                    const newOption = document.createElement('option');
                    newOption.value = data.new_supplier_id;
                    newOption.text = newSupplierName;
                    supplierSelect.add(newOption);
                    supplierSelect.value = data.new_supplier_id;
                    addReceiving(); // Додаємо оприбуткування з новим постачальником
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Помилка при додаванні постачальника:" + data.error,
                        text: "Просимо звернутися з даною помилкою до служби підтримки",           
                    });
                }
            })
            .catch(error => console.error('Error:', error));
        } else {
            addReceiving(); // Додаємо оприбуткування з існуючим постачальником
        }
    });

    function showAddProductForm() {
        document.getElementById('add-product-form-container').style.display = 'block';
    }

    function hideAddProductForm() {
        document.getElementById('add-product-form-container').style.display = 'none';
    }

    function showAddReceivingForm() {
        document.getElementById('add-receiving-form-container').style.display = 'block';
    }

    function hideAddReceivingForm() {
        document.getElementById('add-receiving-form-container').style.display = 'none';
    }

    function validateNumberInput(input) {
        input.addEventListener('keypress', function(event) {
            // Дозволяємо тільки цифри і одну крапку
            const char = String.fromCharCode(event.which);
            if (!/[0-9.]/.test(char)) {
                event.preventDefault();
            }
            
            // Перевіряємо чи вже є крапка у полі
            if (char === '.' && input.value.includes('.')) {
                event.preventDefault();
            }
        });
    }

    function updateCategories() {
        fetch('index_storage.php')
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCategories = doc.querySelector('#categories-list').innerHTML;
                document.querySelector('#categories-list').innerHTML = newCategories;
            })
            .catch(error => console.error('Помилка при оновленні категорій:', error));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const links = document.querySelectorAll('.categories a');
        links.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.href;

                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.querySelector('.menu').innerHTML;
                        document.querySelector('.menu').innerHTML = newContent;

                        // Після оновлення контенту, знову прив'язуємо функцію пошуку до нового поля
                        document.getElementById('search').addEventListener('keyup', searchProducts.bind(null, 'tab-01'));
                        document.getElementById('search-2').addEventListener('keyup', searchProducts.bind(null, 'tab-02'));
                        
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    });

    function searchProducts(tab) {
        const query = tab === 'tab-01' ? document.getElementById('search').value : 
                      tab === 'tab-02' ? document.getElementById('search-2').value : '';
        const category_id = tab === 'tab-01' ? new URLSearchParams(window.location.search).get('category_id') || 0 : 0;
        const url = `index_storage.php?search=${query}&category_id=${category_id}&tab=${tab}&ajax=1`;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                const productTableBody = tab === 'tab-01' ? document.getElementById('product-table-body') : 
                                         tab === 'tab-02' ? document.getElementById('product-table-body-2') : null;
                if (productTableBody) {
                    productTableBody.innerHTML = data;
                } else {
                    console.error('Вкладки не знайдені.');
                }
            })
            .catch(error => console.error('Перевірка помилок при пошуку товарів:', error));
    }

    function searchProduct() {
        const query = document.getElementById('product-search').value;

        fetch(`index_storage.php?action=search_products&search=${query}`)
            .then(response => response.json())
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
                productList.classList.add('show');
            })
            .catch(error => console.error('Error:', error));
    }

    function selectProduct(product) {
        const selectedProducts = document.getElementById('selected-products');
        const productDiv = document.createElement('div');
        productDiv.className = 'selected-product';
        productDiv.innerHTML = `
            <span>${product.name} - <input type="number" value="${product.price_purchase}" step="0.01" min="0" class="product-price"> x <input type="number" value="1" min="1" class="product-quantity" oninput="updateTotals()"> шт.</span>
            <input type="hidden" class="product-id" value="${product.id}">
        `;
        selectedProducts.appendChild(productDiv);

        document.getElementById('product-list').innerHTML = '';  
        updateTotals();
    }

    function updateTotals() {
        const selectedProductsDiv = document.getElementById('selected-products');
        const productElements = selectedProductsDiv.getElementsByClassName('selected-product');
        let totalQuantity = 0;
        let totalPrice = 0;

        for (let productElement of productElements) {
            const price = parseFloat(productElement.querySelector('.product-price').value);
            const quantity = parseInt(productElement.querySelector('.product-quantity').value);
            totalQuantity += quantity;
            totalPrice += price * quantity;
        }

        document.getElementById('total_quantity').value = totalQuantity;
        document.getElementById('total_price').value = totalPrice;
    }

    function addReceiving() {
        const supplierId = document.getElementById('supplier').value;
        const invoiceNumber = document.getElementById('invoice_number').value;
        const date = document.getElementById('date').value;
        const totalQuantity = document.getElementById('total_quantity').value;
        const totalPrice = document.getElementById('total_price').value;
        const comment = document.getElementById('comment').value;
        const products = [];
        const selectedProductsDiv = document.getElementById('selected-products');
        const productElements = selectedProductsDiv.getElementsByClassName('selected-product');

        for (let productElement of productElements) {
            const id = parseInt(productElement.querySelector('.product-id').value);
            const price = parseFloat(productElement.querySelector('.product-price').value);
            const quantity = parseInt(productElement.querySelector('.product-quantity').value);
            products.push({ id, price, quantity });
        }

        const formData = new FormData();
        formData.append('supplier_id', supplierId);
        formData.append('invoice_number', invoiceNumber);
        formData.append('date', date);
        formData.append('total_quantity', totalQuantity);
        formData.append('total_price', totalPrice);
        formData.append('comment', comment);
        formData.append('products', JSON.stringify(products));
        formData.append('action', 'add_receiving');

        fetch('index_storage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                text: "Оприбуткування успішно додано!",
                icon: "success"
                });
                document.getElementById('add-receiving-form-container').style.display = 'none';
                document.getElementById('add-receiving-form').reset();
                updateReceivingHistory();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Помилка при створенні оприбуткування:" + data.error,
                    text: "Просимо звернутися з даною помилкою до служби підтримки",           
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateReceivingHistory() {
        fetch('index_storage.php?action=get_receiving_history')
        .then(response => response.json())
        .then(data => {
            const receivingHistoryBody = document.getElementById('receiving-history-body');
            receivingHistoryBody.innerHTML = '';
            data.receiving.forEach(receiving => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${receiving.supplier_name}</td>
                    <td>${receiving.invoice_number}</td>
                    <td>${receiving.date}</td>
                    <td>${receiving.total_quantity}</td>
                    <td>${receiving.total_price}</td>
                    <td>${receiving.comment}</td>
                `;
                receivingHistoryBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateReceivingHistory();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const links = document.querySelectorAll('.categories ul li a');
        const currentCategory = new URLSearchParams(window.location.search).get('category_id');

        if (!currentCategory) {
            document.querySelector('.categories ul li:first-child').classList.add('active');
        }

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                links.forEach(link => link.parentElement.classList.remove('active'));
                this.parentElement.classList.add('active');
            });
        });
    });
    function showReceivingDetails(receivingId) {
        fetch(`index_storage.php?action=get_receiving_details&receiving_id=${receivingId}`)
            .then(response => response.json())
            .then(data => {
                const receivingDetailsContent = document.getElementById('receiving-details-content');
                receivingDetailsContent.innerHTML = `
                    <h2>Деталі оприбуткування</h2>
                    <p>Дата: ${data.date}</p>
                    <p>Постачальник: ${data.supplier_name}</p>
                    <p>Номер накладної: ${data.invoice_number}</p> <br>
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
                            ${data.products.map(product => `
                                <tr>
                                    <td>${product.name}</td>
                                    <td>${product.price}</td>
                                    <td>${product.quantity}</td>
                                    <td>${product.total}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <br>
                    <p>Всього кількість: ${data.total_quantity}</p>
                    <p>Всього сума накладної: ${data.total_price}</p>
                    <br><p>Коментар:<br> ${data.comment}</p>
                    
                `;
                document.getElementById('receiving-details-modal').style.display = 'block';
            })
            .catch(error => console.error('Error:', error));
    }

    function hideReceivingDetails() {
        document.getElementById('receiving-details-modal').style.display = 'none';
    }

    function updateReceivingHistory() {
        fetch('index_storage.php?action=get_receiving_history')
        .then(response => response.json())
        .then(data => {
            const receivingHistoryBody = document.getElementById('receiving-history-body');
            receivingHistoryBody.innerHTML = '';
            data.receiving.forEach(receiving => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${receiving.supplier_name}</td>
                    <td>${receiving.invoice_number}</td>
                    <td>${receiving.date}</td>
                    <td>${receiving.total_quantity}</td>
                    <td>${receiving.total_price}</td>
                    <td>${receiving.comment}</td>
                    <td><button onclick="showReceivingDetails(${receiving.id})">?</button></td>
                `;
                receivingHistoryBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
    }

    document.getElementById('add-write-off-form').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    const products = [];
    const selectedProductsDiv = document.getElementById('selected-products-write-off');
    const productElements = selectedProductsDiv.getElementsByClassName('selected-product');
    let hasError = false;

    for (let productElement of productElements) {
        const id = parseInt(productElement.querySelector('.product-id').value);
        const quantityInput = productElement.querySelector('.product-quantity');
        const quantity = parseInt(quantityInput.value);
        const maxAmount = parseInt(quantityInput.getAttribute('max'));

        if (quantity > maxAmount) {
            hasError = true;
            quantityInput.style.color = 'red';
        } else {
            quantityInput.style.color = 'black';
        }

        products.push({ id, quantity });
    }

    if (hasError) {
        Swal.fire({
            icon: "error",
            title: "Помилка",
            text: "Недостатньо товарів на складі для списання",
        });
        return;
    }

    if (products.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Попередження!",
            text: "Будь ласка, додайте хоча б один товар до продажу.",           
        });
        return;
    }

    formData.append('products', JSON.stringify(products));
    formData.append('action', 'add_write_off');

    fetch('index_storage.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
            text: "Товари успішно списано!",
            icon: "success"
            });
            document.getElementById('add-write-off-form-container').style.display = 'none';
            document.getElementById('add-write-off-form').reset();
            updateWriteOffHistory();
        } else {
            Swal.fire({
            icon: "error",
            title: "Помилка при створенні списання:" + data.error,
            text: "Просимо звернутися з даною помилкою до служби підтримки",           
            });
        }
    })
    .catch(error => console.error('Error:', error));
});

    function showAddWriteOffForm() {
        document.getElementById('add-write-off-form-container').style.display = 'block';
    }

    function hideAddWriteOffForm() {
        document.getElementById('add-write-off-form-container').style.display = 'none';
    }

    function searchProductWriteOff() {
        const query = document.getElementById('product-search-write-off').value;

        fetch(`index_storage.php?action=search_products&search=${query}&only_available=1`)
            .then(response => response.json())
            .then(data => {
                const productList = document.getElementById('product-list-write-off');
                productList.innerHTML = '';

                data.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'product-item';
                    productItem.textContent = `${product.name} (Наявність: ${product.amount})`;
                    productItem.addEventListener('click', function() {
                        selectProductWriteOff(product);
                    });
                    productList.appendChild(productItem);
                });
                productList.classList.add('show');
            })
            .catch(error => console.error('Error:', error));
    }

    function selectProductWriteOff(product) {
    const selectedProducts = document.getElementById('selected-products-write-off');
    const productDiv = document.createElement('div');
    productDiv.className = 'selected-product';
    productDiv.innerHTML = `
        <span>${product.name} (Наявність: ${product.amount}) - <input type="number" value="1" min="1" max="${product.amount}" class="product-quantity" oninput="validateQuantity(this, ${product.amount})"> шт.</span>
        <input type="hidden" class="product-id" value="${product.id}">
    `;
    selectedProducts.appendChild(productDiv);

    document.getElementById('product-list-write-off').innerHTML = '';  
    updateTotalsWriteOff();
}
function validateQuantity(input, maxAmount) {
    const quantity = parseInt(input.value);
    if (quantity > maxAmount) {
        input.style.color = 'red';
        Swal.fire({
            icon: "error",
            title: "Помилка",
            text: "Недостатньо товарів на складі",           
        });
    } else {
        input.style.color = 'black';
    }
    updateTotalsWriteOff();
}

    function updateTotalsWriteOff() {
        const selectedProductsDiv = document.getElementById('selected-products-write-off');
        const productElements = selectedProductsDiv.getElementsByClassName('selected-product');
        let totalQuantity = 0;

        for (let productElement of productElements) {
            const quantity = parseInt(productElement.querySelector('.product-quantity').value);
            totalQuantity += quantity;
        }

        document.getElementById('total_quantity').value = totalQuantity;
    }

    function updateWriteOffHistory() {
        fetch('index_storage.php?action=get_write_off_history')
        .then(response => response.json())
        .then(data => {
            const writeOffHistoryBody = document.getElementById('write-off-history-body');
            writeOffHistoryBody.innerHTML = '';
            data.write_offs.forEach(writeOff => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${writeOff.write_off_number}</td>
                    <td>${writeOff.date}</td>
                    <td>${writeOff.comment}</td>
                    <td><button onclick="showWriteOffDetails(${writeOff.write_off_number})">?</button></td>
                `;
                writeOffHistoryBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
    }
    function showWriteOffDetails(writeOffId) {
        fetch(`index_storage.php?action=get_write_off_details&write_off_id=${writeOffId}`)
        .then(response => response.json())
        .then(data => {
            const writeOffDetailsContent = document.getElementById('write-off-details-content');
            writeOffDetailsContent.innerHTML = `
                <h2>Деталі списання</h2>
                <p>Номер списання: ${data.write_off_number} від ${data.date}</p>
                <br><p>Товари:<br> ${data.products}</p>
                <br><p>Коментар:<br> ${data.comment}</p>
                
            `;
            document.getElementById('write-off-details-modal').style.display = 'block';
        })
        .catch(error => console.error('Error:', error));
    }

    function hideWriteOffDetails() {
        document.getElementById('write-off-details-modal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateWriteOffHistory();
    });

    </script>
    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>

</html>
