<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';
$table_missing = false;
$products_missing = false;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if deposits table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'deposits'")->fetch();
    if (!$table_check) {
        $table_missing = true;
        throw new Exception("Deposits table does not exist. Please contact administrator.");
    }
    
    // Check if products table exists
    $products_table_check = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$products_table_check) {
        $products_missing = true;
    }
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Get active products
    $products = [];
    try {
        $products_stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY min_investment ASC");
        $products = $products_stmt->fetchAll();
    } catch(PDOException $e) {
        // Products table might not exist
    }
    
    // Handle deposit submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
        $amount = floatval($_POST['amount']);
        $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $payment_method = $_POST['payment_method'];
        $payment_reference = trim($_POST['payment_reference']);
        $bank_name = $_POST['bank_name'] ?? null;
        $account_number = $_POST['account_number'] ?? null;
        $mobile_number = $_POST['mobile_number'] ?? null;
        $payment_service = $_POST['payment_service'] ?? null;
        
        if ($amount < 100) {
            $error = "Minimum deposit amount is Br100.00";
        } elseif (empty($payment_reference)) {
            $error = "Payment reference number is required";
        } else {
            // Check if reference already used
            $check_ref = $pdo->prepare("SELECT id FROM deposits WHERE payment_reference = ?");
            $check_ref->execute([$payment_reference]);
            
            if ($check_ref->fetch()) {
                $error = "This payment reference number has already been used";
            } else {
                // Insert deposit request
                $insert = $pdo->prepare("
                    INSERT INTO deposits 
                    (user_id, product_id, amount, payment_method, payment_service, payment_reference, 
                     bank_name, account_number, mobile_number, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                if ($insert->execute([
                    $user['id'],
                    $product_id,
                    $amount, 
                    $payment_method, 
                    $payment_service,
                    $payment_reference,
                    $bank_name,
                    $account_number,
                    $mobile_number
                ])) {
                    $message = "Deposit request submitted successfully! Waiting for admin approval.";
                } else {
                    $error = "Failed to submit deposit request";
                }
            }
        }
    }
    
    // Get user's deposit history
    $deposits = $pdo->prepare("
        SELECT * FROM deposits 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $deposits->execute([$user['id']]);
    $deposit_history = $deposits->fetchAll();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $deposit_history = [];
} catch(Exception $e) {
    $error = $e->getMessage();
    $deposit_history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Deposit - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        
        /* Step Indicator */
        .steps-indicator { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; }
        .steps-indicator::before { content: ''; position: absolute; top: 25px; left: 0; right: 0; height: 3px; background: #e9ecef; z-index: 0; }
        .step { flex: 1; text-align: center; position: relative; z-index: 1; }
        .step-circle { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #999; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem; transition: all 0.3s; }
        .step.active .step-circle { background: #4a90e2; color: white; box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.2); }
        .step.completed .step-circle { background: #28a745; color: white; }
        .step-label { font-size: 0.9rem; color: #666; font-weight: 600; }
        .step.active .step-label { color: #4a90e2; }
        
        /* Form Steps */
        .form-step { display: none; }
        .form-step.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .deposit-form { background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .deposit-form h2 { color: #333; margin-bottom: 25px; font-size: 1.8rem; }
        
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; color: #333; font-weight: 600; font-size: 1.05rem; }
        .form-group label .required { color: #dc3545; margin-left: 3px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 1rem; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #4a90e2; box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1); }
        .form-group small { color: #666; font-size: 0.9rem; display: block; margin-top: 8px; }
        .form-group .help-text { background: #f8f9fa; padding: 12px; border-radius: 8px; margin-top: 10px; border-left: 3px solid #4a90e2; }
        
        .payment-methods { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .payment-method { padding: 20px; border: 3px solid #e9ecef; border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: center; position: relative; }
        .payment-method:hover { border-color: #4a90e2; background: #f8f9fa; transform: translateY(-2px); }
        .payment-method.selected { border-color: #4a90e2; background: #e8f4fd; box-shadow: 0 5px 15px rgba(74, 144, 226, 0.2); }
        .payment-method.selected::after { content: '✓'; position: absolute; top: 10px; right: 10px; background: #28a745; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .payment-method input[type="radio"] { display: none; }
        .payment-method i { font-size: 2.5rem; margin-bottom: 12px; }
        .payment-method strong { display: block; font-size: 1.1rem; margin-bottom: 5px; }
        
        .bank-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .bank-option { padding: 15px; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; transition: all 0.3s; text-align: center; }
        .bank-option:hover { border-color: #4a90e2; background: #f8f9fa; }
        .bank-option.selected { border-color: #4a90e2; background: #e8f4fd; font-weight: bold; }
        .bank-option input[type="radio"] { display: none; }
        
        .btn { background: #4a90e2; color: white; padding: 16px 32px; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; width: 100%; }
        .btn:hover { background: #357abd; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3); }
        .btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .btn-secondary { background: #6c757d; margin-right: 10px; width: auto; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn-back { background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        
        .info-box { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #17a2b8; }
        .info-box h3 { margin-bottom: 12px; font-size: 1.2rem; }
        .info-box ul { margin-left: 20px; line-height: 1.9; }
        .info-box strong { color: #0c5460; }
        
        .summary-box { background: #f8f9fa; padding: 20px; border-radius: 12px; border: 2px solid #e9ecef; }
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #dee2e6; }
        .summary-item:last-child { border-bottom: none; }
        .summary-label { color: #666; font-weight: 600; }
        .summary-value { color: #333; font-weight: bold; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .payment-methods, .bank-options { grid-template-columns: 1fr; }
            .steps-indicator { flex-wrap: wrap; }
            .btn-group { flex-direction: column; }
            .btn-secondary { width: 100%; margin-right: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Make a Deposit</h1>
            <p>Add funds to your account</p>
        </div>
        
        <a href="index.php" class="btn-back">← Back to Dashboard</a>
        
        <?php if ($table_missing): ?>
            <div style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(243, 156, 18, 0.4);">
                <h2 style="margin: 0 0 15px 0; font-size: 2rem;">⚠️ Deposit System Not Ready</h2>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">The deposit system is being set up. Please contact the administrator to complete the setup.</p>
                
                <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px;">
                    <h3 style="margin: 0 0 10px 0;">📞 What to do:</h3>
                    <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                        <li>Contact the system administrator</li>
                        <li>Ask them to run the deposit table setup</li>
                        <li>The setup takes only 30 seconds</li>
                        <li>You'll be able to make deposits after setup</li>
                    </ul>
                </div>
            </div>
        <?php elseif ($products_missing): ?>
            <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(52, 152, 219, 0.4);">
                <h2 style="margin: 0 0 15px 0; font-size: 2rem;">🛍️ Product System Not Set Up</h2>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">Investment packages are not available yet. You can still make deposits with custom amounts, or ask the administrator to set up products.</p>
                
                <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <h3 style="margin: 0 0 10px 0;">✅ You Can Still:</h3>
                    <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                        <li>Make deposits with any amount (minimum Br100)</li>
                        <li>Use all payment methods (bank transfer, mobile money)</li>
                        <li>Track your deposit history</li>
                    </ul>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    <p style="margin: 0; font-size: 0.95rem;"><strong>For Admin:</strong> Run <code style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">/fix-deposit-product-tables.php</code> to set up investment packages</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📝 3-Step Deposit Process</h3>
            <ul>
                <li><strong>Step 1:</strong> Choose payment method (Bank Transfer or Mobile Money)</li>
                <li><strong>Step 2:</strong> Enter bank details and reference number (REQUIRED)</li>
                <li><strong>Step 3:</strong> Review and submit your deposit request</li>
                <li><strong>Important:</strong> Reference number is mandatory - get it from your payment receipt</li>
            </ul>
        </div>
        
        <div class="deposit-form">
            <!-- Step Indicator -->
            <div class="steps-indicator">
                <div class="step active" id="step-indicator-1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Payment Method</div>
                </div>
                <div class="step" id="step-indicator-2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Bank & Reference</div>
                </div>
                <div class="step" id="step-indicator-3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Review & Submit</div>
                </div>
            </div>
            
            <form method="POST" id="depositForm">
                <!-- STEP 1: Payment Method -->
                <div class="form-step active" id="step-1">
                    <h2>Step 1: Choose Investment Level & Payment Method</h2>
                    
                    <?php if (!empty($products)): ?>
                    <div class="form-group">
                        <label>Select Investment Package (Optional)</label>
                        <select name="product_id" id="product_select" onchange="updateAmount()" style="padding: 14px; font-size: 1.05rem;">
                            <option value="">-- Choose a package or enter custom amount --</option>
                            <?php 
                            // Group products by category
                            $categories = ['regular' => 'Regular', 'premium' => 'Premium', 'vip_one' => 'VIP One', 'vip_two' => 'VIP Two', 'vip_three' => 'VIP Three'];
                            foreach ($categories as $cat_key => $cat_name):
                                $cat_products = array_filter($products, fn($p) => $p['category'] === $cat_key);
                                if (!empty($cat_products)):
                            ?>
                                <optgroup label="<?php echo $cat_name; ?> Packages">
                                    <?php foreach ($cat_products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" 
                                                data-min="<?php echo $product['min_amount'] ?? 0; ?>"
                                                data-return="<?php echo $product['return_percentage'] ?? 0; ?>"
                                                data-duration="<?php echo $product['duration_days'] ?? 0; ?>"
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> - 
                                            Br<?php echo number_format($product['min_amount'] ?? 0); ?>
                                            (<?php echo $product['return_percentage'] ?? 0; ?>% return, 
                                            <?php echo $product['duration_days'] ?? 0; ?> days)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                        <div id="product_info" style="display: none; background: #e8f4fd; padding: 15px; border-radius: 10px; margin-top: 12px; border-left: 4px solid #4a90e2;">
                            <strong style="color: #4a90e2; font-size: 1.1rem;">📦 Selected Package:</strong>
                            <div id="product_details" style="margin-top: 8px; color: #333; line-height: 1.8;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Select How You Want to Pay <span class="required">*</span></label>
                        <div class="payment-methods">
                            <label class="payment-method" onclick="selectPaymentMethod('bank_transfer', this)">
                                <input type="radio" name="payment_method" value="bank_transfer" required>
                                <i class="fas fa-university" style="color: #4a90e2;"></i>
                                <strong>Bank Transfer</strong>
                                <small>CBE, Dashen, Awash, BOA</small>
                            </label>
                            
                            <label class="payment-method" onclick="selectPaymentMethod('mobile_banking', this)">
                                <input type="radio" name="payment_method" value="mobile_banking" required>
                                <i class="fas fa-mobile-alt" style="color: #28a745;"></i>
                                <strong>Mobile Money</strong>
                                <small>CBE Birr, M-Birr, Amole</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deposit Amount (Br) <span class="required">*</span></label>
                        <input type="number" name="amount" id="amount" step="0.01" min="100" required placeholder="Enter amount (minimum Br100)" style="font-size: 1.2rem; font-weight: bold;">
                        <small class="help-text">💡 Minimum deposit: Br100.00</small>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn" onclick="nextStep(2)" id="step1-next">
                            Continue to Bank Details <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- STEP 2: Bank Details & Reference -->
                <div class="form-step" id="step-2">
                    <h2>Step 2: Bank Details & Reference Number</h2>
                    
                    <!-- Bank Transfer Details -->
                    <div id="bank_transfer_details" class="bank-details">
                        <div class="form-group">
                            <label>Select Your Bank <span class="required">*</span></label>
                            <div class="bank-options">
                                <label class="bank-option" onclick="selectBank('Commercial Bank of Ethiopia', this)">
                                    <input type="radio" name="bank_name" value="Commercial Bank of Ethiopia">
                                    <i class="fas fa-university"></i> CBE
                                </label>
                                <label class="bank-option" onclick="selectBank('Dashen Bank', this)">
                                    <input type="radio" name="bank_name" value="Dashen Bank">
                                    <i class="fas fa-university"></i> Dashen
                                </label>
                                <label class="bank-option" onclick="selectBank('Awash Bank', this)">
                                    <input type="radio" name="bank_name" value="Awash Bank">
                                    <i class="fas fa-university"></i> Awash
                                </label>
                                <label class="bank-option" onclick="selectBank('Bank of Abyssinia', this)">
                                    <input type="radio" name="bank_name" value="Bank of Abyssinia">
                                    <i class="fas fa-university"></i> BOA
                                </label>
                                <label class="bank-option" onclick="selectBank('Wegagen Bank', this)">
                                    <input type="radio" name="bank_name" value="Wegagen Bank">
                                    <i class="fas fa-university"></i> Wegagen
                                </label>
                                <label class="bank-option" onclick="selectBank('United Bank', this)">
                                    <input type="radio" name="bank_name" value="United Bank">
                                    <i class="fas fa-university"></i> United
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Account Number <span class="required">*</span></label>
                            <input type="text" name="account_number" id="account_number" placeholder="Enter your bank account number">
                            <small class="help-text">💡 Enter the account number you used to make the payment</small>
                        </div>
                    </div>
                    
                    <!-- Mobile Banking Details -->
                    <div id="mobile_banking_details" class="bank-details">
                        <div class="form-group">
                            <label>Mobile Money Service <span class="required">*</span></label>
                            <select name="payment_service" id="payment_service">
                                <option value="">Select Service</option>
                                <option value="CBE Birr">CBE Birr</option>
                                <option value="M-Birr">M-Birr</option>
                                <option value="HelloCash">HelloCash</option>
                                <option value="Amole">Amole</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Mobile Number <span class="required">*</span></label>
                            <input type="text" name="mobile_number" id="mobile_number" placeholder="+251911234567">
                            <small class="help-text">💡 Enter the phone number you used to make the payment</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Reference Number <span class="required">*</span></label>
                        <input type="text" name="payment_reference" id="payment_reference" required placeholder="Enter transaction reference number" style="text-transform: uppercase; font-family: monospace; font-size: 1.1rem; font-weight: bold; letter-spacing: 1px;">
                        <small class="help-text">
                            <strong>⚠️ REQUIRED:</strong> This is the transaction ID/reference number from your payment receipt. 
                            Example: TXN123456789 or REF-2024-001
                        </small>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn" onclick="nextStep(3)" id="step2-next">
                            Continue to Review <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- STEP 3: Review & Submit -->
                <div class="form-step" id="step-3">
                    <h2>Step 3: Review Your Deposit</h2>
                    
                    <div class="summary-box">
                        <h3 style="margin-bottom: 20px; color: #333;">📋 Deposit Summary</h3>
                        
                        <div class="summary-item">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value" id="summary-method">-</span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-label">Bank/Service:</span>
                            <span class="summary-value" id="summary-bank">-</span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-label">Account/Mobile:</span>
                            <span class="summary-value" id="summary-account">-</span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-label">Amount:</span>
                            <span class="summary-value" id="summary-amount" style="color: #28a745; font-size: 1.3rem;">-</span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-label">Reference Number:</span>
                            <span class="summary-value" id="summary-reference" style="font-family: monospace; color: #4a90e2;">-</span>
                        </div>
                    </div>
                    
                    <div class="info-box" style="margin-top: 20px;">
                        <h3>✅ Before Submitting</h3>
                        <ul>
                            <li>Double-check your reference number is correct</li>
                            <li>Make sure the amount matches your payment</li>
                            <li>Keep your payment receipt until approved</li>
                            <li>Admin will review within 24 hours</li>
                        </ul>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" name="submit_deposit" class="btn" style="background: #28a745;">
                            <i class="fas fa-check-circle"></i> Submit Deposit Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="deposit-history">
            <h2>📋 My Deposit History</h2>
            
            <?php if (empty($deposit_history)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No deposit history yet</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference Number</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposit_history as $deposit): ?>
                            <tr>
                                <td><?php echo date('M j, Y H:i', strtotime($deposit['created_at'])); ?></td>
                                <td><strong>Br<?php echo number_format($deposit['amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($deposit['payment_method']); ?></td>
                                <td style="font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($deposit['payment_reference']); ?></td>
                                <td><span class="status <?php echo $deposit['status']; ?>"><?php echo ucfirst($deposit['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($deposit['admin_notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let selectedPaymentMethod = '';
        
        function updateAmount() {
            const select = document.getElementById('product_select');
            const option = select.options[select.selectedIndex];
            const amountInput = document.getElementById('amount');
            const productInfo = document.getElementById('product_info');
            const productDetails = document.getElementById('product_details');
            
            if (option.value) {
                const amount = parseFloat(option.dataset.min);
                const returnPct = parseFloat(option.dataset.return);
                const duration = parseInt(option.dataset.duration);
                const name = option.dataset.name;
                
                // Set amount
                amountInput.value = amount;
                amountInput.readOnly = true;
                amountInput.style.background = '#f8f9fa';
                
                // Calculate profit
                const profit = (amount * returnPct) / 100;
                const total = amount + profit;
                
                // Show product info
                let details = `
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <div><strong>Package:</strong> ${name}</div>
                        <div><strong>Amount:</strong> Br${amount.toLocaleString()}</div>
                        <div><strong>Return:</strong> ${returnPct}%</div>
                        <div><strong>Duration:</strong> ${duration} days</div>
                        <div><strong>Profit:</strong> <span style="color: #28a745;">Br${profit.toLocaleString()}</span></div>
                        <div><strong>Total Return:</strong> <span style="color: #4a90e2; font-weight: bold;">Br${total.toLocaleString()}</span></div>
                    </div>
                `;
                productDetails.innerHTML = details;
                productInfo.style.display = 'block';
            } else {
                amountInput.value = '';
                amountInput.readOnly = false;
                amountInput.style.background = 'white';
                productInfo.style.display = 'none';
            }
        }
        
        function nextStep(step) {
            // Validate current step
            if (currentStep === 1) {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                const amount = document.getElementById('amount').value;
                
                if (!paymentMethod) {
                    alert('Please select a payment method');
                    return;
                }
                if (!amount || parseFloat(amount) < 100) {
                    alert('Please enter a valid amount (minimum Br100)');
                    return;
                }
            }
            
            if (currentStep === 2) {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                const reference = document.getElementById('payment_reference').value.trim();
                
                if (!reference) {
                    alert('Payment reference number is REQUIRED!');
                    document.getElementById('payment_reference').focus();
                    return;
                }
                
                if (paymentMethod === 'bank_transfer') {
                    const bank = document.querySelector('input[name="bank_name"]:checked');
                    const account = document.getElementById('account_number').value.trim();
                    
                    if (!bank) {
                        alert('Please select your bank');
                        return;
                    }
                    if (!account) {
                        alert('Please enter your account number');
                        document.getElementById('account_number').focus();
                        return;
                    }
                } else {
                    const service = document.getElementById('payment_service').value;
                    const mobile = document.getElementById('mobile_number').value.trim();
                    
                    if (!service) {
                        alert('Please select mobile money service');
                        return;
                    }
                    if (!mobile) {
                        alert('Please enter your mobile number');
                        document.getElementById('mobile_number').focus();
                        return;
                    }
                }
                
                // Update summary
                updateSummary();
            }
            
            // Hide current step
            document.getElementById('step-' + currentStep).classList.remove('active');
            document.getElementById('step-indicator-' + currentStep).classList.remove('active');
            document.getElementById('step-indicator-' + currentStep).classList.add('completed');
            
            // Show next step
            currentStep = step;
            document.getElementById('step-' + currentStep).classList.add('active');
            document.getElementById('step-indicator-' + currentStep).classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function prevStep(step) {
            // Hide current step
            document.getElementById('step-' + currentStep).classList.remove('active');
            document.getElementById('step-indicator-' + currentStep).classList.remove('active');
            
            // Show previous step
            currentStep = step;
            document.getElementById('step-' + currentStep).classList.add('active');
            document.getElementById('step-indicator-' + currentStep).classList.add('active');
            document.getElementById('step-indicator-' + currentStep).classList.remove('completed');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function selectPaymentMethod(method, element) {
            // Remove selected class from all
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            
            // Add selected class to clicked
            element.classList.add('selected');
            
            // Store selected method
            selectedPaymentMethod = method;
            
            // Show/hide relevant details in step 2
            if (method === 'mobile_banking') {
                document.getElementById('mobile_banking_details').style.display = 'block';
                document.getElementById('bank_transfer_details').style.display = 'none';
                
                document.getElementById('payment_service').required = true;
                document.getElementById('mobile_number').required = true;
                document.querySelectorAll('input[name="bank_name"]').forEach(el => el.required = false);
                document.getElementById('account_number').required = false;
            } else {
                document.getElementById('mobile_banking_details').style.display = 'none';
                document.getElementById('bank_transfer_details').style.display = 'block';
                
                document.getElementById('payment_service').required = false;
                document.getElementById('mobile_number').required = false;
                document.querySelectorAll('input[name="bank_name"]').forEach(el => el.required = true);
                document.getElementById('account_number').required = true;
            }
        }
        
        function selectBank(bankName, element) {
            // Remove selected class from all
            document.querySelectorAll('.bank-option').forEach(el => el.classList.remove('selected'));
            
            // Add selected class to clicked
            element.classList.add('selected');
        }
        
        function updateSummary() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const amount = document.getElementById('amount').value;
            const reference = document.getElementById('payment_reference').value;
            
            // Update payment method
            document.getElementById('summary-method').textContent = 
                paymentMethod === 'bank_transfer' ? 'Bank Transfer' : 'Mobile Money';
            
            // Update bank/service
            if (paymentMethod === 'bank_transfer') {
                const bank = document.querySelector('input[name="bank_name"]:checked');
                const account = document.getElementById('account_number').value;
                document.getElementById('summary-bank').textContent = bank ? bank.value : '-';
                document.getElementById('summary-account').textContent = account || '-';
            } else {
                const service = document.getElementById('payment_service').value;
                const mobile = document.getElementById('mobile_number').value;
                document.getElementById('summary-bank').textContent = service || '-';
                document.getElementById('summary-account').textContent = mobile || '-';
            }
            
            // Update amount
            document.getElementById('summary-amount').textContent = 'Br' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Update reference
            document.getElementById('summary-reference').textContent = reference.toUpperCase();
        }
        
        // Form validation before submit
        document.getElementById('depositForm').addEventListener('submit', function(e) {
            const reference = document.getElementById('payment_reference').value.trim();
            if (!reference) {
                e.preventDefault();
                alert('Payment reference number is REQUIRED!');
                prevStep(2);
                document.getElementById('payment_reference').focus();
                return false;
            }
        });
    </script>
</body>
</html>
