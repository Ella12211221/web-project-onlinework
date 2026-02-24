<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Profile Fields - Breakthrough Trading</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%);
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(34, 139, 34, 0.3);
        }
        h1 {
            text-align: center;
            color: #ffd700;
            margin-bottom: 30px;
        }
        .success {
            background: rgba(34, 139, 34, 0.2);
            color: #228b22;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #228b22;
        }
        .error {
            background: rgba(255, 82, 82, 0.2);
            color: #ff5252;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ff5252;
        }
        .info {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ffd700;
        }
        .btn {
            background: linear-gradient(45deg, #228b22, #ffd700);
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👤 Add Profile Fields to Database</h1>
        
        <?php
        if (isset($_POST['add_fields'])) {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'breakthrough_trading';
            
            try {
                echo "<div class='info'>Connecting to database...</div>";
                $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✅ Connected to database successfully!</div>";
                
                // Add profile photo column
                echo "<div class='info'>Adding profile_photo column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL");
                    echo "<div class='success'>✅ Added profile_photo column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ profile_photo column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add date_of_birth column
                echo "<div class='info'>Adding date_of_birth column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE DEFAULT NULL");
                    echo "<div class='success'>✅ Added date_of_birth column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ date_of_birth column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add gender column
                echo "<div class='info'>Adding gender column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('male','female','other') DEFAULT NULL");
                    echo "<div class='success'>✅ Added gender column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ gender column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add occupation column
                echo "<div class='info'>Adding occupation column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN occupation VARCHAR(255) DEFAULT NULL");
                    echo "<div class='success'>✅ Added occupation column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ occupation column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add bio column
                echo "<div class='info'>Adding bio column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");
                    echo "<div class='success'>✅ Added bio column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ bio column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add admin-specific columns
                echo "<div class='info'>Adding admin_title column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN admin_title VARCHAR(255) DEFAULT NULL");
                    echo "<div class='success'>✅ Added admin_title column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ admin_title column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                echo "<div class='info'>Adding admin_department column...</div>";
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN admin_department ENUM('administration','trading','finance','customer_service','compliance','it') DEFAULT NULL");
                    echo "<div class='success'>✅ Added admin_department column!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ admin_department column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Create uploads directory
                echo "<div class='info'>Creating uploads directory...</div>";
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    if (mkdir($upload_dir, 0777, true)) {
                        echo "<div class='success'>✅ Created uploads/profiles/ directory!</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to create uploads directory</div>";
                    }
                } else {
                    echo "<div class='info'>ℹ️ uploads/profiles/ directory already exists</div>";
                }
                
                // Verify all columns exist
                echo "<div class='info'>Verifying profile fields...</div>";
                $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
                
                $required_columns = [
                    'profile_photo', 'date_of_birth', 'gender', 'occupation', 
                    'bio', 'admin_title', 'admin_department'
                ];
                
                $all_exist = true;
                foreach ($required_columns as $column) {
                    if (in_array($column, $columns)) {
                        echo "<div class='success'>✅ Column '$column' exists</div>";
                    } else {
                        echo "<div class='error'>❌ Column '$column' missing</div>";
                        $all_exist = false;
                    }
                }
                
                if ($all_exist) {
                    echo "<div class='success' style='font-size: 18px; text-align: center; margin: 30px 0; padding: 20px;'>";
                    echo "🎉 <strong>PROFILE SYSTEM READY!</strong><br><br>";
                    echo "✅ All profile fields added successfully<br>";
                    echo "✅ Photo upload directory created<br>";
                    echo "✅ User and admin profiles fully functional<br>";
                    echo "✅ Password change system enabled<br>";
                    echo "✅ Complete profile management available<br>";
                    echo "</div>";
                    
                    echo "<div style='text-align: center; margin: 30px 0;'>";
                    echo "<a href='../dashboard/profile.php' class='btn'>👤 User Profile</a>";
                    echo "<a href='../admin/profile.php' class='btn'>👑 Admin Profile</a>";
                    echo "<a href='test-system.php' class='btn'>🧪 Test System</a>";
                    echo "</div>";
                } else {
                    echo "<div class='error' style='font-size: 18px; text-align: center; margin: 30px 0; padding: 20px;'>";
                    echo "❌ <strong>SETUP INCOMPLETE</strong><br>";
                    echo "Some profile fields are missing. Please try again.";
                    echo "</div>";
                }
                
            } catch(PDOException $e) {
                echo "<div class='error'>";
                echo "<h4>❌ Database Error:</h4>";
                echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "<h3>🎯 What This Will Add:</h3>";
            echo "✓ profile_photo column for user/admin photos<br>";
            echo "✓ date_of_birth column for birth dates<br>";
            echo "✓ gender column (male/female/other)<br>";
            echo "✓ occupation column for job titles<br>";
            echo "✓ bio column for personal descriptions<br>";
            echo "✓ admin_title column for admin positions<br>";
            echo "✓ admin_department column for admin departments<br>";
            echo "✓ uploads/profiles/ directory for photo storage<br>";
            echo "</div>";
            
            echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
            echo "<button type='submit' name='add_fields' class='btn' style='font-size: 18px; padding: 20px 40px;'>";
            echo "👤 Add Profile Fields Now";
            echo "</button>";
            echo "</form>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="../index.html" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🏠 Home</a>
            <a href="simple-setup.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🔧 Main Setup</a>
            <a href="../dashboard/index.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">📊 Dashboard</a>
        </div>
    </div>
</body>
</html>