USE harvee_marketplace;

DROP TRIGGER IF EXISTS update_product_rating_after_review;
DROP TRIGGER IF EXISTS update_farmer_rating_after_review;

DELIMITER //
CREATE TRIGGER update_product_rating_after_review
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE review_count INT;

    SELECT AVG(r.rating), COUNT(*) INTO avg_rating, review_count
    FROM reviews r
    WHERE r.product_id = NEW.product_id AND r.is_approved = TRUE;

    UPDATE products
    SET rating = COALESCE(avg_rating, 0), total_reviews = COALESCE(review_count, 0)
    WHERE id = NEW.product_id;
END//

CREATE TRIGGER update_farmer_rating_after_review
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE review_count INT;

    SELECT AVG(r.rating), COUNT(*) INTO avg_rating, review_count
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    WHERE p.farmer_id = NEW.farmer_id AND r.is_approved = TRUE;

    UPDATE farmer_profiles
    SET rating = COALESCE(avg_rating, 0), total_reviews = COALESCE(review_count, 0)
    WHERE user_id = NEW.farmer_id;
END//
DELIMITER ;
