<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'driver') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$_SESSION['driver_profile_csrf'] ??= bin2hex(random_bytes(32));
$stmt = $pdo->prepare('SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ../auth/logout.php');
    exit();
}
$message = '';
$success = false;
$address = $user['address'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = is_string($_POST['address'] ?? null) ? trim($_POST['address']) : '';
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['driver_profile_csrf'], $_POST['csrf_token'])) {
        $message = 'Your session has expired. Please try again.';
    } elseif ($address === '') {
        $message = 'Please enter your address.';
    } else {
        try {
            $update = $pdo->prepare('UPDATE users SET latitude = CASE WHEN address IS DISTINCT FROM ? THEN NULL ELSE latitude END,
                longitude = CASE WHEN address IS DISTINCT FROM ? THEN NULL ELSE longitude END,
                address = ?, updated_at = NOW() WHERE id = ? AND role = ?');
            $update->execute([$address, $address, $address, $user_id, 'driver']);
            $message = 'Address updated successfully.';
            $success = true;
        } catch (PDOException $e) {
            $message = 'Unable to update your address. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-green-50 text-gray-800">
    <nav class="bg-white shadow-sm p-4">
        <div class="max-w-3xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="text-green-700 font-semibold">Back to Dashboard</a>
            <h1 class="text-xl font-bold text-green-700">My Driver Profile</h1>
        </div>
    </nav>
    <main class="max-w-3xl mx-auto px-4 py-8">
        <?php if ($message !== ''): ?>
            <div role="status" class="mb-6 p-4 rounded-lg <?php echo $success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <section class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">Profile Information</h2>
            <p class="font-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($user['email']); ?></p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['driver_profile_csrf']); ?>">
                <label for="address" class="block text-sm font-medium mb-2">Address</label>
                <textarea id="address" name="address" rows="3" autocomplete="street-address" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-600"><?php echo htmlspecialchars($address); ?></textarea>
                <p class="text-sm text-gray-500 mt-2">Include your street, barangay, city or municipality, and province.</p>
                <button type="submit" class="mt-6 px-6 py-3 bg-green-700 text-white rounded-lg hover:bg-green-800">Save Address</button>
            </form>
        </section>
    </main>
</body>
</html>
