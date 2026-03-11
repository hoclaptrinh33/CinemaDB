-- =============================================================================
-- FUNCTIONS & STORED PROCEDURES
-- Note: No DELIMITER directive — executed via PDO (DB::unprepared)
-- =============================================================================

-- Function: Get total reviews for a title
CREATE FUNCTION fn_get_total_reviews(p_title_id INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT IFNULL(rating_count, 0) INTO v_total
    FROM titles WHERE title_id = p_title_id;
    RETURN v_total;
END;

-- Procedure: Add a new title
CREATE PROCEDURE prc_add_title(
    IN  p_title_name   VARCHAR(300),
    IN  p_release_date DATE,
    IN  p_runtime      INT,
    IN  p_title_type   VARCHAR(20),
    IN  p_language_id  INT,
    OUT p_title_id     INT
)
BEGIN
    INSERT INTO titles (
        title_name, release_date, runtime_mins, title_type,
        original_language_id, status, visibility
    )
    VALUES (
        p_title_name, p_release_date, p_runtime, p_title_type,
        p_language_id, 'Released', 'PUBLIC'
    );
    SET p_title_id = LAST_INSERT_ID();
END;

-- Procedure: Add a review with validation
CREATE PROCEDURE prc_add_review(
    IN p_title_id    INT,
    IN p_user_id     INT,
    IN p_rating      INT,
    IN p_review_text TEXT
)
BEGIN
    -- DECLARE must be at the top of the BEGIN block (MySQL requirement)
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'User has already reviewed this title.';

    IF p_rating < 1 OR p_rating > 10 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Rating must be between 1 and 10.';
    END IF;

    INSERT INTO reviews (title_id, user_id, rating, review_text)
    VALUES (p_title_id, p_user_id, p_rating, p_review_text);
END;
