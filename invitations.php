<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Codes - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    session_start();
    
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: ../auth/login.php');
        exit();
    }
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['create_code'])) {
                $code = strtoupper($_POST['code']);
                $max_uses = intval($_POST['max_uses']);
                $bonus_amount = floatval($_POST['bonus_amount']);
                $description = $_POST['description'];
                $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
                
                $create_stmt = $pdo->prepare("INSERT INTO invitation_codes (code, created_by, max_uses, bonus_amount, description, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                if ($create_stmt->execute([$code, $_SESSION['user_id'], $max_uses, $bonus_amount, $description, $expires_at])) {
                    $success_message = "Invitation code '$code' created successfully!";
                } else {
                    $error_message = "Failed to create invitation code!";
                }
            }
            
            if (isset($_POST['toggle_status'])) {
                $code_id = $_POST['code_id'];
                $new_status = $_POST['new_status'];
                
                $toggle_stmt = $pdo->prepare("UPDATE invitation_codes SET is_active = ? WHERE id = ?");
                if ($toggle_stmt->execute([$new_status, $code_id])) {
                    $success_message = "Invitation code status updated!";
                }
            }
        }
        
        // Get invitation code statistics
        $total_codes = $pdo->query("SELECT COUNT(*) FROM invitation_codes")->fetchColumn();
        $active_codes = $pdo->query("SELECT COUNT(*) FROM invitation_codes WHERE is_active = 1")->fetchColumn();
        $total_uses = $pdo->query("SELECT SUM(current_uses) FROM invitation_codes")->fetchColumn() ?: 0;
        $total_bonus_paid = $pdo->query("SELECT SUM(amount) FROM transactions WHERE description LIKE '%invitation code%'")->fetchColumn() ?: 0;
        
        // Get all invitation codes with usage info
        $codes_stmt = $pdo->query("
            SELECT ic.*, u.full_name as creator_name, u.email as creator_email,
                   COUNT(DISTINCT u2.id) as users_registered,
                   CASE WHEN ic.created_by IS NOT NULL THEN 'User Generated' ELSE 'Admin Created' END as code_type
            FROM invitation_codes ic 
            LEFT JOIN users u ON ic.created_by = u.id 
            LEFT JOIN users u2 ON ic.code = u2.invitation_code_used 
            GROUP BY ic.id 
            ORDER BY ic.created_at DESC
        ");
        $invitation_codes = $codes_stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
    ?>
    
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.html"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="transactions.php" class="nav-link">Transactions</a></li>
                    <li class="nav-item"><a href="invitations.php" class="nav-link active">Invitations</a></li>
                    <li class="nav-item"><a href="../index.html" class="nav-link">Website</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-ticket-alt"></i> Invitation Code Management
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Create and manage invitation codes for Ethiopian Birr trading
                    </p>
                </div>

                <?php if (isset($success_message)): ?>
                    <div style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(34, 139, 34, 0.3);">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(255, 82, 82, 0.3);">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 1rem;">
                            <i class="fas fa-ticket-alt"></i> Total Codes
                        </h4>
                        <div style="font-size: 2rem; color: #ffd700; font-weight: bold;">
                            <?php echo $total_codes; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-check-circle"></i> Active Codes
                        </h4>
                        <div style="font-size: 2rem; color: #228b22; font-weight: bold;">
                            <?php echo $active_codes; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 1rem;">
                            <i class="fas fa-users"></i> Total Uses
                        </h4>
                        <div style="font-size: 2rem; color: #ffd700; font-weight: bold;">
                            <?php echo $total_uses; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-coins"></i> Bonus Paid
                        </h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            Br<?php echo number_format($total_bonus_paid, 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Create New Code Form -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; margin-bottom: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-plus"></i> Create New Invitation Code
                    </h3>
                    
                    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                        <div>
                            <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Code</label>
                            <input type="text" name="code" required maxlength="20" 
                                   style="width: 100%; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white; text-transform: uppercase;"
                                   placeholder="e.g., NEWBIE2026">
                        </div>
                        
                        <div>
                            <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Max Uses</label>
                            <input type="number" name="max_uses" required min="1" value="100"
                                   style="width: 100%; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white;">
                        </div>
                        
                        <div>
                            <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Bonus Amount (Br)</label>
                            <input type="number" name="bonus_amount" step="0.01" min="0" value="500"
                                   style="width: 100%; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white;">
                        </div>
                        
                        <div>
                            <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Expires At (Optional)</label>
                            <input type="datetime-local" name="expires_at"
                                   style="width: 100%; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white;">
                        </div>
                        
                        <div style="grid-column: 1 / -1;">
                            <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Description</label>
                            <input type="text" name="description" required
                                   style="width: 100%; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white;"
                                   placeholder="e.g., Special bonus for new Ethiopian traders">
                        </div>
                        
                        <div style="grid-column: 1 / -1; text-align: center;">
                            <button type="submit" name="create_code" 
                                    style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 3rem; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-plus"></i> Create Invitation Code
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Codes -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-list"></i> Existing Invitation Codes
                    </h3>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid rgba(34, 139, 34, 0.3);">
                                    <th style="padding: 1rem; text-align: left; color: #ffd700;">Code</th>
                                    <th style="padding: 1rem; text-align: left; color: #ffd700;">Type</th>
                                    <th style="padding: 1rem; text-align: left; color: #ffd700;">Creator</th>
                                    <th style="padding: 1rem; text-align: left; color: #ffd700;">Description</th>
                                    <th style="padding: 1rem; text-align: center; color: #ffd700;">Bonus</th>
                                    <th style="padding: 1rem; text-align: center; color: #ffd700;">Usage</th>
                                    <th style="padding: 1rem; text-align: center; color: #ffd700;">Status</th>
                                    <th style="padding: 1rem; text-align: center; color: #ffd700;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invitation_codes as $code): ?>
                                    <tr style="border-bottom: 1px solid rgba(34, 139, 34, 0.1);">
                                        <td style="padding: 1rem; color: #ffd700; font-weight: bold; font-family: monospace;">
                                            <?php echo htmlspecialchars($code['code']); ?>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <span style="background: rgba(<?php echo $code['created_by'] ? '34, 139, 34' : '255, 215, 0'; ?>, 0.2); color: <?php echo $code['created_by'] ? '#228b22' : '#ffd700'; ?>; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;">
                                                <?php echo $code['code_type']; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; color: rgba(255, 255, 255, 0.9);">
                                            <?php if ($code['creator_name']): ?>
                                                <div><?php echo htmlspecialchars($code['creator_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.6);"><?php echo htmlspecialchars($code['creator_email']); ?></div>
                                            <?php else: ?>
                                                <span style="color: rgba(255, 255, 255, 0.6);">System Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; color: rgba(255, 255, 255, 0.9);">
                                            <?php echo htmlspecialchars($code['description']); ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center; color: #228b22; font-weight: 600;">
                                            Br<?php echo number_format($code['bonus_amount'], 2); ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.9);">
                                            <?php echo $code['current_uses']; ?> / <?php echo $code['max_uses']; ?>
                                            <div style="background: rgba(255, 255, 255, 0.1); height: 4px; border-radius: 2px; margin-top: 0.5rem;">
                                                <div style="background: linear-gradient(90deg, #228b22, #ffd700); height: 100%; width: <?php echo ($code['current_uses'] / $code['max_uses']) * 100; ?>%; border-radius: 2px;"></div>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; text-align: center;">
                                            <span style="background: rgba(<?php echo $code['is_active'] ? '34, 139, 34' : '255, 82, 82'; ?>, 0.2); color: <?php echo $code['is_active'] ? '#228b22' : '#ff5252'; ?>; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;">
                                                <?php echo $code['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; text-align: center;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $code['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" name="toggle_status" 
                                                        style="background: <?php echo $code['is_active'] ? 'rgba(255, 82, 82, 0.2)' : 'rgba(34, 139, 34, 0.2)'; ?>; color: <?php echo $code['is_active'] ? '#ff5252' : '#228b22'; ?>; border: 1px solid <?php echo $code['is_active'] ? 'rgba(255, 82, 82, 0.3)' : 'rgba(34, 139, 34, 0.3)'; ?>; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; font-size: 0.8rem;">
                                                    <?php echo $code['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Concordial Nexus. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>