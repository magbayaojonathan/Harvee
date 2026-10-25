<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../shared/profile_photo.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'driver') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$message_type = '';

function driver_build_display_name(array $user): string
{
    $explicit = trim((string)($user['name'] ?? ''));
    if ($explicit !== '') {
        return $explicit;
    }
    $full = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($full !== '') {
        return $full;
    }
    return 'Driver';
}

// Fetch current user
$stmt = $pdo->prepare("SELECT id, first_name, last_name, name, email, phone, address, username, role, created_at, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/logout.php');
    exit();
}

$stats = [
    'total_deliveries' => 0,
    'completed_deliveries' => 0,
    'active_deliveries' => 0,
    'confirmed_deliveries' => 0,
];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE delivery_driver_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_deliveries'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE delivery_driver_id = ? AND order_status = 'delivered'");
    $stmt->execute([$user_id]);
    $stats['completed_deliveries'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE delivery_driver_id = ? AND order_status IN ('processing', 'shipped', 'confirmed')");
    $stmt->execute([$user_id]);
    $stats['active_deliveries'] = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE delivery_driver_id = ? AND order_status = 'confirmed'");
    $stmt->execute([$user_id]);
    $stats['confirmed_deliveries'] = (int)($stmt->fetch()['total'] ?? 0);
} catch (PDOException $e) {
    error_log("Driver profile stats error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_photo'])) {
        $result = harvee_handle_profile_photo_upload(
            $pdo,
            (int)$user_id,
            $_FILES['profile_photo'] ?? [],
            (string)($user['profile_image'] ?? '')
        );
        $message = (string)$result['message'];
        $message_type = $result['ok'] ? 'success' : 'error';

        if (!empty($result['ok']) && !empty($result['path'])) {
            $_SESSION['profile_image'] = (string)$result['path'];
        }

        $stmt = $pdo->prepare("SELECT id, first_name, last_name, name, email, phone, address, username, role, created_at, profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }

    if (isset($_POST['update_profile'])) {
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));

        $errors = [];

        if ($first_name === '') {
            $errors[] = 'First name is required';
        }
        if ($last_name === '') {
            $errors[] = 'Last name is required';
        }
        if ($email === '') {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($errors) && $email !== ($user['email'] ?? '')) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->fetch()) {
                $errors[] = 'Email already exists';
            }
        }

        if (empty($errors)) {
            try {
                $update = $pdo->prepare("
                    UPDATE users
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");
                $update->execute([$first_name, $last_name, $email, $phone, $address, $user_id]);

                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = trim($first_name . ' ' . $last_name);

                $message = 'Profile updated successfully!';
                $message_type = 'success';

                $stmt = $pdo->prepare("SELECT id, first_name, last_name, name, email, phone, address, username, role, created_at, profile_image FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Driver profile update error: " . $e->getMessage());
                $message = 'Error updating profile. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'error';
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = (string)($_POST['current_password'] ?? '');
        $new_password = (string)($_POST['new_password'] ?? '');
        $confirm_password = (string)($_POST['confirm_password'] ?? '');

        $errors = [];

        $pwStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pwStmt->execute([$user_id]);
        $pwRow = $pwStmt->fetch();

        if (!$pwRow || !password_verify($current_password, (string)($pwRow['password'] ?? ''))) {
            $errors[] = 'Current password is incorrect';
        }
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }

        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                $message = 'Password changed successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                error_log("Driver password change error: " . $e->getMessage());
                $message = 'Error changing password. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'error';
        }
    }
}

$driver_name = driver_build_display_name($user);
$driver_initial = strtoupper(substr(trim($driver_name), 0, 1));
$driver_first = trim((string)($user['first_name'] ?? ''));
if ($driver_first === '') {
    $driver_first = strtok($driver_name, ' ') ?: $driver_name;
}

$profile_photo_url = harvee_profile_photo_url((string)($user['profile_image'] ?? ''), '../');

