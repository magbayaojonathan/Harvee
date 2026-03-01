<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$message_type = '';

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../auth/logout.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_account'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $name = trim($first_name . ' ' . $last_name);

        $errors = [];
        if ($first_name === '') $errors[] = 'First name is required.';
        if ($last_name === '') $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

        if (empty($errors)) {
            $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $email_check->execute([$email, $user_id]);
            if ($email_check->fetch()) {
                $errors[] = 'Email is already used by another account.';
            }
        }

        if (empty($errors)) {
            $update_stmt = $pdo->prepare("
                UPDATE users
                SET first_name = ?, last_name = ?, name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update_stmt->execute([$first_name, $last_name, $name, $email, $phone, $address, $user_id]);

            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            $message = 'Account settings updated successfully.';
            $message_type = 'success';

            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = implode(' ', $errors);
            $message_type = 'error';
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pass_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $pass_stmt->execute([$hashed_password, $user_id]);

            $message = 'Password changed successfully.';
            $message_type = 'success';
        } else {
            $message = implode(' ', $errors);
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%);
        }
        .card {
            background: #fff;
            border: 1px solid rgba(16, 133, 77, 0.1);
            transition: all 0.25s ease;
        }
        .card:hover {
            border-color: rgba(16, 133, 77, 0.3);
            box-shadow: 0 10px 25px -5px rgba(16, 133, 77, 0.08);
        }
        .gradient-text {
            background: linear-gradient(135deg, #10854d 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">H</span>
                    </div>
                    <span class="text-xl font-bold gradient-text">Harvee Farm</span>
                </a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="text-gray-700 hover:text-[#10854d] font-medium"><i class="fas fa-home mr-1"></i> Dashboard</a>
                    <a href="products/products.php" class="text-gray-700 hover:text-[#10854d] font-medium"><i class="fas fa-box mr-1"></i> Products</a>
                    <a href="orders.php" class="text-gray-700 hover:text-[#10854d] font-medium"><i class="fas fa-shopping-bag mr-1"></i> Orders</a>
                    <a href="settings.php" class="text-[#10854d] font-semibold border-b-2 border-[#10854d] pb-1"><i class="fas fa-cog mr-1"></i> Settings</a>
                </div>
                <a href="../auth/logout.php" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="hero rounded-2xl p-8 mb-8 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Settings</h1>
            <p class="text-white/90 text-lg">Manage your farmer account and security preferences.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="card rounded-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Account Information</h2>
                <form method="POST" action="" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="save_account" class="px-5 py-2.5 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                        Save Changes
                    </button>
                </form>
            </section>

            <section class="card rounded-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Security</h2>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" minlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                    </div>
                    <button type="submit" name="change_password" class="px-5 py-2.5 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                        Update Password
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Account Details</h3>
                    <p class="text-sm text-gray-600">Username: <span class="font-medium"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></span></p>
                    <p class="text-sm text-gray-600">Role: <span class="font-medium capitalize"><?php echo htmlspecialchars($user['role'] ?? 'farmer'); ?></span></p>
                    <p class="text-sm text-gray-600">Member since: <span class="font-medium"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span></p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
