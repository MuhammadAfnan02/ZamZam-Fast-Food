<?php
// ZamZam Fast Food - Admin Panel with Fixed Scrolling

// Define data file and directories
$dataFile = 'data.json';
$uploadDir = 'assets/';

// Create directories if they don't exist
if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

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
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($dataFile, $json);
}

// Handle file upload
function handleFileUpload($fileInputName, $targetDir) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$fileInputName];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($fileExt, $allowedExts)) {
        return null;
    }
    
    // Generate unique filename
    $fileName = uniqid('img_', true) . '.' . $fileExt;
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return null;
}

// Handle form submissions
$message = '';
$messageType = 'success';

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
            if (isset($_POST['phone']) && isset($_POST['location']) && isset($_POST['hours']) && isset($_POST['email']) && isset($_POST['address'])) {
                $data['contact']['phone'] = $_POST['phone'];
                $data['contact']['location'] = $_POST['location'];
                $data['contact']['hours'] = $_POST['hours'];
                $data['contact']['email'] = $_POST['email'];
                $data['contact']['address'] = $_POST['address'];
                saveData($data);
                $message = "Contact information updated successfully!";
            }
            break;
            
        case 'add_menu_item':
            if (isset($_POST['name']) && isset($_POST['price']) && isset($_POST['description']) && isset($_POST['category'])) {
                // Handle image upload
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imagePath = handleFileUpload('image', $uploadDir);
                }
                
                $newId = count($data['menu']) + 1;
                $newItem = [
                    'id' => $newId,
                    'name' => $_POST['name'],
                    'price' => floatval($_POST['price']),
                    'description' => $_POST['description'],
                    'image' => $imagePath ?: 'assets/default-food.png',
                    'category' => $_POST['category']
                ];
                $data['menu'][] = $newItem;
                saveData($data);
                $message = "Menu item added successfully!";
            }
            break;
            
        case 'edit_menu_item':
            if (isset($_POST['item_id']) && isset($_POST['name']) && isset($_POST['price']) && isset($_POST['description']) && isset($_POST['category'])) {
                $itemId = intval($_POST['item_id']);
                
                // Find and update item
                foreach ($data['menu'] as &$item) {
                    if ($item['id'] == $itemId) {
                        $item['name'] = $_POST['name'];
                        $item['price'] = floatval($_POST['price']);
                        $item['description'] = $_POST['description'];
                        $item['category'] = $_POST['category'];
                        
                        // Handle image upload if provided
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $imagePath = handleFileUpload('image', $uploadDir);
                            if ($imagePath) {
                                $item['image'] = $imagePath;
                            }
                        }
                        
                        break;
                    }
                }
                
                saveData($data);
                $message = "Menu item updated successfully!";
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
                
                // Handle image upload
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imagePath = handleFileUpload('image', $uploadDir);
                }
                
                // Get next ID for this category
                $newId = 1;
                if (isset($data['offers'][$category]) && is_array($data['offers'][$category])) {
                    $newId = count($data['offers'][$category]) + 1;
                }
                
                $newOffer = [
                    'id' => $newId,
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image' => $imagePath ?: 'assets/default-offer.png'
                ];
                
                if (!isset($data['offers'][$category])) {
                    $data['offers'][$category] = [];
                }
                
                $data['offers'][$category][] = $newOffer;
                saveData($data);
                $message = "Offer added successfully!";
            }
            break;
            
        case 'edit_offer':
            if (isset($_POST['category']) && isset($_POST['offer_id']) && isset($_POST['title']) && isset($_POST['description']) && isset($_POST['price'])) {
                $category = $_POST['category'];
                $offerId = intval($_POST['offer_id']);
                
                // Find and update offer
                if (isset($data['offers'][$category])) {
                    foreach ($data['offers'][$category] as &$offer) {
                        if ($offer['id'] == $offerId) {
                            $offer['title'] = $_POST['title'];
                            $offer['description'] = $_POST['description'];
                            $offer['price'] = $_POST['price'];
                            
                            // Handle image upload if provided
                            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                                $imagePath = handleFileUpload('image', $uploadDir);
                                if ($imagePath) {
                                    $offer['image'] = $imagePath;
                                }
                            }
                            
                            break;
                        }
                    }
                }
                
                saveData($data);
                $message = "Offer updated successfully!";
            }
            break;
            
        case 'delete_offer':
            if (isset($_POST['category']) && isset($_POST['offer_id'])) {
                $category = $_POST['category'];
                $offerId = intval($_POST['offer_id']);
                
                if (isset($data['offers'][$category])) {
                    $data['offers'][$category] = array_filter($data['offers'][$category], function($offer) use ($offerId) {
                        return $offer['id'] !== $offerId;
                    });
                    // Reindex array
                    $data['offers'][$category] = array_values($data['offers'][$category]);
                    saveData($data);
                    $message = "Offer deleted successfully!";
                }
            }
            break;
    }
}

