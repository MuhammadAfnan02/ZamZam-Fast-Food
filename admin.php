<?php
// ZamZam Fast Food - Admin Panel

// Define data file
$dataFile = 'data.json';

// Load existing data
function loadData() {
    global $dataFile;
    if (file_exists($dataFile)) {
        $json = file_get_contents($dataFile);
        return json_decode($json, true);
    }
    return [];
}

// Save data to file
function saveData($data) {
    global $dataFile;
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($dataFile, $json);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = loadData();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_about':
            if (isset($_POST['about_text'])) {
                $data['about']['text'] = $_POST['about_text'];
                saveData($data);
                $message = "About section updated successfully!";
            }
            break;
            
        case 'save_contact':
            if (isset($_POST['phone']) && isset($_POST['location']) && isset($_POST['hours'])) {
                $data['contact']['phone'] = $_POST['phone'];
                $data['contact']['location'] = $_POST['location'];
                $data['contact']['hours'] = $_POST['hours'];
                saveData($data);
                $message = "Contact information updated successfully!";
            }
            break;
            
        case 'add_menu_item':
            if (isset($_POST['name']) && isset($_POST['price']) && isset($_POST['description'])) {
                $newId = count($data['menu']) + 1;
                $newItem = [
                    'id' => $newId,
                    'name' => $_POST['name'],
                    'price' => floatval($_POST['price']),
                    'description' => $_POST['description']
                ];
                $data['menu'][] = $newItem;
                saveData($data);
                $message = "Menu item added successfully!";
            }
            break;
            
        case 'delete_menu_item':
            if (isset($_POST['item_id'])) {
                $itemId = intval($_POST['item_id']);
                $data['menu'] = array_filter($data['menu'], function($item) use ($itemId) {
                    return $item['id'] !== $itemId;
                });
                // Reindex array
                $data['menu'] = array_values($data['menu']);
                saveData($data);
                $message = "Menu item deleted successfully!";
            }
            break;
            
        case 'add_offer':
            if (isset($_POST['category']) && isset($_POST['title']) && isset($_POST['description']) && isset($_POST['price'])) {
                $category = $_POST['category'];
                $newId = count($data['offers'][$category]) + 1;
                $newOffer = [
                    'id' => $newId,
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price']
                ];
                $data['offers'][$category][] = $newOffer;
                saveData($data);
                $message = "Offer added successfully!";
            }
            break;
            
        case 'delete_offer':
            if (isset($_POST['category']) && isset($_POST['offer_id'])) {
                $category = $_POST['category'];
                $offerId = intval($_POST['offer_id']);
                $data['offers'][$category] = array_filter($data['offers'][$category], function($offer) use ($offerId) {
                    return $offer['id'] !== $offerId;
                });
                // Reindex array
                $data['offers'][$category] = array_values($data['offers'][$category]);
                saveData($data);
                $message = "Offer deleted successfully!";
            }
            break;
    }
}

