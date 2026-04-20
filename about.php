<?php
session_start();
require_once 'shared/header.php';

$teamMembers = [
    [
        'name' => 'Althea Kaye Palentinos',
        'role' => 'CEO',
        'photo' => 'assets/images/palentinos.jpg',
    ],
    [
        'name' => 'Jonathan Magbayao',
        'role' => 'CMO',
        'photo' => 'assets/images/magbayao.png',
    ],
    [
        'name' => 'Reves Mark Jerome',
        'role' => 'CTO',
        'photo' => 'assets/images/reves.png',
    ],
    [
        'name' => 'Marco Samson',
        'role' => 'COO',
        'photo' => 'assets/images/marco.png',
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/lucide-static@0.263.0/font/lucide.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 2rem;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
        }
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .team-card {
            transition: all 0.3s ease;
        }
        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50">

<section class="relative bg-cover bg-center bg-no-repeat h-96 flex items-center"
         style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 text-center text-white">
        <h1 class="text-5xl md:text-6xl font-extrabold mb-4">About Harvee</h1>
        <p class="text-xl md:text-2xl max-w-3xl mx-auto">Connecting local farmers directly with customers for a fresher, fairer, farm-to-table experience.</p>
    </div>
</section>

<section class="py-16 px-4 max-w-7xl mx-auto">
    <div class="grid md:grid-cols-2 gap-8">
        <div class="glass-card p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="target" class="w-8 h-8 text-green-700"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-3">Our Mission</h3>
            <p class="text-gray-600">Share your own mission here. This section is ready for your final content.</p>
        </div>
        <div class="glass-card p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="eye" class="w-8 h-8 text-green-700"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-3">Our Vision</h3>
            <p class="text-gray-600">Share your own vision here. This section is ready for your final content.</p>
        </div>
    </div>
</section>

<section class="hero-gradient py-12 text-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="stat-card">
                <div class="text-4xl font-bold">500+</div>
                <div class="text-lg mt-2">Local Farmers</div>
            </div>
            <div class="stat-card">
                <div class="text-4xl font-bold">10k+</div>
                <div class="text-lg mt-2">Happy Customers</div>
            </div>
            <div class="stat-card">
                <div class="text-4xl font-bold">50+</div>
                <div class="text-lg mt-2">Cities Served</div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 px-4 max-w-7xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Story</h2>
            <p class="text-gray-600 mb-4 leading-relaxed">You can talk about how Harvee started, your goals, the people behind the platform, and why the project matters.</p>
            <p class="text-gray-600 leading-relaxed">Everything here is a placeholder so you can edit it easily later.</p>
        </div>
        <div class="rounded-2xl overflow-hidden shadow-xl">
            <img src="https://images.unsplash.com/photo-1464226184884-fa280b87c399?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                 alt="Farm story" class="w-full h-80 object-cover">
        </div>
    </div>
</section>

<section class="bg-green-50 py-16 px-4">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Our Core Values</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <i data-lucide="leaf" class="w-12 h-12 text-green-600 mx-auto mb-4"></i>
                <h3 class="text-xl font-bold mb-2">Sustainability</h3>
                <p class="text-gray-600">Add your own sustainability statement here.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <i data-lucide="handshake" class="w-12 h-12 text-green-600 mx-auto mb-4"></i>
                <h3 class="text-xl font-bold mb-2">Fair Trade</h3>
                <p class="text-gray-600">Add your own fair trade statement here.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <i data-lucide="users" class="w-12 h-12 text-green-600 mx-auto mb-4"></i>
                <h3 class="text-xl font-bold mb-2">Community</h3>
                <p class="text-gray-600">Add your own community statement here.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 px-4 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Meet the Team</h2>
    <p class="text-center text-gray-500 mb-10">You can change the photo paths easily in the <code>$teamMembers</code> array at the top of this file.</p>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($teamMembers as $member): ?>
        <div class="team-card bg-white rounded-2xl overflow-hidden shadow-md">
            <img
                src="<?php echo htmlspecialchars($member['photo']); ?>"
                alt="<?php echo htmlspecialchars($member['name']); ?>"
                class="w-full h-64 object-cover"
                onerror="this.src='assets/images/logo.png'; this.classList.add('p-8');"
            >
            <div class="p-6 text-center">
                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($member['name']); ?></h3>
                <p class="text-green-600 mb-2"><?php echo htmlspecialchars($member['role']); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="hero-gradient py-16 text-white text-center">
    <div class="max-w-3xl mx-auto px-4">
        <h2 class="text-3xl font-bold mb-4">Join the Harvee Community</h2>
        <p class="text-lg mb-8">Whether you are a farmer, a customer, or a delivery partner, there is a place for you here.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="auth/register.php?role=farmer" class="bg-white text-green-800 px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition">Become a Farmer</a>
            <a href="auth/register.php?role=customer" class="bg-transparent border-2 border-white px-6 py-3 rounded-full font-bold hover:bg-white hover:text-green-800 transition">Shop Now</a>
        </div>
    </div>
</section>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