// Load current data
$data = loadData();

// Get all offer categories
$offerCategories = [
    'student' => 'Student Offers',
    'family' => 'Family Offers',
    'teacher' => 'Teacher Offers',
    'special' => 'Special Offers',
    'combo' => 'Combo Offers'
];

// Add any additional categories from data
if (isset($data['offers'])) {
    foreach ($data['offers'] as $key => $offers) {
        if (!isset($offerCategories[$key])) {
            $offerCategories[$key] = ucfirst($key) . ' Offers';
        }
    }
}

// Get menu categories
$menuCategories = ['all', 'burgers', 'sandwiches', 'fries', 'drinks'];
if (isset($data['menu'])) {
    foreach ($data['menu'] as $item) {
        if (isset($item['category']) && !in_array($item['category'], $menuCategories)) {
            $menuCategories[] = $item['category'];
        }
    }
}

// Check if editing menu item
$editingMenuItem = null;
if (isset($_GET['edit_menu']) && is_numeric($_GET['edit_menu'])) {
    $editId = intval($_GET['edit_menu']);
    foreach ($data['menu'] as $item) {
        if ($item['id'] == $editId) {
            $editingMenuItem = $item;
            break;
        }
    }
}

// Check if editing offer
$editingOffer = null;
$editingOfferCategory = null;
if (isset($_GET['edit_offer']) && isset($_GET['category'])) {
    $editId = intval($_GET['edit_offer']);
    $category = $_GET['category'];
    if (isset($data['offers'][$category])) {
        foreach ($data['offers'][$category] as $offer) {
            if ($offer['id'] == $editId) {
                $editingOffer = $offer;
                $editingOfferCategory = $category;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZamZam Fast Food - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin Panel Styles - Fixed & Responsive */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-blue: #2563eb;
            --primary-dark-blue: #1d4ed8;
            --primary-light-blue: #3b82f6;
            --accent-orange: #f97316;
            --accent-red: #ef4444;
            --accent-green: #10b981;
            --white: #ffffff;
            --light-gray: #f8fafc;
            --medium-gray: #e2e8f0;
            --dark-gray: #64748b;
            --text-dark: #1e293b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            padding: 0;
            margin: 0;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Admin Header */
        .admin-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark-blue));
            color: white;
            padding: 25px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        .admin-header h1 {
            color: white;
            margin-bottom: 10px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-header p {
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
        }
        
        .admin-controls {
            position: absolute;
            top: 25px;
            right: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .admin-control-btn {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .admin-control-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* Admin Navigation */
        .admin-nav {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .admin-nav-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 100%;
        }
        
        .admin-nav-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-blue);
        }
        
        .admin-nav-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-blue);
        }
        
        .admin-nav-card h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .admin-nav-card p {
            color: var(--dark-gray);
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        /* Admin Sections */
        .admin-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--medium-gray);
            width: 100%;
            overflow: visible;
        }
        
        .admin-section h2 {
            color: var(--primary-blue);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
        }
        
        .admin-section h2 i {
            color: var(--primary-blue);
        }
        
        /* Forms */
        .admin-form {
            display: grid;
            gap: 20px;
            width: 100%;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            width: 100%;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .form-group label i {
            color: var(--primary-blue);
            width: 20px;
        }
        
        .form-control {
            padding: 12px 15px;
            border: 2px solid var(--medium-gray);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-gray);
            width: 100%;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: var(--white);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }
        
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%232563eb' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 14px;
            padding-right: 40px;
        }
        
        /* File Upload */
        .file-upload-container {
            border: 2px dashed var(--medium-gray);
            border-radius: var(--radius);
            padding: 25px;
            text-align: center;
            background: var(--light-gray);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            width: 100%;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .file-upload-container i {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .file-upload-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .image-preview {
            margin-top: 15px;
            max-width: 200px;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .image-preview img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        /* Buttons */
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
            width: 100%;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: var(--white);
            box-shadow: var(--shadow);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-success {
            background: var(--accent-green);
            color: var(--white);
            box-shadow: var(--shadow);
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: var(--white);
            box-shadow: var(--shadow);
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-warning {
            background: var(--accent-orange);
            color: var(--white);
            box-shadow: var(--shadow);
        }
        
        .btn-warning:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-blue);
            border-color: var(--primary-blue);
            box-shadow: var(--shadow);
        }
        
        .btn-outline:hover {
            background: var(--primary-blue);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Tables */
        .data-table-container {
            overflow-x: auto;
            margin-top: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--medium-gray);
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .data-table th {
            background: var(--primary-blue);
            color: var(--white);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--medium-gray);
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: var(--light-gray);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .table-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: var(--shadow);
        }
        
        .table-price {
            font-weight: 700;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .table-price::before {
            content: '₹';
            font-size: 0.9em;
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .table-action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .table-action-edit {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .table-action-edit:hover {
            background: var(--primary-dark-blue);
            transform: translateY(-2px);
        }
        
        .table-action-delete {
            background: var(--accent-red);
            color: var(--white);
        }
        
        .table-action-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .table-action-view {
            background: var(--accent-green);
            color: var(--white);
        }
        
        .table-action-view:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        /* Category Badges */
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .category-burgers { background: #f97316; }
        .category-sandwiches { background: #8b5cf6; }
        .category-fries { background: #f59e0b; }
        .category-drinks { background: #3b82f6; }
        .category-all { background: #64748b; }
        
        /* Offer Category Colors */
        .category-student { background: #06b6d4; }
        .category-family { background: #8b5cf6; }
        .category-teacher { background: #10b981; }
        .category-special { background: #f59e0b; }
        .category-combo { background: #ef4444; }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: none;
            animation: slideIn 0.3s ease;
            box-shadow: var(--shadow);
            border-left: 4px solid transparent;
        }
        
        .message.show {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        
        .message.warning {
            background: #fef3c7;
            color: #92400e;
            border-left-color: #f59e0b;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 50px 30px;
            color: var(--dark-gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--medium-gray);
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark-gray);
        }
        
        .empty-state p {
            max-width: 400px;
            margin: 0 auto 20px;
        }
        
        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 2px solid transparent;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-blue);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 12px;
            color: var(--primary-blue);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: var(--primary-blue);
        }
        
        .stat-card p {
            color: var(--dark-gray);
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        
        /* Footer */
        .admin-footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid var(--medium-gray);
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        
        .admin-footer a {
            color: var(--primary-blue);
            text-decoration: none;
        }
        
        .admin-footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .admin-nav {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
            
            .admin-header {
                padding: 20px;
                text-align: center;
            }
            
            .admin-controls {
                position: static;
                margin-top: 15px;
                justify-content: center;
            }
            
            .admin-header h1 {
                justify-content: center;
                font-size: 1.5rem;
            }
            
            .admin-nav {
                grid-template-columns: 1fr;
            }
            
            .admin-section {
                padding: 20px;
            }
            
            .admin-section h2 {
                font-size: 1.3rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                min-width: 600px;
            }
            
            .table-actions {
                flex-direction: column;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 1.3rem;
                flex-direction: column;
                gap: 8px;
            }
            
            .admin-control-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
            .admin-section {
                padding: 15px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.95rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .table-image {
                width: 60px;
                height: 60px;
            }
            
            .table-action-btn {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-card h3 {
                font-size: 1.8rem;
            }
        }
        
        /* Fix for very small screens */
        @media (max-width: 360px) {
            .admin-container {
                padding: 10px;
            }
            
            .admin-nav-card {
                padding: 20px;
            }
            
            .admin-nav-card h3 {
                font-size: 1.1rem;
            }
            
            .admin-nav-card i {
                font-size: 2rem;
            }
            
            .file-upload-container {
                padding: 20px;
            }
            
            .file-upload-container i {
                font-size: 2rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .admin-controls,
            .admin-nav,
            .btn,
            .table-actions {
                display: none !important;
            }
            
            .admin-section {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            
            .data-table-container {
                overflow: visible;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-cogs"></i>
                ZamZam Fast Food Admin
            </h1>
            <p>Manage your restaurant website content easily</p>
            <div class="admin-controls">
                <a href="index.html" class="admin-control-btn">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <a href="#dashboard" class="admin-control-btn">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?> show" id="admin-message">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <script>
            setTimeout(function() {
                const msg = document.getElementById('admin-message');
                if (msg) msg.classList.remove('show');
            }, 5000);
        </script>
        <?php endif; ?>
        
        <!-- Admin Navigation Cards -->
        <div class="admin-nav">
            <a href="#dashboard" class="admin-nav-card">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Dashboard</h3>
                <p>Overview of your restaurant</p>
            </a>
            <a href="#about" class="admin-nav-card">
                <i class="fas fa-info-circle"></i>
                <h3>About Section</h3>
                <p>Edit restaurant information</p>
            </a>
            <a href="#contact" class="admin-nav-card">
                <i class="fas fa-address-book"></i>
                <h3>Contact Info</h3>
                <p>Update contact details</p>
            </a>
            <a href="#menu" class="admin-nav-card">
                <i class="fas fa-utensils"></i>
                <h3>Menu Items</h3>
                <p>Manage food menu</p>
            </a>
            <a href="#offers" class="admin-nav-card">
                <i class="fas fa-tags"></i>
                <h3>Special Offers</h3>
                <p>Create and edit offers</p>
            </a>
        </div>
        
        <!-- Dashboard Section -->
        <div class="admin-section" id="dashboard">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-utensils"></i>
                    <h3><?php echo isset($data['menu']) ? count($data['menu']) : 0; ?></h3>
                    <p>Menu Items</p>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-tags"></i>
                    <h3>
                        <?php 
                        $totalOffers = 0;
                        if (isset($data['offers'])) {
                            foreach ($data['offers'] as $categoryOffers) {
                                if (is_array($categoryOffers)) {
                                    $totalOffers += count($categoryOffers);
                                }
                            }
                        }
                        echo $totalOffers;
                        ?>
                    </h3>
                    <p>Special Offers</p>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-layer-group"></i>
                    <h3><?php echo count($offerCategories); ?></h3>
                    <p>Offer Categories</p>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3 style="margin-bottom: 15px; color: var(--text-dark); font-size: 1.2rem;">Quick Actions</h3>
                <div class="btn-group">
                    <a href="#menu" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </a>
                    <a href="#offers" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Offer
                    </a>
                </div>
            </div>
        </div>
        
        <!-- About Section Editor -->
        <div class="admin-section" id="about">
            <h2><i class="fas fa-info-circle"></i> Edit About Section</h2>
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_about">
                <div class="form-group">
                    <label for="about_text">
                        <i class="fas fa-align-left"></i> About Us Text
                    </label>
                    <textarea id="about_text" name="about_text" class="form-control" required rows="6"><?php echo isset($data['about']['text']) ? htmlspecialchars($data['about']['text']) : ''; ?></textarea>
                    <small style="color: var(--dark-gray); margin-top: 5px; display: block;">
                        Use double line breaks to create paragraphs.
                    </small>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save About Text
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Contact Section Editor -->
        <div class="admin-section" id="contact">
            <h2><i class="fas fa-address-book"></i> Edit Contact Information</h2>
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_contact">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo isset($data['contact']['phone']) ? htmlspecialchars($data['contact']['phone']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($data['contact']['email']) ? htmlspecialchars($data['contact']['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </label>
                        <input type="text" id="location" name="location" class="form-control" value="<?php echo isset($data['contact']['location']) ? htmlspecialchars($data['contact']['location']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">
                            <i class="fas fa-map-pin"></i> Full Address
                        </label>
                        <input type="text" id="address" name="address" class="form-control" value="<?php echo isset($data['contact']['address']) ? htmlspecialchars($data['contact']['address']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hours">
                            <i class="fas fa-clock"></i> Opening Hours
                        </label>
                        <input type="text" id="hours" name="hours" class="form-control" value="<?php echo isset($data['contact']['hours']) ? htmlspecialchars($data['contact']['hours']) : ''; ?>" required>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Contact Info
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Menu Items Editor -->
        <div class="admin-section" id="menu">
            <h2><i class="fas fa-utensils"></i> Manage Menu Items</h2>
            
            <!-- Add/Edit Menu Item Form -->
            <h3 style="margin-bottom: 20px; color: var(--text-dark); font-size: 1.2rem;">
                <i class="fas fa-<?php echo $editingMenuItem ? 'edit' : 'plus'; ?>"></i>
                <?php echo $editingMenuItem ? 'Edit Menu Item' : 'Add New Menu Item'; ?>
            </h3>
            
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editingMenuItem ? 'edit_menu_item' : 'add_menu_item'; ?>">
                <?php if ($editingMenuItem): ?>
                <input type="hidden" name="item_id" value="<?php echo $editingMenuItem['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="item_name">
                            <i class="fas fa-hamburger"></i> Item Name
                        </label>
                        <input type="text" id="item_name" name="name" class="form-control" value="<?php echo $editingMenuItem ? htmlspecialchars($editingMenuItem['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_price">
                            <i class="fas fa-rupee-sign"></i> Price (₹)
                        </label>
                        <input type="number" id="item_price" name="price" class="form-control" step="1" min="0" value="<?php echo $editingMenuItem ? $editingMenuItem['price'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_category">
                            <i class="fas fa-filter"></i> Category
                        </label>
                        <select id="item_category" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($menuCategories as $category): ?>
                                <?php if ($category !== 'all'): ?>
                                <option value="<?php echo $category; ?>" <?php echo ($editingMenuItem && $editingMenuItem['category'] == $category) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($category); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="item_description">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea id="item_description" name="description" class="form-control" required rows="3"><?php echo $editingMenuItem ? htmlspecialchars($editingMenuItem['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i> Item Image
                    </label>
                    <div class="file-upload-container">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4 style="margin: 10px 0; font-size: 1rem;"><?php echo $editingMenuItem ? 'Change Image' : 'Upload Item Image'; ?></h4>
                        <p style="font-size: 0.85rem; color: var(--dark-gray); margin: 5px 0;">
                            Click to browse or drag & drop
                        </p>
                        <p style="font-size: 0.8rem; color: var(--dark-gray);">
                            Recommended: 400x300px, JPG, PNG, GIF, WebP
                        </p>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    
                    <?php if ($editingMenuItem && !empty($editingMenuItem['image'])): ?>
                    <div class="image-preview">
                        <p style="margin-bottom: 8px; color: var(--dark-gray); font-size: 0.9rem;">Current Image:</p>
                        <img src="<?php echo htmlspecialchars($editingMenuItem['image']); ?>" alt="Current item image">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $editingMenuItem ? 'edit' : 'plus'; ?>"></i>
                        <?php echo $editingMenuItem ? 'Update Menu Item' : 'Add Menu Item'; ?>
                    </button>
                    
                    <?php if ($editingMenuItem): ?>
                    <a href="admin.php#menu" class="btn btn-warning">
                        <i class="fas fa-times"></i> Cancel Edit
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Existing Menu Items Table -->
            <h3 style="margin-top: 40px; margin-bottom: 15px; color: var(--text-dark); font-size: 1.2rem;">
                <i class="fas fa-list"></i> Current Menu Items
                <span style="font-size: 0.9rem; color: var(--dark-gray); margin-left: 10px;">
                    (<?php echo isset($data['menu']) ? count($data['menu']) : 0; ?> items)
                </span>
            </h3>
            
            <?php if (isset($data['menu']) && count($data['menu']) > 0): ?>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['menu'] as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="table-image" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjcwIiBoZWlnaHQ9IjcwIiBmaWxsPSIjMjU2M2ViIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZTwvdGV4dD48L3N2Zz4='">
                                <?php else: ?>
                                <div style="width: 70px; height: 70px; background: var(--light-gray); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--dark-gray);">
                                    <i class="fas fa-image"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <br>
                                <small style="color: var(--dark-gray); font-size: 0.85rem;">
                                    <?php echo htmlspecialchars(substr($item['description'], 0, 60)) . (strlen($item['description']) > 60 ? '...' : ''); ?>
                                </small>
                            </td>
                            <td>
                                <span class="category-badge category-<?php echo $item['category'] ?? 'all'; ?>">
                                    <?php echo ucfirst($item['category'] ?? 'all'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-price"><?php echo number_format($item['price'], 0); ?></div>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="admin.php?edit_menu=<?php echo $item['id']; ?>#menu" class="table-action-btn table-action-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_menu_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="table-action-btn table-action-delete" onclick="return confirm('Delete this menu item?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-utensils"></i>
                <h3>No Menu Items Found</h3>
                <p>Add your first menu item using the form above.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Special Offers Editor -->
        <div class="admin-section" id="offers">
            <h2><i class="fas fa-tags"></i> Manage Special Offers</h2>
            
            <!-- Add/Edit Offer Form -->
            <h3 style="margin-bottom: 20px; color: var(--text-dark); font-size: 1.2rem;">
                <i class="fas fa-<?php echo $editingOffer ? 'edit' : 'plus'; ?>"></i>
                <?php echo $editingOffer ? 'Edit Offer' : 'Add New Offer'; ?>
            </h3>
            
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editingOffer ? 'edit_offer' : 'add_offer'; ?>">
                <?php if ($editingOffer): ?>
                <input type="hidden" name="offer_id" value="<?php echo $editingOffer['id']; ?>">
                <input type="hidden" name="category" value="<?php echo $editingOfferCategory; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="offer_category">
                            <i class="fas fa-filter"></i> Category
                        </label>
                        <select id="offer_category" name="category" class="form-control" required <?php echo $editingOffer ? 'disabled' : ''; ?>>
                            <option value="">Select Category</option>
                            <?php foreach ($offerCategories as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($editingOfferCategory == $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($editingOffer): ?>
                        <input type="hidden" name="category" value="<?php echo $editingOfferCategory; ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="offer_title">
                            <i class="fas fa-heading"></i> Offer Title
                        </label>
                        <input type="text" id="offer_title" name="title" class="form-control" value="<?php echo $editingOffer ? htmlspecialchars($editingOffer['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="offer_price">
                            <i class="fas fa-rupee-sign"></i> Price/Details
                        </label>
                        <input type="text" id="offer_price" name="price" class="form-control" value="<?php echo $editingOffer ? htmlspecialchars($editingOffer['price']) : ''; ?>" required>
                        <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                            Enter price (e.g., 249) or details (e.g., 20% off)
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="offer_description">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea id="offer_description" name="description" class="form-control" required rows="3"><?php echo $editingOffer ? htmlspecialchars($editingOffer['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i> Offer Image
                    </label>
                    <div class="file-upload-container">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4 style="margin: 10px 0; font-size: 1rem;"><?php echo $editingOffer ? 'Change Image' : 'Upload Offer Image'; ?></h4>
                        <p style="font-size: 0.85rem; color: var(--dark-gray); margin: 5px 0;">
                            Click to browse or drag & drop
                        </p>
                        <p style="font-size: 0.8rem; color: var(--dark-gray);">
                            Recommended: 400x300px, JPG, PNG, GIF, WebP
                        </p>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    
                    <?php if ($editingOffer && !empty($editingOffer['image'])): ?>
                    <div class="image-preview">
                        <p style="margin-bottom: 8px; color: var(--dark-gray); font-size: 0.9rem;">Current Image:</p>
                        <img src="<?php echo htmlspecialchars($editingOffer['image']); ?>" alt="Current offer image">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $editingOffer ? 'edit' : 'plus'; ?>"></i>
                        <?php echo $editingOffer ? 'Update Offer' : 'Add Offer'; ?>
                    </button>
                    
                    <?php if ($editingOffer): ?>
                    <a href="admin.php#offers" class="btn btn-warning">
                        <i class="fas fa-times"></i> Cancel Edit
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Existing Offers by Category -->
            <h3 style="margin-top: 40px; margin-bottom: 20px; color: var(--text-dark); font-size: 1.2rem;">
                <i class="fas fa-list"></i> Current Offers
            </h3>
            
            <?php if (isset($data['offers']) && count($data['offers']) > 0): ?>
                <?php foreach ($offerCategories as $categoryKey => $categoryName): ?>
                <?php if (isset($data['offers'][$categoryKey]) && is_array($data['offers'][$categoryKey]) && count($data['offers'][$categoryKey]) > 0): ?>
                <div style="margin-bottom: 30px;">
                    <h4 style="margin-bottom: 15px; color: var(--text-dark); display: flex; align-items: center; gap: 10px; font-size: 1.1rem;">
                        <span class="category-badge category-<?php echo $categoryKey; ?>" style="font-size: 0.7rem; padding: 3px 12px;">
                            <?php echo $categoryName; ?>
                        </span>
                        <span style="font-size: 0.85rem; color: var(--dark-gray);">
                            (<?php echo count($data['offers'][$categoryKey]); ?> offers)
                        </span>
                    </h4>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['offers'][$categoryKey] as $offer): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($offer['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($offer['image']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>" class="table-image" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjcwIiBoZWlnaHQ9IjcwIiBmaWxsPSIjM2U4MmY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5PZmZlcjwvdGV4dD48L3N2Zz4='">
                                        <?php else: ?>
                                        <div style="width: 70px; height: 70px; background: var(--light-gray); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--dark-gray);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($offer['title']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($offer['description'], 0, 60)) . (strlen($offer['description']) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <?php if (is_numeric($offer['price'])): ?>
                                        <div class="table-price"><?php echo number_format($offer['price'], 0); ?></div>
                                        <?php else: ?>
                                        <div style="font-weight: 600; color: var(--accent-orange);"><?php echo htmlspecialchars($offer['price']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="admin.php?edit_offer=<?php echo $offer['id']; ?>&category=<?php echo $categoryKey; ?>#offers" class="table-action-btn table-action-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_offer">
                                                <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                <button type="submit" class="table-action-btn table-action-delete" onclick="return confirm('Delete this offer?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h3>No Offers Found</h3>
                <p>Add your first offer using the form above.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="admin-footer">
            <p>
                &copy; 2023 ZamZam Fast Food - Admin Panel | 
                <a href="index.html">View Website</a> | 
                <a href="#dashboard">Back to Top ↑</a>
            </p>
        </div>
    </div>
    
    <script>
        // Admin Panel JavaScript - Fixed Scrolling
        document.addEventListener('DOMContentLoaded', function() {
            // Fix for admin panel scrolling
            const adminContainer = document.querySelector('.admin-container');
            const adminSections = document.querySelectorAll('.admin-section');
            
            // Ensure admin container has proper height
            adminContainer.style.minHeight = 'calc(100vh - 40px)';
            
            // Fix anchor link scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    
                    if (targetId !== '#') {
                        e.preventDefault();
                        const targetElement = document.querySelector(targetId);
                        
                        if (targetElement) {
                            // Calculate offset for fixed header
                            const headerHeight = document.querySelector('.admin-header').offsetHeight + 30;
                            const targetPosition = targetElement.offsetTop - headerHeight;
                            
                            // Smooth scroll to target
                            window.scrollTo({
                                top: targetPosition,
                                behavior: 'smooth'
                            });
                            
                            // Highlight section
                            targetElement.style.boxShadow = '0 0 0 2px rgba(37, 99, 235, 0.3)';
                            setTimeout(() => {
                                targetElement.style.boxShadow = '';
                            }, 1000);
                        }
                    }
                });
            });
            
            // Auto-hide messages
            const message = document.getElementById('admin-message');
            if (message) {
                setTimeout(() => {
                    message.classList.remove('show');
                    message.style.display = 'none';
                }, 5000);
            }
            
            // File upload preview
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const file = this.files[0];
                    const container = this.parentElement;
                    
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Remove existing preview
                            const oldPreview = container.nextElementSibling;
                            if (oldPreview && oldPreview.classList.contains('image-preview')) {
                                oldPreview.remove();
                            }
                            
                            // Create new preview
                            const preview = document.createElement('div');
                            preview.className = 'image-preview';
                            preview.innerHTML = `
                                <p style="margin-bottom: 8px; color: var(--dark-gray); font-size: 0.9rem;">Preview:</p>
                                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: auto;">
                            `;
                            container.parentNode.insertBefore(preview, container.nextSibling);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
            
            // Confirm delete actions
            const deleteButtons = document.querySelectorAll('.table-action-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Price input formatting
            const priceInputs = document.querySelectorAll('input[name="price"], input[name="item_price"]');
            priceInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.name === 'item_price') {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    }
                });
            });
            
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = this.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = 'var(--accent-red)';
                            field.focus();
                        } else {
                            field.style.borderColor = '';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
            
            // Responsive table scrolling fix
            const tableContainers = document.querySelectorAll('.data-table-container');
            tableContainers.forEach(container => {
                // Add horizontal scroll on touch devices
                let isScrolling = false;
                let startX, scrollLeft;
                
                container.addEventListener('touchstart', (e) => {
                    isScrolling = true;
                    startX = e.touches[0].pageX - container.offsetLeft;
                    scrollLeft = container.scrollLeft;
                });
                
                container.addEventListener('touchmove', (e) => {
                    if (!isScrolling) return;
                    e.preventDefault();
                    const x = e.touches[0].pageX - container.offsetLeft;
                    const walk = (x - startX) * 2;
                    container.scrollLeft = scrollLeft - walk;
                });
                
                container.addEventListener('touchend', () => {
                    isScrolling = false;
                });
            });
            
            // Initialize all sections with proper spacing
            adminSections.forEach((section, index) => {
                section.style.marginTop = index === 0 ? '0' : '30px';
            });
            
            // Fix for mobile viewport height
            function setViewportHeight() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }
            
            setViewportHeight();
            window.addEventListener('resize', setViewportHeight);
            
            // Scroll to top button for admin panel
            const scrollTopBtn = document.createElement('button');
            scrollTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            scrollTopBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                background: var(--primary-blue);
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                box-shadow: var(--shadow-lg);
                transition: var(--transition);
                z-index: 1000;
                opacity: 0;
                visibility: hidden;
            `;
            
            document.body.appendChild(scrollTopBtn);
            
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollTopBtn.style.opacity = '1';
                    scrollTopBtn.style.visibility = 'visible';
                } else {
                    scrollTopBtn.style.opacity = '0';
                    scrollTopBtn.style.visibility = 'hidden';
                }
            });
        });
    </script>
</body>
</html>