$member_since = '';
if (!empty($user['created_at'])) {
    $member_since = date('F Y', strtotime((string)$user['created_at']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .profile-sidebar {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 100%);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="text-gray-700">Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-[#10854d]">My Profile</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($message !== ''): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <div class="flex items-center">
                    <?php if ($message_type === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    <div><?php echo $message; ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Profile Sidebar -->
            <div class="lg:w-1/3">
                <div class="profile-sidebar rounded-xl shadow-lg overflow-hidden sticky top-20">
	                    <!-- Profile Header -->
	                    <div class="p-6 text-center text-white">
	                        <div class="w-24 h-24 bg-white rounded-full mx-auto mb-4 flex items-center justify-center overflow-hidden">
	                            <?php if ($profile_photo_url !== ''): ?>
	                                <img src="<?php echo htmlspecialchars($profile_photo_url); ?>" alt="Profile photo" class="w-full h-full object-cover">
	                            <?php else: ?>
	                                <span class="text-3xl font-bold text-[#10854d]">
	                                    <?php echo htmlspecialchars($driver_initial); ?>
	                                </span>
	                            <?php endif; ?>
	                        </div>
	                        <form method="POST" action="" enctype="multipart/form-data" class="mb-4">
	                            <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-white file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-white/20 file:text-white hover:file:bg-white/30" required>
	                            <button type="submit" name="update_photo" class="mt-2 w-full px-3 py-2 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-lg transition-colors">
	                                Update Photo
	                            </button>
	                        </form>
	                        <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($driver_name); ?></h2>
	                        <p class="text-green-100 text-sm mb-4">
	                            <?php if ($member_since !== ''): ?>
	                                Member since <?php echo htmlspecialchars($member_since); ?>
                            <?php else: ?>
                                Driver Account
                            <?php endif; ?>
                        </p>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-3 pt-4 border-t border-green-600">
                            <div>
                                <div class="text-2xl font-bold"><?php echo (int)$stats['total_deliveries']; ?></div>
                                <div class="text-xs text-green-100">Deliveries</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold"><?php echo (int)$stats['completed_deliveries']; ?></div>
                                <div class="text-xs text-green-100">Delivered</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold"><?php echo (int)$stats['active_deliveries']; ?></div>
                                <div class="text-xs text-green-100">Active</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white p-4">
                        <h3 class="font-semibold text-gray-700 mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="dashboard.php#available" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h12M9 12h12M9 19h12M5 5h.01M5 12h.01M5 19h.01"/>
                                </svg>
                                <span class="text-gray-700">Available Deliveries</span>
                            </a>
                            <a href="dashboard.php#active" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17l6-6-6-6"/>
                                </svg>
                                <span class="text-gray-700">My Active Deliveries</span>
                            </a>
                            <a href="dashboard.php#completed" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-700">Completed</span>
                            </a>
                            <a href="../auth/logout.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-red-600">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="lg:w-2/3 space-y-6">
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Edit Profile Information</h2>
                    </div>

                    <form method="POST" action="" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text"
                                       name="first_name"
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text"
                                       name="last_name"
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email"
                                       name="email"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel"
                                       name="phone"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                                <textarea name="address"
                                          rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                    name="update_profile"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Change Password</h2>
                    </div>

                    <form method="POST" action="" class="p-6" onsubmit="return validatePasswordForm()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password"
                                       name="current_password"
                                       id="current_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password"
                                       name="new_password"
                                       id="new_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password"
                                       name="confirm_password"
                                       id="confirm_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>

                            <!-- Password Strength Meter -->
                            <div class="hidden" id="password-strength">
                                <div class="flex items-center mt-2">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-red-500" id="strength-bar" style="width: 0%"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-600" id="strength-text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                    name="change_password"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Statistics -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Account Statistics</h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="stat-card bg-blue-50 p-4 rounded-lg">
                                <div class="text-blue-600 text-2xl font-bold"><?php echo (int)$stats['total_deliveries']; ?></div>
                                <div class="text-sm text-gray-600">Total Deliveries</div>
                            </div>

                            <div class="stat-card bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 text-2xl font-bold"><?php echo (int)$stats['completed_deliveries']; ?></div>
                                <div class="text-sm text-gray-600">Delivered</div>
                            </div>

                            <div class="stat-card bg-yellow-50 p-4 rounded-lg">
                                <div class="text-yellow-600 text-2xl font-bold"><?php echo (int)$stats['active_deliveries']; ?></div>
                                <div class="text-sm text-gray-600">Active</div>
                            </div>

                            <div class="stat-card bg-purple-50 p-4 rounded-lg">
                                <div class="text-purple-600 text-2xl font-bold"><?php echo (int)$stats['confirmed_deliveries']; ?></div>
                                <div class="text-sm text-gray-600">Confirmed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function validatePasswordForm() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (newPass.length < 6) {
                alert('New password must be at least 6 characters long');
                return false;
            }

            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return false;
            }

            return true;
        }

        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const strengthDiv = document.getElementById('password-strength');

            if (password.length > 0) {
                strengthDiv.classList.remove('hidden');

                let strength = 0;
                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]+/)) strength += 25;
                if (password.match(/[A-Z]+/)) strength += 25;
                if (password.match(/[0-9]+/)) strength += 25;

                strengthBar.style.width = strength + '%';

                if (strength < 50) {
                    strengthBar.className = 'h-full bg-red-500';
                    strengthText.textContent = 'Weak';
                } else if (strength < 75) {
                    strengthBar.className = 'h-full bg-yellow-500';
                    strengthText.textContent = 'Medium';
                } else {
                    strengthBar.className = 'h-full bg-green-500';
                    strengthText.textContent = 'Strong';
                }
            } else {
                strengthDiv.classList.add('hidden');
            }
        });

        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
