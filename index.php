<?php
session_start();
require_once 'config/database.php';
require_once 'shared/header.php';
require_once 'shared/navigation.php';
?>

<main>
    <section class="hero">
        <h1>Welcome to Harvee</h1>
        <p>Connecting Farmers Directly to Customers</p>
    </section>
    
    <section class="features">
        <div class="feature-card">
            <h3>For Farmers</h3>
            <p>Showcase your products to a wider market</p>
        </div>
        <div class="feature-card">
            <h3>For Customers</h3>
            <p>Buy fresh produce directly from local farmers</p>
        </div>
    </section>
</main>

<?php require_once 'shared/footer.php'; ?>