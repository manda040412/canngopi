<?php
// Include the connection file
include('../connection.php');

// Query for fetching data from order_items based on the order_list status
$sql = "SELECT order_items.OrderID, menu.nama_menu, order_items.Quantity, order_items.order_time, menu.image, order_items.notes
        FROM order_items 
        JOIN menu ON order_items.menuID = menu.menuID
        JOIN order_list ON order_items.OrderID = order_list.OrderID
        WHERE order_list.status = 'pending'";

$result = $conn->query($sql);

if (!$result) {
    error_log("SQL Query: " . $sql);  // Log the SQL query for debugging
    die("Query error: " . $conn->error);
}

// Query to count total orders that are not completed
$count_sql = "SELECT COUNT(DISTINCT OrderID) as total_orders FROM order_list WHERE status != 'completed'";
$count_result = $conn->query($count_sql);

$total_orders = 0;
if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_orders = $row['total_orders'];
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* CSS styling as per your code */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            background-color: #9D0000;
            color: white;
            width: 100%;
            padding: 10px 20px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .order-container {
            display: flex;
            overflow-x: auto;  
            width: 95%;
            padding: 30px 30px 15px 30px;
            gap: 70px; 
        }

        .order {
            background-color: #f7f7f7;
            width: 30%;
            height: 410px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
            position: relative;
            margin-bottom: 5px;
        } 

        .order-count {
            font-size: 18px;
            margin-left: 20px;
        }

        .cashier-name {
            font-size: 18px;
            margin-right: 20px;
        }
        
        .order-header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;      
            bottom: 15px;
        }

        .order-id {
            color: #777373;
        }

        .order-id-code {
            color: #7C0000;
        }

        .pesanan {
            margin-left: 15px;
            margin-top: -40px;
            margin-bottom: 25px;
            position: relative;
        }

        .order-list {
            list-style-type: none;
            padding-left: 0px;
            margin: 15px 15px 15px 15px;
            height: 285px;
            overflow-y: auto;
            position: relative;
            top: -15px;
        }

        .order-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-list img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            border-radius: 5px;
        }

        .item-detail {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }

        .menu-info {
            display: flex;
            flex-direction: column; /* Stack elements vertically */
            margin-right: auto; /* Ensure it aligns left with quantity */
        }

        .menu-name {
            font-weight: 450;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quantity {
            margin-left: auto;
            margin-right: 20px;
            text-align: right;
            width: 30px;
            flex-shrink: 0;
        }

        .complete-btn {
            background-color: #6C0000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            width: 50%;
            text-align: center;
            position: absolute;
            bottom: 5px;
            right: 10px;
            z-index: 2;
        }

        .menu-notes {
            margin-top: -15px; /* Add some space between menu name and notes */
            font-size: 14px;
            color: #555;
        }

        .footer {
            width: 100%;
            background-color: #9D0000;
            color: white;
            text-align: center;
            padding: 20px;
            position: fixed;
            bottom: -5px;
        }

        .footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="order-count">Jumlah pesanan: <?php echo $total_orders; ?></div>
        <img src="images/logo_cango.png" height="50" alt="logo cango">
        <div class="cashier-name">Nama Kasir: Andrew</div>
    </div>
    <!-- Tabel untuk menampilkan data order -->
    <div class="order-container">
        <?php
        if ($result->num_rows > 0) {
            // Mengelompokkan pesanan berdasarkan OrderID
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[$row['OrderID']][] = $row;
            }

            foreach ($orders as $orderID => $orderItems) {
                echo '<div class="order">';
                echo '<div class="order-header">';
                echo '<div class="order-id">Order ID: <span class="order-id-code">' . $orderID . '</span></div>';
                echo '<p class="order-time">' . date('H:i', strtotime($orderItems[0]['order_time'])) . '</p>';
                echo '</div>';
                echo '<h4 class="pesanan">Pesanan</h4>';
                echo '<ul class="order-list">';
                foreach ($orderItems as $item) {
                    echo '<li>';
                        if (!empty($item['image'])) {
                        // Mengambil base URL
                        $baseURL = "https://cobaadmin.canngopi.com";
                        
                        // Menghapus '/admin' jika ada dalam path
                        $imagePath = str_replace('/admin', '', $item['image']);
                        
                        // Menampilkan gambar dengan path dari database
                        echo '<img src="' . htmlspecialchars($baseURL . $imagePath) . '" alt="' . htmlspecialchars($item['nama_menu']) . '">';
                        } else {
                        // Placeholder jika tidak ada gambar
                        echo '<img src="https://via.placeholder.com/150" alt="Tidak ada gambar">';
                        }
                    echo '<div class="item-detail">';
                    
                    // Wrap menu name and notes in a div for vertical alignment
                    echo '<div class="menu-info">';
                    echo '<p class="menu-name">' . $item['nama_menu'] . '</p>';
                    
                    // Display the notes for the menu item if available
                    if (!empty($item['notes'])) {
                        echo '<p class="menu-notes">Notes: ' . htmlspecialchars($item['notes']) . '</p>';
                    } else {
                        echo '<p class="menu-notes">Notes: No notes provided</p>';
                    }
                    echo '</div>'; // Close menu-info div
                
                    echo '<p class="quantity">x' . $item['Quantity'] . '</p>'; // Quantity remains on the same line
                
                    echo '</div>'; // Close item-detail div
                    echo '</li>';
                }
                
                echo '</ul>';

                echo '<button class="complete-btn" onclick="completeOrder(' . $orderID . ')">Selesai</button>';
                echo '</div>';
            }
        } else {
            echo '<p>Tidak ada pesanan</p>';
        }
        ?>
    </div>
    <div class="footer">
        <p id="current-time">9:38:10</p>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            function fetchKitchenData() {
            $.ajax({
                url: 'KitchenStatus.php',
                method: 'GET',
                success: function (response) {
                    try {
                        console.log("Response received:", response);

                        if (response.error) {
                            console.error("Error from server:", response.error);
                            return;
                        }

                        // If the response contains the refresh signal, reload the page
                        if (response.refresh) {
                            console.log("Status is active, refreshing the page.");
                            location.reload(); // Refresh the page
                            return; // Ensure no further processing after the refresh
                        }

                        // Log the message (Optional)
                        console.log(response.message);
                    } catch (e) {
                        console.error("Error parsing response:", e);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    console.log("Response Text:", xhr.responseText); // Log the raw response text
                }
            });
        }
        // Poll every 5 seconds to fetch new orders
        setInterval(fetchKitchenData, 5000);
        
            let time = document.getElementById("current-time");
            setInterval(() => {
                let d = new Date();
                time.innerHTML = d.toLocaleTimeString();
            }, 1000);

            function completeOrder(orderID) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "complete_order.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);
                            document.querySelectorAll('.order').forEach(order => {
                                if (order.querySelector('.order-id-code').innerText === orderID.toString()) {
                                    order.remove();
                                }
                            });
                            document.querySelector('.order-count').innerText = `Jumlah pesanan: ${response.total_orders}`;
                        } else {
                            alert('Terjadi kesalahan saat menyelesaikan pesanan.');
                        }
                    }
                };
                xhr.send("orderID=" + orderID);
            }
        </script>
    </div>
</body>
</html>