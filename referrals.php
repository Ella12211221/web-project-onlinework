<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Generate unique referral code if not exists
    if (empty($user['referral_code'])) {
        $referral_code = strtoupper(substr(md5($user['id'] . $user['email'] . time()), 0, 8));
        $update = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
        $update->execute([$referral_code, $user['id']]);
        $user['referral_code'] = $referral_code;
    }
    
    // Get referral statistics
    $referral_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_referrals,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_referrals,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_referrals
        FROM users 
        WHERE referred_by = ?
    ");
    $referral_stats->execute([$user['id']]);
    $stats = $referral_stats->fetch();
    
    // Get commission earnings
    $commission_total = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_commission
        FROM commissions 
        WHERE user_id = ? AND status = 'paid'
    ");
    $commission_total->execute([$user['id']]);
    $commission_data = $commission_total->fetch();
    
    $pending_commission = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as pending_commission
        FROM commissions 
        WHERE user_id = ? AND status = 'pending'
    ");
    $pending_commission->execute([$user['id']]);
    $pending_data = $pending_commission->fetch();
    
    // Get referral list
    $referrals = $pdo->prepare("
        SELECT id, full_name, email, status, account_balance, created_at
        FROM users 
        WHERE referred_by = ?
        ORDER BY created_at DESC
    ");
    $referrals->execute([$user['id']]);
    $referral_list = $referrals->fetchAll();
    
    // Get commission history
    $commission_history = $pdo->prepare("
        SELECT c.*, u.full_name as from_user
        FROM commissions c
        LEFT JOIN users u ON c.from_user_id = u.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 20
    ");
    $commission_history->execute([$user['id']]);
    $commissions = $commission_history->fetchAll();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$referral_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/register.php?ref=" . $user['referral_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        
        .referral-link-section { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center; }
        .referral-link-section h2 { margin-bottom: 20px; font-size: 1.8rem; }
        .referral-code { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin: 20px 0; font-size: 2rem; font-weight: bold; letter-spacing: 3px; }
        .referral-link-box { background: white; color: #333; padding: 15px; border-radius: 10px; margin: 20px 0; display: flex; align-items: center; gap: 10px; }
        .referral-link-box input { flex: 1; border: none; font-size: 1rem; padding: 10px; background: transparent; }
        .copy-btn { background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .copy-btn:hover { background: #218838; transform: translateY(-2px); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 5px solid #4a90e2; }
        .stat-card.green { border-left-color: #28a745; }
        .stat-card.orange { border-left-color: #f39c12; }
        .stat-card.purple { border-left-color: #9b59b6; }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #333; margin-bottom: 5px; }
        .stat-card .subtitle { color: #999; font-size: 0.85rem; }
        
        .section { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .section h2 { color: #333; margin-bottom: 20px; font-size: 1.5rem; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status.active { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.suspended { background: #f8d7da; color: #721c24; }
        .status.paid { background: #d4edda; color: #155724; }
        
        .btn-back { background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 20px; font-weight: 600; }
        .btn-back:hover { background: #5a6268; }
        
        .share-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .share-btn { padding: 12px 24px; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .share-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .share-btn.whatsapp { background: #25D366; }
        .share-btn.telegram { background: #0088cc; }
        .share-btn.facebook { background: #1877f2; }
        .share-btn.twitter { background: #1DA1F2; }
        
        .commission-type { font-size: 0.85rem; color: #666; font-weight: 600; }
        .amount { font-weight: bold; color: #28a745; font-size: 1.1rem; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header h1 { font-size: 2rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .referral-link-box { flex-direction: column; }
            .share-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌐 My Referral Network</h1>
            <p>Build your network and earn commissions</p>
        </div>
        
        <a href="index.php" class="btn-back">← Back to Dashboard</a>
        
        <!-- Referral Link Section -->
        <div class="referral-link-section">
            <h2>📢 Your Unique Referral Link</h2>
            <p style="margin-bottom: 20px;">Share this link to invite people to join under you</p>
            
            <div class="referral-code">
                <?php echo htmlspecialchars($user['referral_code']); ?>
            </div>
            
            <div class="referral-link-box">
                <input type="text" id="referralLink" value="<?php echo htmlspecialchars($referral_link); ?>" readonly>
                <button class="copy-btn" onclick="copyReferralLink()">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>
            
            <div class="share-buttons">
                <a href="https://wa.me/?text=Join%20Concordial%20Nexus%20and%20start%20earning!%20<?php echo urlencode($referral_link); ?>" target="_blank" class="share-btn whatsapp">
                    <i class="fab fa-whatsapp"></i> Share on WhatsApp
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($referral_link); ?>&text=Join%20Concordial%20Nexus" target="_blank" class="share-btn telegram">
                    <i class="fab fa-telegram"></i> Share on Telegram
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>" target="_blank" class="share-btn facebook">
                    <i class="fab fa-facebook"></i> Share on Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($referral_link); ?>&text=Join%20Concordial%20Nexus" target="_blank" class="share-btn twitter">
                    <i class="fab fa-twitter"></i> Share on Twitter
                </a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Referrals</h3>
                <div class="number"><?php echo $stats['total_referrals'] ?? 0; ?></div>
                <div class="subtitle">People you invited</div>
            </div>
            <div class="stat-card green">
                <h3>Active Referrals</h3>
                <div class="number"><?php echo $stats['active_referrals'] ?? 0; ?></div>
                <div class="subtitle">Active members</div>
            </div>
            <div class="stat-card orange">
                <h3>Total Commissions</h3>
                <div class="number">Br<?php echo number_format($commission_data['total_commission'] ?? 0, 2); ?></div>
                <div class="subtitle">Earned commissions</div>
            </div>
            <div class="stat-card purple">
                <h3>Pending Commissions</h3>
                <div class="number">Br<?php echo number_format($pending_data['pending_commission'] ?? 0, 2); ?></div>
                <div class="subtitle">Awaiting approval</div>
            </div>
        </div>
        
        <!-- Referral List -->
        <div class="section">
            <h2>👥 My Referrals</h2>
            <?php if (empty($referral_list)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>You haven't referred anyone yet. Share your referral link to start building your network!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referral_list as $referral): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($referral['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                <td><span class="status <?php echo $referral['status']; ?>"><?php echo ucfirst($referral['status']); ?></span></td>
                                <td><strong>Br<?php echo number_format($referral['account_balance'], 2); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Commission History -->
        <div class="section">
            <h2>💰 Commission History</h2>
            <?php if (empty($commissions)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-money-bill-wave" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No commissions earned yet. Commissions are earned when your referrals make investments.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commissions as $commission): ?>
                            <tr>
                                <td><?php echo date('M j, Y H:i', strtotime($commission['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($commission['from_user'] ?? 'N/A'); ?></strong></td>
                                <td><span class="commission-type"><?php echo ucfirst(str_replace('_', ' ', $commission['commission_type'] ?? 'referral')); ?></span></td>
                                <td><span class="amount">Br<?php echo number_format($commission['amount'], 2); ?></span></td>
                                <td><span class="status <?php echo $commission['status']; ?>"><?php echo ucfirst($commission['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- How It Works -->
        <div class="section" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
            <h2>📖 How Referral Commissions Work</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: white; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #4a90e2; margin-bottom: 10px;">1️⃣ Share Your Link</h3>
                    <p style="color: #666;">Share your unique referral link with friends, family, and on social media.</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #28a745; margin-bottom: 10px;">2️⃣ They Register</h3>
                    <p style="color: #666;">When someone registers using your link, they become your referral.</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #f39c12; margin-bottom: 10px;">3️⃣ They Invest</h3>
                    <p style="color: #666;">When your referrals make investments, you earn commissions automatically.</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #9b59b6; margin-bottom: 10px;">4️⃣ You Earn</h3>
                    <p style="color: #666;">Commissions are added to your account balance and can be withdrawn.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyReferralLink() {
            const linkInput = document.getElementById('referralLink');
            const copyBtn = document.querySelector('.copy-btn');
            
            // Select the text
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // For mobile devices
            
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(linkInput.value)
                    .then(function() {
                        // Success feedback
                        const originalText = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        copyBtn.style.background = '#28a745';
                        
                        setTimeout(function() {
                            copyBtn.innerHTML = originalText;
                            copyBtn.style.background = '';
                        }, 2000);
                    })
                    .catch(function(err) {
                        // Fallback to old method
                        fallbackCopy(linkInput, copyBtn);
                    });
            } else {
                // Use fallback for older browsers
                fallbackCopy(linkInput, copyBtn);
            }
        }
        
        function fallbackCopy(linkInput, copyBtn) {
            try {
                // Old method using execCommand
                linkInput.select();
                const successful = document.execCommand('copy');
                
                if (successful) {
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    copyBtn.style.background = '#28a745';
                    
                    setTimeout(function() {
                        copyBtn.innerHTML = originalText;
                        copyBtn.style.background = '';
                    }, 2000);
                } else {
                    alert('Copy failed. Please copy the link manually.');
                }
            } catch (err) {
                alert('Copy not supported. Please copy the link manually: ' + linkInput.value);
            }
        }
    </script>
</body>
</html>
