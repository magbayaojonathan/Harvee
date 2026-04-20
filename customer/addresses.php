<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$user = null;
$cart_count = 0;
$address_table_exists = false;
$address_has_coordinates = false;
$addresses = [];

function fetchCustomerAddresses(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM addresses
        WHERE user_id = ?
        ORDER BY is_default DESC, created_at DESC, id DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll() ?: [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $cartStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cart_count = (int)$cartStmt->fetchColumn();

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'addresses'");
    $address_table_exists = (bool)$tableCheck->fetchColumn();
    if ($address_table_exists) {
        $latCheck = $pdo->query("SHOW COLUMNS FROM addresses LIKE 'latitude'");
        $lngCheck = $pdo->query("SHOW COLUMNS FROM addresses LIKE 'longitude'");
        $address_has_coordinates = (bool)$latCheck->fetch() && (bool)$lngCheck->fetch();
    }
} catch (PDOException $e) {
    $message = "Unable to load address details right now.";
    $message_type = "error";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $address_table_exists) {
    if (isset($_POST['save_address'])) {
        $address_label = trim($_POST['address_label'] ?? 'Home');
        $recipient_name = trim($_POST['recipient_name'] ?? ($_SESSION['name'] ?? ''));
        $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));
        $address_line1 = trim($_POST['address_line1'] ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
        $latitude = trim((string)($_POST['latitude'] ?? ''));
        $longitude = trim((string)($_POST['longitude'] ?? ''));
        $set_default = isset($_POST['is_default']);

        $errors = [];

        if ($recipient_name === '') $errors[] = "Recipient name is required";
        if ($phone === '') $errors[] = "Phone number is required";
        if ($address_line1 === '') $errors[] = "Address line is required";
        if ($city === '') $errors[] = "City is required";
        if ($province === '') $errors[] = "Province is required";
        if ($latitude !== '' && !is_numeric($latitude)) $errors[] = "Invalid latitude value";
        if ($longitude !== '' && !is_numeric($longitude)) $errors[] = "Invalid longitude value";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($set_default) {
                    $resetStmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $resetStmt->execute([$user_id]);
                }

                if ($address_has_coordinates) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO addresses (
                            user_id, address_type, recipient_name, phone, address_line1, address_line2,
                            barangay, city, province, postal_code, latitude, longitude,
                            delivery_instructions, is_default
                        ) VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $user_id,
                        $address_label !== '' ? $address_label . ' - ' . $recipient_name : $recipient_name,
                        $phone,
                        $address_line1,
                        $address_line2 !== '' ? $address_line2 : null,
                        $barangay !== '' ? $barangay : null,
                        $city,
                        $province,
                        $postal_code !== '' ? $postal_code : null,
                        $latitude !== '' ? (float)$latitude : null,
                        $longitude !== '' ? (float)$longitude : null,
                        $delivery_instructions !== '' ? $delivery_instructions : null,
                        $set_default ? 1 : 0
                    ]);
                } else {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO addresses (
                            user_id, address_type, recipient_name, phone, address_line1, address_line2,
                            barangay, city, province, postal_code, delivery_instructions, is_default
                        ) VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $user_id,
                        $address_label !== '' ? $address_label . ' - ' . $recipient_name : $recipient_name,
                        $phone,
                        $address_line1,
                        $address_line2 !== '' ? $address_line2 : null,
                        $barangay !== '' ? $barangay : null,
                        $city,
                        $province,
                        $postal_code !== '' ? $postal_code : null,
                        $delivery_instructions !== '' ? $delivery_instructions : null,
                        $set_default ? 1 : 0
                    ]);
                }

                if ($set_default || empty(fetchCustomerAddresses($pdo, $user_id))) {
                    $summaryParts = array_filter([
                        $address_line1,
                        $address_line2,
                        $barangay,
                        $city,
                        $province,
                        $postal_code
                    ]);
                    $summaryAddress = implode(', ', $summaryParts);

                    $userUpdateStmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
                    $userUpdateStmt->execute([$summaryAddress, $user_id]);
                }

                $pdo->commit();
                $message = "New address added successfully.";
                $message_type = "success";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = "Unable to save the new address right now.";
                $message_type = "error";
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = "error";
        }
    }

    if (isset($_POST['set_default']) && isset($_POST['address_id'])) {
        $address_id = (int)$_POST['address_id'];

        try {
            $pdo->beginTransaction();

            $resetStmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$user_id]);

            $defaultStmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $defaultStmt->execute([$address_id, $user_id]);

            $selectedStmt = $pdo->prepare("
                SELECT address_line1, address_line2, barangay, city, province, postal_code
                FROM addresses
                WHERE id = ? AND user_id = ?
            ");
            $selectedStmt->execute([$address_id, $user_id]);
            $selectedAddress = $selectedStmt->fetch();

            if ($selectedAddress) {
                $summaryParts = array_filter([
                    $selectedAddress['address_line1'] ?? '',
                    $selectedAddress['address_line2'] ?? '',
                    $selectedAddress['barangay'] ?? '',
                    $selectedAddress['city'] ?? '',
                    $selectedAddress['province'] ?? '',
                    $selectedAddress['postal_code'] ?? ''
                ]);
                $summaryAddress = implode(', ', $summaryParts);

                $userUpdateStmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
                $userUpdateStmt->execute([$summaryAddress, $user_id]);
            }

            $pdo->commit();
            $message = "Default address updated.";
            $message_type = "success";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Unable to update the default address.";
            $message_type = "error";
        }
    }
}

