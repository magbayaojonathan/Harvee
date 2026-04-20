<?php
session_start();
require_once 'shared/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Harvee</title>
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
        .contact-icon {
            transition: transform 0.3s ease;
        }
        .contact-icon:hover {
            transform: translateY(-4px);
        }
        .form-input {
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #10854d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 133, 77, 0.1);
        }
        .btn-submit {
            transition: all 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -8px rgba(16, 133, 77, 0.4);
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Hero Section -->
<section class="relative bg-cover bg-center bg-no-repeat h-80 flex items-center" 
         style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 text-center text-white">
        <h1 class="text-5xl md:text-6xl font-extrabold mb-4">Contact Us</h1>
        <p class="text-xl md:text-2xl max-w-3xl mx-auto">We’d love to hear from you — whether it’s a question, feedback, or partnership idea.</p>
    </div>
</section>

<!-- Contact Info Cards -->
<section class="py-16 px-4 max-w-7xl mx-auto">
    <div class="grid md:grid-cols-3 gap-8">
        <div class="glass-card p-6 text-center contact-icon">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="phone" class="w-7 h-7 text-green-700"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Phone</h3>
            <p class="text-gray-600">+1 (555) 123-4567</p>
            <p class="text-gray-500 text-sm">Mon-Fri, 9am - 6pm</p>
        </div>
        <div class="glass-card p-6 text-center contact-icon">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="mail" class="w-7 h-7 text-green-700"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Email</h3>
            <p class="text-gray-600">support@harvee.com</p>
            <p class="text-gray-600">hello@harvee.com</p>
        </div>
        <div class="glass-card p-6 text-center contact-icon">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="map-pin" class="w-7 h-7 text-green-700"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Office</h3>
            <p class="text-gray-600">123 Farmstead Avenue<br>Nairobi, Kenya</p>
        </div>
    </div>
</section>

<!-- Contact Form & Map -->
<section class="py-8 px-4 max-w-7xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Send us a message</h2>
            <form action="#" method="POST" id="contactForm">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-green-600 focus:ring-2 focus:ring-green-200">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-green-600 focus:ring-2 focus:ring-green-200">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Subject</label>
                    <input type="text" name="subject" 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-green-600 focus:ring-2 focus:ring-green-200">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Message <span class="text-red-500">*</span></label>
                    <textarea name="message" rows="5" required 
                              class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-green-600 focus:ring-2 focus:ring-green-200"></textarea>
                </div>
                <button type="submit" class="btn-submit w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-6 rounded-xl transition flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i> Send Message
                </button>
                <p class="text-sm text-gray-500 mt-4 text-center">We’ll get back to you within 24‑48 hours.</p>
            </form>
        </div>

        <!-- Map Placeholder & Additional Info -->
        <div>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <!-- Replace with actual Google Maps embed or static image -->
                    <img src="https://placehold.co/600x400/e2e8f0/64748b?text=Map+View" alt="Map placeholder" class="w-full h-full object-cover">
                </div>
                <div class="p-4 text-center text-gray-500 text-sm">
                    <i data-lucide="navigation" class="inline-block w-4 h-4 mr-1"></i> Main Office: 123 Farmstead Avenue, Nairobi
                </div>
            </div>
            <div class="bg-green-50 rounded-2xl p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-3">Frequently Asked Questions</h3>
                <div class="space-y-3">
                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <span class="font-medium text-gray-800">How do I become a farmer on Harvee?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 group-open:rotate-180 transition"></i>
                        </summary>
                        <p class="text-gray-600 mt-2 pl-4">Simply click "Become a Farmer" on the homepage, fill out the registration form, and our team will verify your farm within 2 business days.</p>
                    </details>
                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <span class="font-medium text-gray-800">What areas do you serve?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 group-open:rotate-180 transition"></i>
                        </summary>
                        <p class="text-gray-600 mt-2 pl-4">We currently deliver to Nairobi, Kisumu, Mombasa, and surrounding counties. We’re expanding rapidly!</p>
                    </details>
                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <span class="font-medium text-gray-800">How can I partner with Harvee?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 group-open:rotate-180 transition"></i>
                        </summary>
                        <p class="text-gray-600 mt-2 pl-4">Send us a message through this form or email partnerships@harvee.com with your proposal.</p>
                    </details>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Social Media Links -->
<section class="py-12 text-center">
    <div class="max-w-4xl mx-auto px-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Follow Our Journey</h3>
        <div class="flex justify-center gap-8">
            <a href="#" class="text-gray-600 hover:text-green-700 transition transform hover:scale-110">
                <i data-lucide="facebook" class="w-8 h-8"></i>
            </a>
            <a href="#" class="text-gray-600 hover:text-green-700 transition transform hover:scale-110">
                <i data-lucide="instagram" class="w-8 h-8"></i>
            </a>
            <a href="#" class="text-gray-600 hover:text-green-700 transition transform hover:scale-110">
                <i data-lucide="twitter" class="w-8 h-8"></i>
            </a>
            <a href="#" class="text-gray-600 hover:text-green-700 transition transform hover:scale-110">
                <i data-lucide="linkedin" class="w-8 h-8"></i>
            </a>
        </div>
    </div>
</section>

<!-- Simple JavaScript for form submission (frontend only) -->
<script>
    document.getElementById('contactForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Thank you for reaching out! Our team will respond shortly. (Demo mode)');
        this.reset();
    });
</script>

<?php require_once '../shared/footer.php'; ?>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