// Load current data
$data = loadData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZamZam Fast Food - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin-specific styles */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark-blue) 100%);
            color: white;
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            color: white;
            margin-bottom: 10px;
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .admin-nav a {
            padding: 10px 20px;
            background-color: var(--white);
            color: var(--primary-blue);
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            border: 2px solid var(--medium-gray);
            transition: var(--transition);
        }
        
        .admin-nav a:hover {
            background-color: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }
        
        .admin-section {
            background-color: var(--white);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .admin-section h2 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .admin-form {
            display: grid;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-admin {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            align-self: flex-start;
        }
        
        .btn-admin:hover {
            background-color: var(--primary-dark-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-danger {
            background-color: #ef4444;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th {
            background-color: var(--light-gray);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--medium-gray);
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--medium-gray);
            vertical-align: top;
        }
        
        .data-table tr:hover {
            background-color: #f8fafc;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .message {
            padding: 15px;
            background-color: #10b981;
            color: white;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: none;
        }
        
        .message.show {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .admin-logout {
            float: right;
            margin-top: 10px;
        }
        
        .admin-logout a {
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: var(--radius);
            transition: var(--transition);
        }
        
        .admin-logout a:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-cogs"></i> ZamZam Fast Food - Admin Panel</h1>
            <p>Manage your restaurant content and data</p>
            <div class="admin-logout">
                <a href="index.html"><i class="fas fa-arrow-left"></i> Back to Website</a>
            </div>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="message show" id="success-message">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <script>
            // Hide message after 5 seconds
            setTimeout(function() {
                document.getElementById('success-message').classList.remove('show');
            }, 5000);
        </script>
        <?php endif; ?>
        
        <div class="admin-nav">
            <a href="#about">About Section</a>
            <a href="#contact">Contact Info</a>
            <a href="#menu">Menu Items</a>
            <a href="#offers">Special Offers</a>
        </div>
        
        <!-- About Section Editor -->
        <div class="admin-section" id="about">
            <h2><i class="fas fa-info-circle"></i> Edit About Section</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_about">
                <div class="form-group">
                    <label for="about_text">About Us Text</label>
                    <textarea id="about_text" name="about_text" class="form-control" required><?php echo isset($data['about']['text']) ? htmlspecialchars($data['about']['text']) : ''; ?></textarea>
                    <small>Use double line breaks to create paragraphs.</small>
                </div>
                <button type="submit" class="btn-admin">Save About Text</button>
            </form>
        </div>
        
        <!-- Contact Section Editor -->
        <div class="admin-section" id="contact">
            <h2><i class="fas fa-address-book"></i> Edit Contact Information</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_contact">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo isset($data['contact']['phone']) ? htmlspecialchars($data['contact']['phone']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="location">Location Address</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo isset($data['contact']['location']) ? htmlspecialchars($data['contact']['location']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="hours">Opening Hours</label>
                    <input type="text" id="hours" name="hours" class="form-control" value="<?php echo isset($data['contact']['hours']) ? htmlspecialchars($data['contact']['hours']) : ''; ?>" required>
                </div>
                <button type="submit" class="btn-admin">Save Contact Info</button>
            </form>
        </div>
        
        <!-- Menu Items Editor -->
        <div class="admin-section" id="menu">
            <h2><i class="fas fa-utensils"></i> Manage Menu Items</h2>
            
            <!-- Add New Menu Item Form -->
            <h3>Add New Menu Item</h3>
            <form method="POST" class="admin-form" style="margin-bottom: 30px;">
                <input type="hidden" name="action" value="add_menu_item">
                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="item_price">Price ($)</label>
                    <input type="number" id="item_price" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="item_description">Description</label>
                    <textarea id="item_description" name="description" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn-admin">Add Menu Item</button>
            </form>
            
            <!-- Existing Menu Items Table -->
            <h3>Current Menu Items</h3>
            <?php if (isset($data['menu']) && count($data['menu']) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['menu'] as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_menu_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to delete this menu item?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No menu items found. Add your first menu item above.</p>
            <?php endif; ?>
        </div>
        
        <!-- Special Offers Editor -->
        <div class="admin-section" id="offers">
            <h2><i class="fas fa-tags"></i> Manage Special Offers</h2>
            
            <!-- Add New Offer Form -->
            <h3>Add New Offer</h3>
            <form method="POST" class="admin-form" style="margin-bottom: 30px;">
                <input type="hidden" name="action" value="add_offer">
                <div class="form-group">
                    <label for="offer_category">Category</label>
                    <select id="offer_category" name="category" class="form-control" required>
                        <option value="student">Student Offers</option>
                        <option value="family">Family Offers</option>
                        <option value="special">Special Offers</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="offer_title">Offer Title</label>
                    <input type="text" id="offer_title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="offer_description">Description</label>
                    <textarea id="offer_description" name="description" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="offer_price">Price/Details</label>
                    <input type="text" id="offer_price" name="price" class="form-control" required>
                    <small>Enter price (e.g., $9.99) or details (e.g., 20% off)</small>
                </div>
                <button type="submit" class="btn-admin">Add Offer</button>
            </form>
            
            <!-- Existing Offers by Category -->
            <h3>Current Offers</h3>
            <?php if (isset($data['offers'])): ?>
                <?php foreach (['student' => 'Student Offers', 'family' => 'Family Offers', 'special' => 'Special Offers'] as $category => $categoryName): ?>
                <h4><?php echo $categoryName; ?></h4>
                <?php if (isset($data['offers'][$category]) && count($data['offers'][$category]) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['offers'][$category] as $offer): ?>
                        <tr>
                            <td><?php echo $offer['id']; ?></td>
                            <td><?php echo htmlspecialchars($offer['title']); ?></td>
                            <td><?php echo htmlspecialchars($offer['description']); ?></td>
                            <td><?php echo htmlspecialchars($offer['price']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_offer">
                                        <input type="hidden" name="category" value="<?php echo $category; ?>">
                                        <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                        <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to delete this offer?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No <?php echo strtolower($categoryName); ?> found. Add your first offer above.</p>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
            <p>No offers found. Add your first offer above.</p>
            <?php endif; ?>
        </div>
        
        <div class="footer-bottom" style="margin-top: 50px;">
            <p>&copy; 2023 ZamZam Fast Food. Admin Panel v1.0 | <a href="index.html" class="admin-link">View Website</a></p>
        </div>
    </div>
    
    <script>
        // Simple admin panel JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for admin nav links
            const adminNavLinks = document.querySelectorAll('.admin-nav a');
            
            adminNavLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        window.scrollTo({
                            top: targetSection.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>