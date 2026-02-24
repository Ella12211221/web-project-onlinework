<?php
// Script to update site name from "Breakthrough Trading" to "Concordial Nexus"
echo "🔄 Updating site name to Concordial Nexus...\n\n";

// Define the replacements
$replacements = [
    'Breakthrough Online Trading' => 'Concordial Nexus',
    'Breakthrough Trading' => 'Concordial Nexus',
    'breakthrough_trading' => 'concordial_nexus', // Database name
];

// Files to update (excluding database connection strings for now)
$files_to_update = [
    // Main pages
    'index.html',
    'index.php',
    'about.html',
    'services.html',
    'contact.html',
    'pricing.html',
    'portfolio.html',
    'faq.html',
    
    // Auth pages
    'auth/login.php',
    'auth/register.php',
    'auth/logout.php',
    
    // Dashboard pages
    'dashboard/index.php',
    'dashboard/wallet.php',
    'dashboard/investments.php',
    'dashboard/investments-fixed.php',
    'dashboard/payment-methods.php',
    'dashboard/transactions.php',
    'dashboard/profile.php',
    'dashboard/analysis.php',
    'dashboard/orders.php',
    'dashboard/portfolio.php',
    'dashboard/trading.php',
    'dashboard/markets.php',
    
    // Admin pages
    'admin/dashboard.php',
    'admin/users.php',
    'admin/transactions.php',
    'admin/payment-transactions.php',
    'admin/withdrawal-management.php',
    'admin/invitations.php',
    'admin/profile.php',
    'admin/edit-user.php',
    
    // Test files
    'test-withdrawal-system.php',
    'test-session.php',
    'test-navigation.php',
    'test-login-simple.php',
    'test-profile-fix.php',
    
    // Setup files
    'setup-investment-system.php',
    'setup-investment-system-fixed.php',
    'complete-investment-system.php',
    
    // Database files (titles only, not connection strings)
    'database/setup.php',
    'database/setup-payment-transactions.php',
    'database/add-profile-fields.php',
    'database/add-withdrawal-fields.php',
    'database/test-system.php',
    'database/status.php',
    'database/index.php',
    'database/create-admin.php',
    'database/create-database.php',
    'database/create-tables.php',
    'database/simple-setup.php',
    'database/test-connection.php',
    'database/setup-logic.php',
    'database/insert-data.php',
    'database/fix-invitation-table.php',
    'database/update-payment-methods.php',
    
    // Other files
    'fix-withdrawal-errors.php',
    'update-payment-table-simple.php',
    'add-withdrawal-fields-simple.php',
    'clean-profile-data.php',
    'emergency-fix.php',
    'final-error-fix.php',
    'fix-column-error.php',
    'fix-date-error.php',
    'fix-display-code.php',
    'fix-profile-error.php',
    'quick-fix-columns.php',
    'remove-all-errors.php',
    'update-investment-levels.php',
];

$updated_files = 0;
$total_replacements = 0;

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        $file_replacements = 0;
        
        // Apply replacements (but skip database connection strings)
        foreach ($replacements as $search => $replace) {
            // Skip database name replacement in connection strings
            if ($search === 'breakthrough_trading') {
                // Only replace in titles, comments, and non-connection contexts
                $patterns = [
                    '/(<title>.*?)breakthrough_trading(.*?<\/title>)/i',
                    '/(database.*?["\'])breakthrough_trading(["\'])/i',
                    '/(comment.*?)breakthrough_trading/i',
                ];
                
                foreach ($patterns as $pattern) {
                    $new_content = preg_replace($pattern, '$1' . $replace . '$2', $content);
                    if ($new_content !== $content) {
                        $content = $new_content;
                        $file_replacements++;
                    }
                }
            } else {
                // Regular text replacements
                $new_content = str_replace($search, $replace, $content);
                if ($new_content !== $content) {
                    $replacements_made = substr_count($content, $search);
                    $content = $new_content;
                    $file_replacements += $replacements_made;
                }
            }
        }
        
        // Write back if changes were made
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            echo "✅ Updated: $file ($file_replacements replacements)\n";
            $updated_files++;
            $total_replacements += $file_replacements;
        }
    } else {
        echo "⚠️ File not found: $file\n";
    }
}

echo "\n🎉 Site name update complete!\n";
echo "📊 Summary:\n";
echo "   - Files updated: $updated_files\n";
echo "   - Total replacements: $total_replacements\n";
echo "\n📝 Note: Database connection strings kept as 'breakthrough_trading' to maintain compatibility.\n";
echo "   If you want to rename the database, do it separately through phpMyAdmin or MySQL.\n";

// Update markdown files
$md_files = [
    'README.md',
    'TRANSACTION-FEATURES.md',
    'SYSTEM-COMPLETION-SUMMARY.md',
    'INVESTMENT-SYSTEM-COMPLETE.md',
    'PROFILE-SYSTEM-SUMMARY.md',
    'ERROR-FIX-SUMMARY.md',
    'COMMISSION-PAYMENT-APPROVAL-SYSTEM.md',
    'AUTO-INVITATION-SYSTEM.md',
    'INVITATION-SYSTEM.md',
    'ACCESS-CONTROL-IMPLEMENTATION.md',
    'NAVIGATION-SYSTEM-UPDATE.md',
    'UPDATED-PAYMENT-METHODS.md',
    'IMPROVED-INVESTMENT-FLOW.md',
    'COMPLETE-PROFILE-IMPLEMENTATION.md',
    'PROFILE-ERROR-FIX.md',
    'README-DATABASE.md',
];

$md_updated = 0;
foreach ($md_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        foreach ($replacements as $search => $replace) {
            if ($search !== 'breakthrough_trading') { // Skip database name in MD files
                $content = str_replace($search, $replace, $content);
            }
        }
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            echo "✅ Updated markdown: $file\n";
            $md_updated++;
        }
    }
}

echo "\n📄 Markdown files updated: $md_updated\n";
echo "\n🚀 Concordial Nexus is ready!\n";
?>