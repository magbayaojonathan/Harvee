<?php
session_start();
require_once 'config/database.php';
require_once 'shared/header.php';
require_once 'shared/navigation.php';

// Fetch featured products
$featured_products = [];
try {
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.description, p.price, p.image_url, f.business_name FROM products p JOIN farmers f ON p.farmer_id = f.id WHERE p.is_active = 1 LIMIT 6");
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Continue without featured products if query fails
}
?>

    <!-- Hero Section -->
    <section class="hero" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 80px 20px;">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
            <div class="hero-content" style="text-align: left;">
                <h1 style="font-size: 3.5rem; margin-bottom: 20px; font-weight: bold;">Welcome to Harvee</h1>
                <p style="font-size: 1.3rem; margin-bottom: 30px; opacity: 0.95;">Connecting Farmers Directly to Customers for Fresh Produce</p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="customer/browse.php" style="display: inline-block; background: white; color: #4CAF50; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: all 0.3s;">Browse Products</a>
                    <?php else: ?>
                        <a href="auth/login.php" style="display: inline-block; background: white; color: #4CAF50; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: all 0.3s;">Shop Now</a>
                        <a href="auth/register.php" style="display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; border: 2px solid white; transition: all 0.3s;">Create Account</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image" style="text-align: center;">
                <img src="assets/images/Farm.avif" alt="Fresh Farm Produce" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" style="padding: 60px 20px; background: #f9f9f9;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 50px; color: #333;">Why Choose Harvee?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🌾</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Fresh from Farm</h3>
                    <p style="color: #666;">Get the freshest produce directly from local farmers, picked at peak ripeness</p>
                </div>
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">👨‍🌾</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Support Local</h3>
                    <p style="color: #666;">Buy directly from farmers and support local agriculture and rural communities</p>
                </div>
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">💰</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Best Prices</h3>
                    <p style="color: #666;">No middlemen means better prices for both farmers and customers</p>
                </div>
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📦</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Easy Ordering</h3>
                    <p style="color: #666;">Simple and secure platform for ordering and payment</p>
                </div>
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🚚</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Fast Delivery</h3>
                    <p style="color: #666;">Quick delivery to your doorstep while maintaining freshness</p>
                </div>
                <div class="feature-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="font-size: 3rem; margin-bottom: 15px;">✅</div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #4CAF50;">Quality Assured</h3>
                    <p style="color: #666;">All products are verified for quality and freshness</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <?php if(!empty($featured_products)): ?>
    <section style="padding: 60px 20px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 50px; color: #333;">Featured Products</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px;">
                <?php foreach($featured_products as $product): ?>
                <div class="product-card" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                    <div style="width: 100%; height: 200px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="font-size: 3rem;">🥬</div>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 20px;">
                        <h3 style="font-size: 1.2rem; margin-bottom: 8px; color: #333;"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                        <p style="color: #999; font-size: 0.85rem; margin-bottom: 15px;">by <?php echo htmlspecialchars($product['business_name']); ?></p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 1.5rem; font-weight: bold; color: #4CAF50;">₹<?php echo number_format($product['price'], 2); ?></span>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" style="background: #4CAF50; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; transition: background 0.3s;">View</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="customer/browse.php" style="display: inline-block; background: #4CAF50; color: white; padding: 12px 40px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: background 0.3s;">View All Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- How It Works Section -->
    <section style="padding: 60px 20px; background: #f9f9f9;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 50px; color: #333;">How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #4CAF50; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="width: 60px; height: 60px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; margin: 0 auto 20px;">1</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 15px; color: #333;">Create Account</h3>
                    <p style="color: #666; line-height: 1.6;">Sign up as a customer or farmer to get started on our platform</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #4CAF50; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="width: 60px; height: 60px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; margin: 0 auto 20px;">2</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 15px; color: #333;">Browse & Add</h3>
                    <p style="color: #666; line-height: 1.6;">Customers browse products; Farmers add their quality listings</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #4CAF50; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="width: 60px; height: 60px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; margin: 0 auto 20px;">3</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 15px; color: #333;">Order & Pay</h3>
                    <p style="color: #666; line-height: 1.6;">Place orders and make secure payments with multiple options</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #4CAF50; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
                    <div style="width: 60px; height: 60px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; margin: 0 auto 20px;">4</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 15px; color: #333;">Receive & Enjoy</h3>
                    <p style="color: #666; line-height: 1.6;">Get fresh produce delivered to your doorstep quickly</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 60px 20px; text-align: center;">
        <div style="max-width: 800px; margin: 0 auto;">
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Ready to Get Started?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 30px; opacity: 0.95;">Join thousands of customers and farmers already using Harvee</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="customer/dashboard.php" style="display: inline-block; background: white; color: #4CAF50; padding: 12px 40px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s;">Go to Dashboard</a>
                <?php else: ?>
                    <a href="auth/register.php?role=customer" style="display: inline-block; background: white; color: #4CAF50; padding: 12px 40px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s;">Sign Up as Customer</a>
                    <a href="auth/register.php?role=farmer" style="display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 12px 40px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 1.1rem; border: 2px solid white; transition: all 0.3s;">Become a Farmer</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php require_once 'shared/footer.php'; ?>