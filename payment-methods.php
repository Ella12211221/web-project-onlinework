<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Process payment form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Process Mobile Banking Payment
        if (isset($_POST['process_mobile_payment'])) {
            $mobile_service = $_POST['mobile_service'] ?? '';
            $mobile_number = $_POST['mobile_number'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $reference_number = $_POST['reference_number'] ?? '';
            
            // Validate inputs
            if (empty($mobile_service) || empty($mobile_number) || $amount <= 0 || empty($reference_number)) {
                $error_message = "All fields are required for mobile banking payment.";
            } elseif ($amount < 100 || $amount > 50000) {
                $error_message = "Amount must be between Br100 and Br50,000 for mobile banking.";
            } else {
                // Insert payment record
                $insert_payment = $pdo->prepare("
                    INSERT INTO payment_transactions 
                    (user_id, payment_method, payment_service, mobile_number, amount, reference_number, status, created_at) 
                    VALUES (?, 'mobile_banking', ?, ?, ?, ?, 'pending', NOW())
                ");
                
                if ($insert_payment->execute([$_SESSION['user_id'], $mobile_service, $mobile_number, $amount, $reference_number])) {
                    $success_message = "Mobile banking payment submitted successfully! Reference: " . $reference_number . ". Your payment is being processed.";
                } else {
                    $error_message = "Failed to process mobile banking payment. Please try again.";
                }
            }
        }
        
        // Process Bank Transfer Payment
        elseif (isset($_POST['process_bank_payment'])) {
            $bank_name = $_POST['bank_name'] ?? '';
            $account_number = $_POST['account_number'] ?? '';
            $account_holder = $_POST['account_holder'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $reference_number = $_POST['reference_number'] ?? '';
            $branch_code = $_POST['branch_code'] ?? '';
            
            // Validate inputs
            if (empty($bank_name) || empty($account_number) || empty($account_holder) || $amount <= 0 || empty($reference_number)) {
                $error_message = "All required fields must be filled for bank transfer payment.";
            } elseif ($amount < 1000 || $amount > 500000) {
                $error_message = "Amount must be between Br1,000 and Br500,000 for bank transfer.";
            } else {
                // Insert payment record
                $insert_payment = $pdo->prepare("
                    INSERT INTO payment_transactions 
                    (user_id, payment_method, bank_name, account_number, account_holder, amount, reference_number, branch_code, status, created_at) 
                    VALUES (?, 'bank_transfer', ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                if ($insert_payment->execute([$_SESSION['user_id'], $bank_name, $account_number, $account_holder, $amount, $reference_number, $branch_code])) {
                    $success_message = "Bank transfer payment submitted successfully! Reference: " . $reference_number . ". Processing time: 1-2 hours.";
                } else {
                    $error_message = "Failed to process bank transfer payment. Please try again.";
                }
            }
        }
        
        // Process Digital Wallet Payment
        elseif (isset($_POST['process_wallet_payment'])) {
            $amount = floatval($_POST['amount'] ?? 0);
            $wallet_pin = $_POST['wallet_pin'] ?? '';
            $purpose = $_POST['purpose'] ?? '';
            
            // Validate inputs
            if ($amount <= 0 || empty($wallet_pin) || empty($purpose)) {
                $error_message = "All fields are required for wallet payment.";
            } elseif ($amount < 100) {
                $error_message = "Minimum wallet payment amount is Br100.";
            } elseif ($amount > ($user['account_balance'] ?? 0)) {
                $error_message = "Insufficient wallet balance. Available: Br" . number_format($user['account_balance'] ?? 0, 2);
            } else {
                // For demo purposes, we'll accept any 4-digit PIN
                if (strlen($wallet_pin) < 4) {
                    $error_message = "Transaction PIN must be at least 4 digits.";
                } else {
                    // Process wallet payment (deduct from balance)
                    $new_balance = ($user['account_balance'] ?? 0) - $amount;
                    
                    // Update user balance
                    $update_balance = $pdo->prepare("UPDATE users SET account_balance = ? WHERE id = ?");
                    
                    // Insert payment record
                    $insert_payment = $pdo->prepare("
                        INSERT INTO payment_transactions 
                        (user_id, payment_method, amount, purpose, status, created_at) 
                        VALUES (?, 'digital_wallet', ?, ?, 'completed', NOW())
                    ");
                    
                    if ($update_balance->execute([$new_balance, $_SESSION['user_id']]) && 
                        $insert_payment->execute([$_SESSION['user_id'], $amount, $purpose])) {
                        
                        // Update user data for display
                        $user['account_balance'] = $new_balance;
                        $success_message = "Wallet payment processed successfully! Br" . number_format($amount, 2) . " has been deducted from your wallet.";
                    } else {
                        $error_message = "Failed to process wallet payment. Please try again.";
                    }
                }
            }
        }
        
        // Process Withdrawal Registration
        elseif (isset($_POST['update_withdrawal_info'])) {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $withdrawal_account_number = trim($_POST['withdrawal_account_number'] ?? '');
            $withdrawal_phone = trim($_POST['withdrawal_phone'] ?? '');
            
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($withdrawal_account_number) || empty($withdrawal_phone)) {
                $error_message = "All withdrawal information fields are required.";
            } elseif (strlen($withdrawal_phone) < 10) {
                $error_message = "Please enter a valid phone number.";
            } elseif (strlen($withdrawal_account_number) < 8) {
                $error_message = "Please enter a valid account number.";
            } else {
                // Update user withdrawal information
                $update_withdrawal = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, withdrawal_account_number = ?, withdrawal_phone = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($update_withdrawal->execute([$first_name, $last_name, $withdrawal_account_number, $withdrawal_phone, $_SESSION['user_id']])) {
                    // Update user data for display
                    $user['first_name'] = $first_name;
                    $user['last_name'] = $last_name;
                    $user['withdrawal_account_number'] = $withdrawal_account_number;
                    $user['withdrawal_phone'] = $withdrawal_phone;
                    
                    $success_message = "Withdrawal information updated successfully! You can now process withdrawals.";
                } else {
                    $error_message = "Failed to update withdrawal information. Please try again.";
                }
            }
        }
        
        // Process Withdrawal Request
        elseif (isset($_POST['request_withdrawal'])) {
            $withdrawal_amount = floatval($_POST['withdrawal_amount'] ?? 0);
            $withdrawal_reason = $_POST['withdrawal_reason'] ?? '';
            $withdrawal_pin = $_POST['withdrawal_pin'] ?? '';
            
            // Check if withdrawal info is complete
            $withdrawal_complete = !empty($user['first_name']) && !empty($user['last_name']) && 
                                 !empty($user['withdrawal_account_number']) && !empty($user['withdrawal_phone']);
            
            if (!$withdrawal_complete) {
                $error_message = "Please complete your withdrawal information first.";
            } elseif ($withdrawal_amount <= 0 || empty($withdrawal_reason) || empty($withdrawal_pin)) {
                $error_message = "All withdrawal fields are required.";
            } elseif ($withdrawal_amount < 100) {
                $error_message = "Minimum withdrawal amount is Br100.";
            } elseif ($withdrawal_amount > ($user['account_balance'] ?? 0)) {
                $error_message = "Insufficient balance. Available: Br" . number_format($user['account_balance'] ?? 0, 2);
            } elseif (strlen($withdrawal_pin) < 4) {
                $error_message = "Transaction PIN must be at least 4 digits.";
            } else {
                // Calculate withdrawal fee (2% minimum Br10)
                $withdrawal_fee = max(10, $withdrawal_amount * 0.02);
                $total_deduction = $withdrawal_amount + $withdrawal_fee;
                
                if ($total_deduction > ($user['account_balance'] ?? 0)) {
                    $error_message = "Insufficient balance including fee. Required: Br" . number_format($total_deduction, 2) . " (Amount: Br" . number_format($withdrawal_amount, 2) . " + Fee: Br" . number_format($withdrawal_fee, 2) . ")";
                } else {
                    // Generate reference number
                    $reference_number = 'WD' . date('Ymd') . rand(1000, 9999);
                    
                    // Insert withdrawal request
                    $insert_withdrawal = $pdo->prepare("
                        INSERT INTO payment_transactions 
                        (user_id, payment_method, amount, reference_number, purpose, status, created_at) 
                        VALUES (?, 'withdrawal_request', ?, ?, ?, 'pending', NOW())
                    ");
                    
                    if ($insert_withdrawal->execute([$_SESSION['user_id'], $withdrawal_amount, $reference_number, $withdrawal_reason])) {
                        // Create admin notification for IMMEDIATE attention
                        $notification_stmt = $pdo->prepare("
                            INSERT INTO notifications 
                            (user_id, title, message, type, created_at) 
                            SELECT id, ?, ?, 'withdrawal', NOW() 
                            FROM users WHERE user_type = 'admin'
                        ");
                        $notification_title = "🚨 URGENT: New Withdrawal Request Requires Approval";
                        $notification_message = "User " . ($user['full_name'] ?? 'Unknown') . " has requested a withdrawal of Br" . number_format($withdrawal_amount, 2) . " for " . $withdrawal_reason . ". Reference: " . $reference_number . ". ADMIN APPROVAL REQUIRED BEFORE PROCESSING.";
                        $notification_stmt->execute([$notification_title, $notification_message]);
                        
                        $success_message = "Withdrawal request submitted successfully! 
                        
                        📋 IMPORTANT INFORMATION:
                        • Reference Number: " . $reference_number . "
                        • Amount: Br" . number_format($withdrawal_amount, 2) . "
                        • Reason: " . $withdrawal_reason . "
                        • Status: PENDING ADMIN APPROVAL
                        
                        ⏳ Your withdrawal request has been sent to our admin team for review and approval. 
                        
                        ⚠️ PLEASE NOTE: Funds will NOT be transferred until an admin manually approves your request. This typically takes 1-3 business days.";
                    } else {
                        $error_message = "Failed to submit withdrawal request. Please try again.";
                    }
                }
            }
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid #c3e6cb;">
                        <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-check-circle"></i> Payment Successful!</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid #f5c6cb;">
                        <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-exclamation-triangle"></i> Payment Error</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-credit-card"></i> Payment Methods
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Choose your preferred payment method for Ethiopian Birr investments
                    </p>
                </div>

                <!-- Payment Methods Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    
                    <!-- Withdrawal Information Section -->
                    <div style="grid-column: 1 / -1; background: rgba(255, 215, 0, 0.1); border: 2px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; margin-bottom: 2rem;">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="background: linear-gradient(45deg, #ffd700, #ffed4e); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-user-check" style="font-size: 2rem; color: #0a0e1a;"></i>
                            </div>
                            <h3 style="color: #ffd700; margin-bottom: 1rem;">Withdrawal Information</h3>
                            <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                                Complete your withdrawal information to enable fund withdrawals
                            </p>
                        </div>
                        
                        <?php
                        $withdrawal_complete = !empty($user['first_name']) && !empty($user['last_name']) && 
                                             !empty($user['withdrawal_account_number']) && !empty($user['withdrawal_phone']);
                        ?>
                        
                        <?php if ($withdrawal_complete): ?>
                            <div style="background: rgba(34, 139, 34, 0.2); border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="color: #228b22; margin-bottom: 1rem; text-align: center;">✅ Withdrawal Information Complete</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <strong style="color: rgba(255, 255, 255, 0.9);">Name:</strong>
                                        <div style="color: #ffd700;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                    </div>
                                    <div>
                                        <strong style="color: rgba(255, 255, 255, 0.9);">Account Number:</strong>
                                        <div style="color: #ffd700;"><?php echo htmlspecialchars($user['withdrawal_account_number']); ?></div>
                                    </div>
                                    <div>
                                        <strong style="color: rgba(255, 255, 255, 0.9);">Phone:</strong>
                                        <div style="color: #ffd700;"><?php echo htmlspecialchars($user['withdrawal_phone']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <button onclick="showWithdrawalForm()" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem; margin-right: 1rem;">
                                    <i class="fas fa-edit"></i> Update Information
                                </button>
                                <button onclick="showWithdrawalRequestForm()" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-money-bill-wave"></i> Request Withdrawal
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(231, 76, 60, 0.2); border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center;">
                                <h4 style="color: #e74c3c; margin-bottom: 1rem;">⚠️ Withdrawal Information Required</h4>
                                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 1rem;">
                                    Please complete your withdrawal information to enable fund withdrawals. This information is required for security and verification purposes.
                                </p>
                                <div style="background: rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                    <h5 style="color: #ffd700; margin-bottom: 0.5rem;">Required Information:</h5>
                                    <div style="display: grid; gap: 0.5rem; text-align: left;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                            <i class="fas fa-user" style="color: #ffd700;"></i>
                                            <span>First Name & Last Name</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                            <i class="fas fa-university" style="color: #ffd700;"></i>
                                            <span>Bank Account Number</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                            <i class="fas fa-phone" style="color: #ffd700;"></i>
                                            <span>Phone Number</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <button onclick="showWithdrawalForm()" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-user-plus"></i> Complete Withdrawal Information
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Banking -->
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 1rem; right: 1rem;">
                            <span style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">
                                Most Popular
                            </span>
                        </div>
                        
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="background: linear-gradient(45deg, #228b22, #32cd32); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-mobile-alt" style="font-size: 2rem; color: white;"></i>
                            </div>
                            <h3 style="color: #ffd700; margin-bottom: 1rem;">Mobile Banking</h3>
                            <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                                Fast and secure payments through Ethiopian mobile banking services
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: #228b22; margin-bottom: 1rem;">Supported Services:</h4>
                            <div style="display: grid; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #228b22;"></i>
                                    <span>CBE Birr</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #228b22;"></i>
                                    <span>M-Birr</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #228b22;"></i>
                                    <span>HelloCash</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #228b22;"></i>
                                    <span>Amole</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Processing Time:</span>
                                <span style="color: #ffd700; font-weight: bold;">Instant</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Transaction Fee:</span>
                                <span style="color: #228b22; font-weight: bold;">Free</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Daily Limit:</span>
                                <span style="color: #ffd700; font-weight: bold;">Br50,000</span>
                            </div>
                        </div>
                        
                        <button onclick="showMobileBankingForm()" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; width: 100%; cursor: pointer; font-size: 1rem;">
                            <i class="fas fa-mobile-alt"></i> Use Mobile Banking
                        </button>
                    </div>

                    <!-- Bank Transfer -->
                    <div style="background: rgba(74, 144, 226, 0.1); border: 1px solid rgba(74, 144, 226, 0.3); border-radius: 15px; padding: 2rem;">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="background: linear-gradient(45deg, #4a90e2, #357abd); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-university" style="font-size: 2rem; color: white;"></i>
                            </div>
                            <h3 style="color: #ffd700; margin-bottom: 1rem;">Bank Transfer</h3>
                            <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                                Traditional bank transfers for larger investments
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: #4a90e2; margin-bottom: 1rem;">Supported Banks:</h4>
                            <div style="display: grid; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #4a90e2;"></i>
                                    <span>Commercial Bank of Ethiopia</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #4a90e2;"></i>
                                    <span>Dashen Bank</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #4a90e2;"></i>
                                    <span>Awash Bank</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.9);">
                                    <i class="fas fa-check-circle" style="color: #4a90e2;"></i>
                                    <span>Bank of Abyssinia</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Processing Time:</span>
                                <span style="color: #ffd700; font-weight: bold;">1-2 Hours</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Transaction Fee:</span>
                                <span style="color: #228b22; font-weight: bold;">0.5%</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Daily Limit:</span>
                                <span style="color: #ffd700; font-weight: bold;">Br500,000</span>
                            </div>
                        </div>
                        
                        <button onclick="showBankTransferForm()" style="background: linear-gradient(45deg, #4a90e2, #357abd); color: white; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; width: 100%; cursor: pointer; font-size: 1rem;">
                            <i class="fas fa-university"></i> Use Bank Transfer
                        </button>
                    </div>

                    <!-- Digital Wallet -->
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem;">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="background: linear-gradient(45deg, #ffd700, #ffed4e); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-wallet" style="font-size: 2rem; color: #0a0e1a;"></i>
                            </div>
                            <h3 style="color: #ffd700; margin-bottom: 1rem;">Digital Wallet</h3>
                            <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                                Use your platform wallet balance for instant investments
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: #ffd700; margin-bottom: 1rem;">Current Balance:</h4>
                            <div style="text-align: center; background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1.5rem;">
                                <div style="font-size: 2rem; font-weight: bold; color: #ffd700; margin-bottom: 0.5rem;">
                                    Br<?php echo number_format($user['account_balance'] ?? 0, 2); ?>
                                </div>
                                <div style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">Available Balance</div>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Processing Time:</span>
                                <span style="color: #ffd700; font-weight: bold;">Instant</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Transaction Fee:</span>
                                <span style="color: #228b22; font-weight: bold;">Free</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: rgba(255, 255, 255, 0.8);">Daily Limit:</span>
                                <span style="color: #ffd700; font-weight: bold;">No Limit</span>
                            </div>
                        </div>
                        
                        <div style="display: grid; gap: 0.5rem;">
                            <button onclick="showWalletForm()" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; width: 100%; cursor: pointer; font-size: 1rem;">
                                <i class="fas fa-wallet"></i> Use Wallet Balance
                            </button>
                            <a href="wallet.php" style="background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 0.75rem 1.5rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; display: block;">
                                <i class="fas fa-plus"></i> Add Funds to Wallet
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Payment Security -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2); margin-bottom: 3rem;">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-shield-alt"></i> Payment Security & Information
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                        <div style="text-align: center;">
                            <div style="background: rgba(34, 139, 34, 0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-lock" style="font-size: 1.5rem; color: #228b22;"></i>
                            </div>
                            <h4 style="color: #228b22; margin-bottom: 1rem;">256-bit SSL Encryption</h4>
                            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                                All transactions are protected with bank-level security encryption
                            </p>
                        </div>
                        
                        <div style="text-align: center;">
                            <div style="background: rgba(74, 144, 226, 0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-clock" style="font-size: 1.5rem; color: #4a90e2;"></i>
                            </div>
                            <h4 style="color: #4a90e2; margin-bottom: 1rem;">24/7 Processing</h4>
                            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                                Payments are processed around the clock for your convenience
                            </p>
                        </div>
                        
                        <div style="text-align: center;">
                            <div style="background: rgba(255, 215, 0, 0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-headset" style="font-size: 1.5rem; color: #ffd700;"></i>
                            </div>
                            <h4 style="color: #ffd700; margin-bottom: 1rem;">Customer Support</h4>
                            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                                Get help with payments from our dedicated support team
                            </p>
                        </div>
                        
                        <div style="text-align: center;">
                            <div style="background: rgba(231, 76, 60, 0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-undo" style="font-size: 1.5rem; color: #e74c3c;"></i>
                            </div>
                            <h4 style="color: #e74c3c; margin-bottom: 1rem;">Refund Protection</h4>
                            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                                Failed transactions are automatically refunded within 24 hours
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="text-align: center;">
                    <a href="investments.php" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin: 0 1rem;">
                        <i class="fas fa-arrow-left"></i> Back to Investments
                    </a>
                    <a href="transactions.php" style="background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; text-decoration: none; font-weight: 600; margin: 0 1rem;">
                        <i class="fas fa-history"></i> Transaction History
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Payment Forms Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 15px; padding: 2rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 id="modalTitle" style="color: #2c3e50; margin: 0;">Payment Details</h3>
                <button onclick="closePaymentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">×</button>
            </div>
            
            <div id="modalContent">
                <!-- Content will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Concordial Nexus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function showMobileBankingForm() {
            document.getElementById('modalTitle').textContent = '📱 Mobile Banking Payment';
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" name="payment_method" value="mobile_banking">
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Select Mobile Banking Service</label>
                        <select name="mobile_service" required style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">Choose your mobile banking service</option>
                            <option value="cbe_birr">CBE Birr</option>
                            <option value="m_birr">M-Birr</option>
                            <option value="hello_cash">HelloCash</option>
                            <option value="amole">Amole</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Mobile Number</label>
                        <input type="tel" name="mobile_number" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your mobile number (e.g., +251912345678)">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Amount (Br)</label>
                        <input type="number" name="amount" required min="100" max="50000"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter amount (Min: Br100, Max: Br50,000)">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Transaction Reference Number</label>
                        <input type="text" name="reference_number" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter transaction reference number from your mobile banking">
                        <small style="color: #666; font-size: 0.85rem;">Complete the transaction on your mobile banking app first, then enter the reference number here.</small>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; border-left: 4px solid #27ae60;">
                        <h4 style="color: #27ae60; margin: 0 0 0.5rem 0;">Payment Summary</h4>
                        <p style="margin: 0; color: #2c3e50;">• Processing: Instant</p>
                        <p style="margin: 0; color: #2c3e50;">• Fee: Free</p>
                        <p style="margin: 0; color: #2c3e50;">• Daily Limit: Br50,000</p>
                    </div>
                    
                    <button type="submit" name="process_mobile_payment" 
                            style="background: linear-gradient(45deg, #27ae60, #32cd32); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-mobile-alt"></i> Process Mobile Payment
                    </button>
                </form>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function showBankTransferForm() {
            document.getElementById('modalTitle').textContent = '🏦 Bank Transfer Payment';
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" name="payment_method" value="bank_transfer">
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Select Bank</label>
                        <select name="bank_name" required style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">Choose your bank</option>
                            <option value="cbe">Commercial Bank of Ethiopia (CBE)</option>
                            <option value="dashen">Dashen Bank</option>
                            <option value="awash">Awash Bank</option>
                            <option value="boa">Bank of Abyssinia (BOA)</option>
                            <option value="nib">Nib International Bank</option>
                            <option value="wegagen">Wegagen Bank</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Account Number</label>
                        <input type="text" name="account_number" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your account number">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Account Holder Name</label>
                        <input type="text" name="account_holder" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter account holder name">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Amount (Br)</label>
                        <input type="number" name="amount" required min="1000" max="500000"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter amount (Min: Br1,000, Max: Br500,000)">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Transaction Reference Number</label>
                        <input type="text" name="reference_number" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter bank transfer reference number">
                        <small style="color: #666; font-size: 0.85rem;">Complete the bank transfer first, then enter the reference/confirmation number provided by your bank.</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Branch Code (Optional)</label>
                        <input type="text" name="branch_code" 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter branch code if required">
                    </div>
                    
                    <div style="background: #e8f4fd; padding: 1rem; border-radius: 8px; border-left: 4px solid #4a90e2;">
                        <h4 style="color: #4a90e2; margin: 0 0 0.5rem 0;">Payment Summary</h4>
                        <p style="margin: 0; color: #2c3e50;">• Processing: 1-2 hours</p>
                        <p style="margin: 0; color: #2c3e50;">• Fee: 0.5% of amount</p>
                        <p style="margin: 0; color: #2c3e50;">• Daily Limit: Br500,000</p>
                    </div>
                    
                    <button type="submit" name="process_bank_payment" 
                            style="background: linear-gradient(45deg, #4a90e2, #357abd); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-university"></i> Process Bank Transfer
                    </button>
                </form>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function showWalletForm() {
            document.getElementById('modalTitle').textContent = '💳 Digital Wallet Payment';
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" name="payment_method" value="digital_wallet">
                    
                    <div style="background: #fff8e1; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffd700;">
                        <h4 style="color: #f39c12; margin: 0 0 0.5rem 0;">Current Wallet Balance</h4>
                        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #2c3e50;">Br<?php echo number_format($user['account_balance'] ?? 0, 2); ?></p>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Amount to Use (Br)</label>
                        <input type="number" name="amount" required min="100" max="<?php echo $user['account_balance'] ?? 0; ?>"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter amount (Available: Br<?php echo number_format($user['account_balance'] ?? 0, 2); ?>)">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Transaction PIN</label>
                        <input type="password" name="wallet_pin" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your transaction PIN">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Purpose</label>
                        <select name="purpose" required style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">Select purpose</option>
                            <option value="investment">Investment Deposit</option>
                            <option value="trading">Trading Capital</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    
                    <div style="background: #fff8e1; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffd700;">
                        <h4 style="color: #f39c12; margin: 0 0 0.5rem 0;">Payment Summary</h4>
                        <p style="margin: 0; color: #2c3e50;">• Processing: Instant</p>
                        <p style="margin: 0; color: #2c3e50;">• Fee: Free</p>
                        <p style="margin: 0; color: #2c3e50;">• Daily Limit: No limit</p>
                    </div>
                    
                    <button type="submit" name="process_wallet_payment" 
                            style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-wallet"></i> Process Wallet Payment
                    </button>
                </form>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function showWithdrawalForm() {
            document.getElementById('modalTitle').textContent = '👤 Withdrawal Information';
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" name="update_withdrawal_info" value="1">
                    
                    <div style="background: #e8f4fd; padding: 1rem; border-radius: 8px; border-left: 4px solid #4a90e2;">
                        <h4 style="color: #4a90e2; margin: 0 0 0.5rem 0;">Withdrawal Information</h4>
                        <p style="margin: 0; color: #2c3e50; font-size: 0.9rem;">This information is required for withdrawal verification and security purposes.</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">First Name</label>
                            <input type="text" name="first_name" required 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                   style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                                   placeholder="Enter your first name">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Last Name</label>
                            <input type="text" name="last_name" required 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                   style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                                   placeholder="Enter your last name">
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Bank Account Number</label>
                        <input type="text" name="withdrawal_account_number" required 
                               value="<?php echo htmlspecialchars($user['withdrawal_account_number'] ?? ''); ?>"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your bank account number for withdrawals">
                        <small style="color: #666; font-size: 0.85rem;">This account will be used for all withdrawal transactions.</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Phone Number</label>
                        <input type="tel" name="withdrawal_phone" required 
                               value="<?php echo htmlspecialchars($user['withdrawal_phone'] ?? ''); ?>"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your phone number (e.g., +251912345678)">
                        <small style="color: #666; font-size: 0.85rem;">Used for withdrawal verification and notifications.</small>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <h4 style="color: #856404; margin: 0 0 0.5rem 0;">Security Notice</h4>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• All withdrawal information is encrypted and secure</p>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• This information is only used for withdrawal processing</p>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• You can update this information anytime</p>
                    </div>
                    
                    <button type="submit" name="update_withdrawal_info" 
                            style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-save"></i> Save Withdrawal Information
                    </button>
                </form>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function showWithdrawalRequestForm() {
            document.getElementById('modalTitle').textContent = '💰 Request Withdrawal';
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" name="request_withdrawal" value="1">
                    
                    <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; border-left: 4px solid #27ae60;">
                        <h4 style="color: #27ae60; margin: 0 0 0.5rem 0;">Available Balance</h4>
                        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #2c3e50;">Br<?php echo number_format($user['account_balance'] ?? 0, 2); ?></p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <h4 style="color: #2c3e50; margin: 0 0 0.5rem 0;">Withdrawal Details</h4>
                        <p style="margin: 0; color: #2c3e50; font-size: 0.9rem;"><strong>Name:</strong> <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></p>
                        <p style="margin: 0; color: #2c3e50; font-size: 0.9rem;"><strong>Account:</strong> <?php echo htmlspecialchars($user['withdrawal_account_number'] ?? ''); ?></p>
                        <p style="margin: 0; color: #2c3e50; font-size: 0.9rem;"><strong>Phone:</strong> <?php echo htmlspecialchars($user['withdrawal_phone'] ?? ''); ?></p>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Withdrawal Amount (Br)</label>
                        <input type="number" name="withdrawal_amount" required min="100" max="<?php echo $user['account_balance'] ?? 0; ?>"
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter withdrawal amount (Min: Br100)">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Withdrawal Reason</label>
                        <select name="withdrawal_reason" required style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">Select reason</option>
                            <option value="profit_withdrawal">Profit Withdrawal</option>
                            <option value="investment_return">Investment Return</option>
                            <option value="emergency">Emergency</option>
                            <option value="personal_use">Personal Use</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Transaction PIN</label>
                        <input type="password" name="withdrawal_pin" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter your transaction PIN">
                    </div>
                    
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <h4 style="color: #856404; margin: 0 0 0.5rem 0;">Withdrawal Information</h4>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• Processing Time: 1-3 business days</p>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• Minimum Amount: Br100</p>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• Withdrawal Fee: 2% (minimum Br10)</p>
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">• Requires admin approval</p>
                    </div>
                    
                    <button type="submit" name="request_withdrawal" 
                            style="background: linear-gradient(45deg, #27ae60, #2ecc71); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-money-bill-wave"></i> Request Withdrawal
                    </button>
                </form>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>