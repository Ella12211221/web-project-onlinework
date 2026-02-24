<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';
$table_missing = false;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$table_check) {
        $table_missing = true;
        throw new Exception("Products table does not exist. Please run the setup script.");
    }
    
    // Handle Add Product
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $min_investment = !empty($_POST['min_investment']) ? floatval($_POST['min_investment']) : null;
        $max_investment = !empty($_POST['max_investment']) ? floatval($_POST['max_investment']) : null;
        $return_percentage = !empty($_POST['return_percentage']) ? floatval($_POST['return_percentage']) : null;
        $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : null;
        $image_url = trim($_POST['image_url']);
        $status = $_POST['status'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $insert = $pdo->prepare("
            INSERT INTO products 
            (name, description, category, price, min_investment, max_investment, 
             return_percentage, duration_days, image_url, status, featured, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($insert->execute([$name, $description, $category, $price, $min_investment, $max_investment, 
                              $return_percentage, $duration_days, $image_url, $status, $featured, $_SESSION['user_id']])) {
            $message = "Product added successfully!";
        } else {
            $error = "Failed to add product";
        }
    }
    
    // Handle Edit Product
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
        $id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $min_investment = !empty($_POST['min_investment']) ? floatval($_POST['min_investment']) : null;
        $max_investment = !empty($_POST['max_investment']) ? floatval($_POST['max_investment']) : null;
        $return_percentage = !empty($_POST['return_percentage']) ? floatval($_POST['return_percentage']) : null;
        $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : null;
        $image_url = trim($_POST['image_url']);
        $status = $_POST['status'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $update = $pdo->prepare("
            UPDATE products SET 
            name = ?, description = ?, category = ?, price = ?, 
            min_investment = ?, max_investment = ?, return_percentage = ?, 
            duration_days = ?, image_url = ?, status = ?, featured = ?
            WHERE id = ?
        ");
        
        if ($update->execute([$name, $description, $category, $price, $min_investment, $max_investment,
                              $return_percentage, $duration_days, $image_url, $status, $featured, $id])) {
            $message = "Product updated successfully!";
        } else {
            $error = "Failed to update product";
        }
    }
    
    // Handle Delete Product
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $delete = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($delete->execute([$id])) {
            $message = "Product deleted successfully!";
        } else {
            $error = "Failed to delete product";
        }
    }
    
    // Get all products
    $products = $pdo->query("
        SELECT p.*, u.full_name as creator_name
        FROM products p
        LEFT JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'inactive'")->fetchColumn(),
        'featured' => $pdo->query("SELECT COUNT(*) FROM products WHERE featured = 1")->fetchColumn()
    ];
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $products = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0];
} catch(Exception $e) {
    $error = $e->getMessage();
    $products = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3); }
        .stat-card.active { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.inactive { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.featured { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card h3 { font-size: 1rem; margin-bottom: 10px; opacity: 0.9; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: transform 0.2s; margin: 5px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: #4a90e2; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-back { background: #6c757d; color: white; }
        
        .form-section { background: white; border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #4a90e2; }
        
        .products-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 15px 10px; text-align: left; font-weight: 600; }
        td { padding: 12px 10px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.inactive { background: #f8d7da; color: #721c24; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; overflow-y: auto; }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; padding: 30px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-close { font-size: 2rem; cursor: pointer; color: #666; }
        .modal-close:hover { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Product Management</h1>
            <p>Concordial Nexus - Administrative Panel</p>
        </div>
        
        <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
        
        <?php if ($table_missing): ?>
            <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 30px; border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 30px rgba(231, 76, 60, 0.4);">
                <h2 style="margin: 0 0 15px 0; font-size: 2rem;">⚠️ Products Table Not Found</h2>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">The products table doesn't exist in your database yet. This is required for the product management system to work.</p>
                
                <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0;">🚀 Quick Fix (30 seconds):</h3>
                    <p style="margin-bottom: 15px;">Click the button below to automatically create the products table:</p>
                    <a href="../fix-deposit-product-tables.php" style="display: inline-block; background: white; color: #e74c3c; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1.1rem;">
                        ✅ Create Products Table Now
                    </a>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    <p style="margin: 0; font-size: 0.9rem;"><strong>Alternative:</strong> Run the setup script at <code style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">/database/setup-products-table.php</code></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card active">
                <h3>Active Products</h3>
                <div class="number"><?php echo $stats['active']; ?></div>
            </div>
            <div class="stat-card inactive">
                <h3>Inactive Products</h3>
                <div class="number"><?php echo $stats['inactive']; ?></div>
            </div>
            <div class="stat-card featured">
                <h3>Featured Products</h3>
                <div class="number"><?php echo $stats['featured']; ?></div>
            </div>
        </div>
        
        <button onclick="openAddModal()" class="btn btn-success" style="margin-bottom: 20px;">
            <i class="fas fa-plus"></i> Add New Product
        </button>
        
        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price/Range</th>
                        <th>Returns</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #666;">
                                No products found. Click "Add New Product" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong>#<?php echo $product['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>
                                    <?php if ($product['min_investment'] && $product['max_investment']): ?>
                                        Br<?php echo number_format($product['min_investment'], 0); ?> - Br<?php echo number_format($product['max_investment'], 0); ?>
                                    <?php else: ?>
                                        Br<?php echo number_format($product['price'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['return_percentage']): ?>
                                        <?php echo $product['return_percentage']; ?>%
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['duration_days']): ?>
                                        <?php echo $product['duration_days']; ?> days
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $product['status']; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['featured']): ?>
                                        <span style="color: #f39c12; font-size: 1.5rem;">⭐</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['creator_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <button onclick='editProduct(<?php echo json_encode($product); ?>)' class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;" onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="investment">Investment Package</option>
                            <option value="trading">Trading Plan</option>
                            <option value="service">Service</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Price (Br) *</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Min Investment (Br)</label>
                        <input type="number" name="min_investment" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Max Investment (Br)</label>
                        <input type="number" name="max_investment" step="0.01">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Return Percentage (%)</label>
                        <input type="number" name="return_percentage" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Duration (Days)</label>
                        <input type="number" name="duration_days">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured"> Featured Product (Show on homepage)
                    </label>
                </div>
                
                <button type="submit" name="add_product" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <button type="button" onclick="closeAddModal()" class="btn btn-back">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="product_id" id="edit_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" id="edit_category" required>
                            <option value="investment">Investment Package</option>
                            <option value="trading">Trading Plan</option>
                            <option value="service">Service</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Price (Br) *</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Min Investment (Br)</label>
                        <input type="number" name="min_investment" id="edit_min_investment" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Max Investment (Br)</label>
                        <input type="number" name="max_investment" id="edit_max_investment" step="0.01">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Return Percentage (%)</label>
                        <input type="number" name="return_percentage" id="edit_return_percentage" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Duration (Days)</label>
                        <input type="number" name="duration_days" id="edit_duration_days">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image_url" id="edit_image_url">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" id="edit_featured"> Featured Product
                    </label>
                </div>
                
                <button type="submit" name="edit_product" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <button type="button" onclick="closeEditModal()" class="btn btn-back">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_min_investment').value = product.min_investment || '';
            document.getElementById('edit_max_investment').value = product.max_investment || '';
            document.getElementById('edit_return_percentage').value = product.return_percentage || '';
            document.getElementById('edit_duration_days').value = product.duration_days || '';
            document.getElementById('edit_status').value = product.status;
            document.getElementById('edit_image_url').value = product.image_url || '';
            document.getElementById('edit_featured').checked = product.featured == 1;
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