if ($address_table_exists) {
    try {
        $addresses = fetchCustomerAddresses($pdo, $user_id);
    } catch (PDOException $e) {
        $addresses = [];
    }
}

$first_name = $_SESSION['first_name'] ?? 'Customer';
$current_address = trim((string)($user['address'] ?? ''));
$primary_address = null;
if (!empty($addresses)) {
    foreach ($addresses as $addressItem) {
        if ((int)($addressItem['is_default'] ?? 0) === 1) {
            $primary_address = $addressItem;
            break;
        }
    }
    if ($primary_address === null) {
        $primary_address = $addresses[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%); }
        .main-nav a { transition: all 0.3s ease; }
        .main-nav a:hover { color: #10854d; }
        .main-nav a.active { color: #10854d; font-weight: 600; border-bottom: 2px solid #10854d; }
        .panel-card { border: 1px solid rgba(16, 133, 77, 0.1); box-shadow: 0 18px 30px -24px rgba(16, 133, 77, 0.35); }
        .address-card { border: 1px solid rgba(16, 133, 77, 0.12); transition: all 0.25s ease; }
        .address-card:hover { border-color: rgba(16, 133, 77, 0.35); transform: translateY(-1px); }
        .add-address-button {
            background: linear-gradient(135deg, #10854d 0%, #0f766e 100%);
            box-shadow: 0 18px 32px -22px rgba(16, 133, 77, 0.85);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .add-address-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 38px -24px rgba(16, 133, 77, 0.95);
            filter: saturate(1.05);
        }
        .map-container {
            border: 1px solid #d1d5db;
            border-radius: 20px;
            overflow: hidden;
            height: 280px;
            background: #f3f4f6;
        }
        .map-help {
            font-size: 0.84rem;
            color: #4b5563;
            margin: 8px 0 10px 2px;
        }
        .map-search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .map-search-input {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 0.95rem;
        }
        .map-search-input:focus {
            border-color: #10854d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 133, 77, 0.12);
        }
        .map-search-btn {
            border: 0;
            border-radius: 16px;
            background: #10854d;
            color: #fff;
            font-weight: 700;
            padding: 12px 16px;
            cursor: pointer;
        }
        .map-search-btn:hover {
            background: #0d6e40;
        }
        .map-search-results {
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            max-height: 180px;
            overflow-y: auto;
        }
        .map-search-item {
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            font-size: 0.9rem;
            color: #374151;
            border: 0;
            background: #fff;
            cursor: pointer;
        }
        .map-search-item:hover {
            background: #f3f4f6;
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-8 h-8 object-contain">
                    <span class="text-xl font-bold text-[#10854d]">Harvee</span>
                </a>

                <div class="hidden md:flex items-center space-x-8 main-nav">
                    <a href="dashboard.php" class="text-gray-700 font-medium">Home</a>
                    <a href="browse.php" class="text-gray-700 font-medium">Shopping</a>
                    <a href="cart.php" class="text-gray-700 font-medium relative">
                        Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-2 -right-4 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="orders.php" class="text-gray-700 font-medium">My Orders</a>
                    <a href="addresses.php" class="text-gray-700 font-medium active">Addresses</a>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="profile.php" class="hidden md:inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="../auth/logout.php" class="px-4 py-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-[1.15fr,0.85fr] gap-8">
            <section class="panel-card bg-white rounded-3xl p-8">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#10854d]">Delivery setup</p>
                        <h1 class="text-3xl font-bold text-gray-900 mt-2">Manage your addresses</h1>
                        <p class="text-gray-500 mt-2">Customers can save multiple delivery addresses here and choose which one should be the default.</p>
                    </div>
                    <button type="button" id="toggleAddressForm" class="add-address-button inline-flex items-center gap-3 px-5 py-3.5 rounded-2xl text-white font-semibold">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <i class="fas fa-location-dot text-sm"></i>
                        </span>
                        <span class="flex flex-col items-start leading-tight">
                            <span>Add New Address</span>
                        </span>
                    </button>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="mb-6 rounded-2xl border px-4 py-3 <?php echo $message_type === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$address_table_exists): ?>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
                        The multiple-address table is not available in this database yet. Once the `addresses` table is migrated, this page can store several delivery addresses.
                    </div>
                <?php else: ?>
                    <div class="space-y-4 mb-6">
                        <?php if (!empty($addresses)): ?>
                            <?php foreach ($addresses as $address): ?>
                                <?php
                                    $addressLines = array_filter([
                                        $address['address_line1'] ?? '',
                                        $address['address_line2'] ?? '',
                                        $address['barangay'] ?? '',
                                        $address['city'] ?? '',
                                        $address['province'] ?? '',
                                        $address['postal_code'] ?? ''
                                    ]);
                                ?>
                                <div class="address-card rounded-3xl p-5 bg-gray-50">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-[#10854d] text-sm font-semibold">
                                                    <?php echo htmlspecialchars($address['address_type'] ?? 'shipping'); ?>
                                                </span>
                                                <?php if ((int)($address['is_default'] ?? 0) === 1): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm font-semibold">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($address['recipient_name'] ?? 'Recipient'); ?></h2>
                                            <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($address['phone'] ?? ''); ?></p>
                                            <p class="text-gray-700 leading-7 mt-3"><?php echo htmlspecialchars(implode(', ', $addressLines)); ?></p>
                                            <?php if (!empty($address['delivery_instructions'])): ?>
                                                <p class="text-sm text-gray-500 mt-3">Note: <?php echo htmlspecialchars($address['delivery_instructions']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ((int)($address['is_default'] ?? 0) !== 1): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="address_id" value="<?php echo (int)$address['id']; ?>">
                                                <button type="submit" name="set_default" class="px-4 py-2 rounded-full border border-[#10854d] text-[#10854d] font-semibold hover:bg-green-50 transition-colors">
                                                    Set as Default
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 text-amber-800">
                                No delivery addresses saved yet. Add your first one now.
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="" id="addressFormPanel" class="space-y-5 border-t border-gray-200 pt-6 <?php echo !empty($addresses) ? 'hidden' : ''; ?>">
                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Address label</label>
                                <select name="address_label" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]">
                                    <option value="Home">Home</option>
                                    <option value="Work">Work</option>
                                    <option value="Family">Family</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Recipient name</label>
                                <input type="text" name="recipient_name" value="<?php echo htmlspecialchars($_POST['recipient_name'] ?? ($_SESSION['name'] ?? '')); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Full recipient name">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="09XXXXXXXXX">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Postal code</label>
                                <input type="text" name="postal_code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Postal code">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Address line 1</label>
                            <input type="text" name="address_line1" value="<?php echo htmlspecialchars($_POST['address_line1'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="House no., street, subdivision">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pin location on map</label>
                            <p class="map-help">Search the place, click on the map, or drag the pin to your exact delivery location.</p>
                            <div class="map-search-bar">
                                <input type="text" id="mapSearchInput" class="map-search-input" placeholder="Search barangay, street, city">
                                <button type="button" id="mapSearchBtn" class="map-search-btn">Search</button>
                            </div>
                            <div id="mapSearchResults" class="map-search-results hidden"></div>
                            <div id="addressMap" class="map-container"></div>
                            <input type="hidden" name="latitude" id="latitudeInput" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                            <input type="hidden" name="longitude" id="longitudeInput" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Address line 2</label>
                            <input type="text" name="address_line2" value="<?php echo htmlspecialchars($_POST['address_line2'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Building, floor, unit, optional">
                        </div>

                        <div class="grid md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Barangay</label>
                                <input type="text" name="barangay" value="<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Barangay">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">City / Municipality</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="City or municipality">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Province</label>
                                <input type="text" name="province" value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Province">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery instructions</label>
                            <textarea name="delivery_instructions" rows="3" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-[#10854d]" placeholder="Near chapel, look for green gate, call on arrival"><?php echo htmlspecialchars($_POST['delivery_instructions'] ?? ''); ?></textarea>
                        </div>

                        <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="is_default" class="w-4 h-4 rounded text-[#10854d] focus:ring-[#10854d]" <?php echo empty($addresses) ? 'checked' : ''; ?>>
                            Set as default delivery address
                        </label>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="submit" name="save_address" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-[#10854d] text-white font-semibold hover:bg-[#0d6e40] transition-colors">
                                <i class="fas fa-save mr-2"></i> Save Address
                            </button>
                            <button type="button" id="cancelAddressForm" class="inline-flex items-center justify-center px-6 py-3 rounded-full border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-colors <?php echo empty($addresses) ? 'hidden' : ''; ?>">
                                Cancel
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <aside class="space-y-6">
                <div class="panel-card bg-white rounded-3xl p-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#10854d]">Current default</p>
                    <h2 class="text-xl font-bold text-gray-900 mt-2 mb-4"><?php echo htmlspecialchars($first_name); ?>'s active address</h2>
                    <?php if ($primary_address !== null): ?>
                        <?php
                            $primaryLines = array_filter([
                                $primary_address['address_line1'] ?? '',
                                $primary_address['address_line2'] ?? '',
                                $primary_address['barangay'] ?? '',
                                $primary_address['city'] ?? '',
                                $primary_address['province'] ?? '',
                                $primary_address['postal_code'] ?? ''
                            ]);
                        ?>
                        <div class="rounded-2xl bg-gray-50 border border-gray-200 p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-[#10854d] text-sm font-semibold">Default address</span>
                                <i class="fas fa-house text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($primary_address['recipient_name'] ?? ''); ?></p>
                            <p class="text-gray-700 leading-7"><?php echo htmlspecialchars(implode(', ', $primaryLines)); ?></p>
                        </div>
                    <?php elseif ($current_address !== ''): ?>
                        <div class="rounded-2xl bg-gray-50 border border-gray-200 p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-[#10854d] text-sm font-semibold">Legacy address</span>
                                <i class="fas fa-house text-gray-400"></i>
                            </div>
                            <p class="text-gray-700 leading-7"><?php echo nl2br(htmlspecialchars($current_address)); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 text-amber-800">
                            No default address saved yet. Add one now so checkout and delivery become smoother later.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel-card bg-white rounded-3xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Why this is better</h2>
                    <ul class="space-y-3 text-gray-600">
                        <li><i class="fas fa-check text-[#10854d] mr-2"></i>Customers can now save more than one delivery address.</li>
                        <li><i class="fas fa-check text-[#10854d] mr-2"></i>One address can be marked as the default for checkout.</li>
                        <li><i class="fas fa-check text-[#10854d] mr-2"></i>Farmers remain focused on seller and pickup details, not customer delivery addresses.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </main>

    <script>
        let addressMap = null;
        let addressMarker = null;
        let mapSearchTimer = null;

        const toggleAddressForm = document.getElementById('toggleAddressForm');
        const cancelAddressForm = document.getElementById('cancelAddressForm');
        const addressFormPanel = document.getElementById('addressFormPanel');

        if (toggleAddressForm && addressFormPanel) {
            toggleAddressForm.addEventListener('click', () => {
                addressFormPanel.classList.remove('hidden');
                addressFormPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (cancelAddressForm && addressFormPanel) {
            cancelAddressForm.addEventListener('click', () => {
                addressFormPanel.classList.add('hidden');
            });
        }

        function updateCoordinateInputs(lat, lng) {
            const latInput = document.getElementById('latitudeInput');
            const lngInput = document.getElementById('longitudeInput');
            if (latInput) latInput.value = Number(lat).toFixed(8);
            if (lngInput) lngInput.value = Number(lng).toFixed(8);
        }

        function hideMapSearchResults() {
            const results = document.getElementById('mapSearchResults');
            if (!results) return;
            results.innerHTML = '';
            results.classList.add('hidden');
        }

        function fillAddressFromResult(item) {
            const addressLine1 = document.querySelector('input[name="address_line1"]');
            const barangay = document.querySelector('input[name="barangay"]');
            const city = document.querySelector('input[name="city"]');
            const province = document.querySelector('input[name="province"]');
            const postalCode = document.querySelector('input[name="postal_code"]');
            const address = item.address || {};

            if (addressLine1 && !addressLine1.value.trim()) {
                const roadParts = [address.house_number || '', address.road || address.neighbourhood || address.suburb || ''].filter(Boolean);
                addressLine1.value = roadParts.join(' ').trim() || (item.display_name || '').split(',').slice(0, 2).join(',').trim();
            }
            if (barangay && !barangay.value.trim()) barangay.value = address.suburb || address.village || address.neighbourhood || '';
            if (city && !city.value.trim()) city.value = address.city || address.town || address.municipality || address.county || '';
            if (province && !province.value.trim()) province.value = address.state || '';
            if (postalCode && !postalCode.value.trim()) postalCode.value = address.postcode || '';
        }

        function renderMapSearchResults(results) {
            const resultsBox = document.getElementById('mapSearchResults');
            if (!resultsBox) return;

            if (!Array.isArray(results) || results.length === 0) {
                hideMapSearchResults();
                return;
            }

            resultsBox.innerHTML = '';
            results.slice(0, 5).forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'map-search-item';
                button.textContent = item.display_name || 'Unknown location';
                button.addEventListener('click', () => {
                    const lat = parseFloat(item.lat);
                    const lng = parseFloat(item.lon);
                    if (isNaN(lat) || isNaN(lng) || !addressMap || !addressMarker) return;
                    addressMap.setView([lat, lng], 16);
                    addressMarker.setLatLng([lat, lng]);
                    updateCoordinateInputs(lat, lng);
                    fillAddressFromResult(item);
                    hideMapSearchResults();
                });
                resultsBox.appendChild(button);
            });
            resultsBox.classList.remove('hidden');
        }

        function searchAddress(query) {
            if (!query || query.length < 4) {
                hideMapSearchResults();
                return;
            }

            fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&countrycodes=ph&limit=5&q=${encodeURIComponent(query)}`)
                .then((response) => response.json())
                .then((data) => renderMapSearchResults(Array.isArray(data) ? data : []))
                .catch(() => hideMapSearchResults());
        }

        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data) {
                        fillAddressFromResult(data);
                    }
                })
                .catch(() => {});
        }

        function initAddressMap() {
            const mapEl = document.getElementById('addressMap');
            if (!mapEl || typeof L === 'undefined') return;

            const latInput = document.getElementById('latitudeInput');
            const lngInput = document.getElementById('longitudeInput');
            const mapSearchInput = document.getElementById('mapSearchInput');
            const mapSearchBtn = document.getElementById('mapSearchBtn');
            const savedLat = parseFloat(latInput?.value || '');
            const savedLng = parseFloat(lngInput?.value || '');
            const initialLat = !isNaN(savedLat) ? savedLat : 14.5995;
            const initialLng = !isNaN(savedLng) ? savedLng : 120.9842;
            const initialZoom = (!isNaN(savedLat) && !isNaN(savedLng)) ? 16 : 11;

            addressMap = L.map('addressMap').setView([initialLat, initialLng], initialZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(addressMap);

            addressMarker = L.marker([initialLat, initialLng], { draggable: true }).addTo(addressMap);
            updateCoordinateInputs(initialLat, initialLng);

            addressMarker.on('dragend', (event) => {
                const point = event.target.getLatLng();
                updateCoordinateInputs(point.lat, point.lng);
                reverseGeocode(point.lat, point.lng);
            });

            addressMap.on('click', (event) => {
                const { lat, lng } = event.latlng;
                addressMarker.setLatLng([lat, lng]);
                updateCoordinateInputs(lat, lng);
                reverseGeocode(lat, lng);
            });

            if (mapSearchInput) {
                mapSearchInput.addEventListener('input', () => {
                    clearTimeout(mapSearchTimer);
                    mapSearchTimer = setTimeout(() => searchAddress(mapSearchInput.value.trim()), 400);
                });
                mapSearchInput.addEventListener('blur', () => {
                    setTimeout(() => hideMapSearchResults(), 150);
                });
            }

            if (mapSearchBtn && mapSearchInput) {
                mapSearchBtn.addEventListener('click', () => {
                    searchAddress(mapSearchInput.value.trim());
                });
                mapSearchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        searchAddress(mapSearchInput.value.trim());
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initAddressMap();
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</body>
</html>
