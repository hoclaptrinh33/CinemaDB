# 03 — Database Schema

## Sơ đồ quan hệ (ERD — tóm tắt)

```
countries ──────┬─── studios
                └─── persons

languages ──────── titles ─────┬─── series ─── seasons ─── episodes
                                ├─── title_studios ──── studios
                                ├─── title_person_roles ──── persons ──── roles
                                └─── reviews ──── users
```

---

## Script SQL đầy đủ

### 1 — Lookup Tables

```sql
-- -----------------------------------------------------------------------------
-- 1️⃣ LOOKUP TABLES
-- -----------------------------------------------------------------------------
CREATE TABLE countries (
    country_id   INT AUTO_INCREMENT,
    iso_code     VARCHAR(3)   NOT NULL,
    country_name VARCHAR(100) NOT NULL,
    CONSTRAINT pk_countries  PRIMARY KEY (country_id),
    CONSTRAINT uq_country_iso UNIQUE (iso_code)
);

CREATE TABLE languages (
    language_id   INT AUTO_INCREMENT,
    iso_code      VARCHAR(5)   NOT NULL,
    language_name VARCHAR(100) NOT NULL,
    CONSTRAINT pk_languages    PRIMARY KEY (language_id),
    CONSTRAINT uq_language_iso UNIQUE (iso_code)
);
```

---

### 2 — Titles (Movie + Series + Episode)

```sql
-- -----------------------------------------------------------------------------
-- 2️⃣ TITLES (Movie + Series + Episode)
-- -----------------------------------------------------------------------------
CREATE TABLE titles (
    title_id             INT AUTO_INCREMENT,
    title_name           VARCHAR(300) NOT NULL,
    original_language_id INT,
    release_date         DATE,
    runtime_mins         INT,
    title_type           VARCHAR(20)  NOT NULL CHECK (title_type IN ('MOVIE','SERIES','EPISODE')),
    description          TEXT,

    -- Denormalization (cập nhật bởi Trigger)
    rating_sum           INT          DEFAULT 0,
    rating_count         INT          DEFAULT 0,
    avg_rating           DECIMAL(4,2) DEFAULT 0.00,

    -- Media
    poster_path          VARCHAR(500),
    backdrop_path        VARCHAR(500),
    trailer_url          VARCHAR(500),

    status       VARCHAR(20) CHECK (status IN ('Rumored','Post Production','Released','Canceled')),
    budget       BIGINT,
    revenue      BIGINT,
    visibility   VARCHAR(20) DEFAULT 'PUBLIC'
                 CHECK (visibility IN ('PUBLIC','HIDDEN','COPYRIGHT_STRIKE','GEO_BLOCKED')),
    moderation_reason VARCHAR(500),
    hidden_at    TIMESTAMP NULL,

    CONSTRAINT pk_titles    PRIMARY KEY (title_id),
    CONSTRAINT fk_title_lang FOREIGN KEY (original_language_id)
        REFERENCES languages(language_id)
);
```

> **Lưu ý denormalization:** `rating_sum`, `rating_count`, `avg_rating` không được cập nhật thủ công — chúng được quản lý hoàn toàn bởi Trigger `trg_update_title_rating_*`. Code Laravel **không được** tự cập nhật các cột này.

---

### 3 — Series / Season / Episode

```sql
-- -----------------------------------------------------------------------------
-- 3️⃣ SERIES / SEASON / EPISODE
-- -----------------------------------------------------------------------------
CREATE TABLE series (
    series_id INT PRIMARY KEY,
    CONSTRAINT fk_series_title FOREIGN KEY (series_id)
        REFERENCES titles(title_id) ON DELETE CASCADE
);

CREATE TABLE seasons (
    season_id     INT AUTO_INCREMENT,
    series_id     INT NOT NULL,
    season_number INT NOT NULL,
    CONSTRAINT pk_seasons       PRIMARY KEY (season_id),
    CONSTRAINT fk_season_series FOREIGN KEY (series_id)
        REFERENCES series(series_id) ON DELETE CASCADE
);

CREATE TABLE episodes (
    episode_id     INT PRIMARY KEY,
    season_id      INT NOT NULL,
    episode_number INT NOT NULL,
    air_date       DATE,
    CONSTRAINT fk_episode_title  FOREIGN KEY (episode_id)
        REFERENCES titles(title_id) ON DELETE CASCADE,
    CONSTRAINT fk_episode_season FOREIGN KEY (season_id)
        REFERENCES seasons(season_id) ON DELETE CASCADE
);
```

> **Pattern:** Một Series là một `titles` record với `title_type='SERIES'`. `series.series_id` = `titles.title_id` (1-to-1 shared PK). Tương tự, Episode cũng là bản ghi trong `titles` với `title_type='EPISODE'`.

---

### 4 — Studios

```sql
-- -----------------------------------------------------------------------------
-- 4️⃣ STUDIOS
-- -----------------------------------------------------------------------------
CREATE TABLE studios (
    studio_id    INT AUTO_INCREMENT,
    studio_name  VARCHAR(200) NOT NULL,
    country_id   INT,
    founded_year INT,
    website_url  VARCHAR(500),
    logo_path    VARCHAR(500),
    CONSTRAINT pk_studios       PRIMARY KEY (studio_id),
    CONSTRAINT fk_studio_country FOREIGN KEY (country_id)
        REFERENCES countries(country_id)
);

CREATE TABLE title_studios (
    title_id  INT,
    studio_id INT,
    CONSTRAINT pk_title_studios PRIMARY KEY (title_id, studio_id),
    CONSTRAINT fk_ts_title      FOREIGN KEY (title_id)
        REFERENCES titles(title_id) ON DELETE CASCADE,
    CONSTRAINT fk_ts_studio     FOREIGN KEY (studio_id)
        REFERENCES studios(studio_id) ON DELETE CASCADE
);
```

---

### 5 — Persons & Cast/Crew

```sql
-- -----------------------------------------------------------------------------
-- 5️⃣ PERSONS & CAST/CREW
-- -----------------------------------------------------------------------------
CREATE TABLE persons (
    person_id    INT AUTO_INCREMENT,
    full_name    VARCHAR(200) NOT NULL,
    birth_date   DATE,
    death_date   DATE,
    gender       VARCHAR(10) CHECK (gender IN ('Male','Female','Other')),
    country_id   INT,
    biography    TEXT,
    profile_path VARCHAR(500),
    CONSTRAINT pk_persons       PRIMARY KEY (person_id),
    CONSTRAINT fk_person_country FOREIGN KEY (country_id)
        REFERENCES countries(country_id)
);

CREATE TABLE roles (
    role_id   INT AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    CONSTRAINT pk_roles PRIMARY KEY (role_id),
    CONSTRAINT uq_role  UNIQUE (role_name)
);

-- Bảng pivot 3 chiều: title ↔ person ↔ role
CREATE TABLE title_person_roles (
    title_id       INT,
    person_id      INT,
    role_id        INT,
    character_name VARCHAR(200),   -- Tên nhân vật (cho diễn viên)
    cast_order     INT,            -- Thứ tự xuất hiện trong credit
    CONSTRAINT pk_title_person_roles PRIMARY KEY (title_id, person_id, role_id),
    CONSTRAINT fk_tpr_title  FOREIGN KEY (title_id)
        REFERENCES titles(title_id) ON DELETE CASCADE,
    CONSTRAINT fk_tpr_person FOREIGN KEY (person_id)
        REFERENCES persons(person_id) ON DELETE CASCADE,
    CONSTRAINT fk_tpr_role   FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
);
```

> Các `role_name` seed mặc định: `Actor`, `Director`, `Writer`, `Producer`, `Cinematographer`, `Composer`, `Editor`.

---

### 6 — Users, Reviews & Audit Log

```sql
-- -----------------------------------------------------------------------------
-- 6️⃣ USERS & REVIEWS & AUDIT LOG
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(512) NOT NULL,
    role          VARCHAR(20)  DEFAULT 'USER'
                  CHECK (role IN ('USER','MODERATOR','ADMIN')),
    is_active     TINYINT(1)   DEFAULT 1,
    reputation    INT          DEFAULT 0,           -- Điểm uy tín tích lũy
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_users    PRIMARY KEY (user_id),
    CONSTRAINT uq_username UNIQUE (username),
    CONSTRAINT uq_email    UNIQUE (email)
);

CREATE TABLE reviews (
    review_id    INT AUTO_INCREMENT,
    title_id     INT NOT NULL,
    user_id      INT NOT NULL,
    rating       INT CHECK (rating BETWEEN 1 AND 10),
    review_text  TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    helpful_votes     INT       DEFAULT 0,
    has_spoilers      TINYINT(1) DEFAULT 0,
    reputation_earned INT       DEFAULT 0,          -- Điểm user kiếm từ bài viết này (dùng để trừ khi xóa)
    CONSTRAINT pk_reviews      PRIMARY KEY (review_id),
    CONSTRAINT fk_review_title FOREIGN KEY (title_id)
        REFERENCES titles(title_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user  FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT uq_user_title   UNIQUE (title_id, user_id)  -- Chống spam: 1 user chỉ review 1 phim 1 lần
);

-- Bảng pivot chống double helpful vote
CREATE TABLE review_helpful_votes (
    review_id INT NOT NULL,
    user_id   INT NOT NULL,
    voted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_review_helpful_votes PRIMARY KEY (review_id, user_id),
    CONSTRAINT fk_rhv_review FOREIGN KEY (review_id)
        REFERENCES reviews(review_id) ON DELETE CASCADE,
    CONSTRAINT fk_rhv_user   FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE title_audit_log (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    title_id    INT,
    action_type VARCHAR(20) CHECK (action_type IN ('INSERT','UPDATE','DELETE')),
    old_title   VARCHAR(300),
    new_title   VARCHAR(300),
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action_user VARCHAR(50)
);
```

---

### 7 — Indexes

```sql
-- -----------------------------------------------------------------------------
-- 7️⃣ CÁC CHỈ MỤC (INDEXES)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_titles_name     ON titles(title_name);
CREATE INDEX idx_titles_release  ON titles(release_date);
CREATE INDEX idx_titles_rating   ON titles(avg_rating);
CREATE INDEX idx_persons_name    ON persons(full_name);
CREATE INDEX idx_reviews_title   ON reviews(title_id);
CREATE INDEX idx_reviews_user    ON reviews(user_id);
CREATE INDEX idx_tpr_title       ON title_person_roles(title_id);
CREATE INDEX idx_tpr_person      ON title_person_roles(person_id);
CREATE INDEX idx_tpr_role        ON title_person_roles(role_id);
CREATE INDEX idx_title_studio_title  ON title_studios(title_id);
CREATE INDEX idx_title_studio_studio ON title_studios(studio_id);
CREATE INDEX idx_season_series   ON seasons(series_id);
CREATE INDEX idx_episode_season  ON episodes(season_id);
```

---

### 8 — Views

```sql
-- -----------------------------------------------------------------------------
-- 8️⃣ TẠO VIEW
-- -----------------------------------------------------------------------------

-- Chi tiết title cơ bản
CREATE OR REPLACE VIEW vw_title_details AS
SELECT
    t.title_id,
    t.title_name,
    t.title_type,
    t.release_date,
    t.runtime_mins,
    t.avg_rating,
    t.rating_count,
    l.language_name
FROM titles t
LEFT JOIN languages l ON t.original_language_id = l.language_id;

-- Reviews kèm thông tin user và title
CREATE OR REPLACE VIEW vw_user_reviews AS
SELECT
    r.review_id,
    u.username,
    t.title_name,
    r.rating,
    r.review_text,
    r.created_at
FROM reviews r
JOIN users u ON r.user_id = u.user_id
JOIN titles t ON r.title_id = t.title_id;
```

---

### 9 — Triggers

```sql
-- -----------------------------------------------------------------------------
-- 9️⃣ TẠO TRIGGERS
-- -----------------------------------------------------------------------------
DELIMITER //

-- Sau khi INSERT review: cộng dồn rating
CREATE TRIGGER trg_update_title_rating_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE titles
    SET rating_sum   = rating_sum + NEW.rating,
        rating_count = rating_count + 1,
        avg_rating   = (rating_sum) / (rating_count)
    WHERE title_id = NEW.title_id;
END;
//

-- Sau khi DELETE review: trừ rating
CREATE TRIGGER trg_update_title_rating_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    UPDATE titles
    SET rating_sum   = rating_sum - OLD.rating,
        rating_count = rating_count - 1,
        avg_rating   = IF(rating_count <= 0, 0, rating_sum / rating_count)
    WHERE title_id = OLD.title_id;
END;
//

-- Sau khi UPDATE review: hiệu chỉnh rating
CREATE TRIGGER trg_update_title_rating_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    UPDATE titles
    SET rating_sum = rating_sum - OLD.rating + NEW.rating,
        avg_rating = IF(rating_count = 0, 0, rating_sum / rating_count)
    WHERE title_id = NEW.title_id;
END;
//

-- Audit: ghi log khi INSERT title
CREATE TRIGGER trg_audit_title_insert
AFTER INSERT ON titles
FOR EACH ROW
BEGIN
    INSERT INTO title_audit_log (title_id, action_type, new_title, action_user)
    VALUES (NEW.title_id, 'INSERT', NEW.title_name, USER());
END;
//

-- Audit: ghi log khi UPDATE title
CREATE TRIGGER trg_audit_title_update
AFTER UPDATE ON titles
FOR EACH ROW
BEGIN
    INSERT INTO title_audit_log (title_id, action_type, old_title, new_title, action_user)
    VALUES (NEW.title_id, 'UPDATE', OLD.title_name, NEW.title_name, USER());
END;
//

-- Audit: ghi log khi DELETE title
CREATE TRIGGER trg_audit_title_delete
AFTER DELETE ON titles
FOR EACH ROW
BEGIN
    INSERT INTO title_audit_log (title_id, action_type, old_title, action_user)
    VALUES (OLD.title_id, 'DELETE', OLD.title_name, USER());
END;
//

DELIMITER ;
```

---

### 10 — Functions & Stored Procedures

```sql
-- -----------------------------------------------------------------------------
-- 🔟 TẠO FUNCTIONS & PROCEDURES
-- -----------------------------------------------------------------------------
DELIMITER //

-- Function: Lấy tổng số review của 1 title
CREATE FUNCTION fn_get_total_reviews(p_title_id INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT IFNULL(rating_count, 0) INTO v_total
    FROM titles WHERE title_id = p_title_id;
    RETURN v_total;
END;
//

-- Procedure: Thêm title mới
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
//

-- Procedure: Thêm review với kiểm tra hợp lệ
CREATE PROCEDURE prc_add_review(
    IN p_title_id   INT,
    IN p_user_id    INT,
    IN p_rating     INT,
    IN p_review_text TEXT
)
BEGIN
    IF p_rating < 1 OR p_rating > 10 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Điểm đánh giá phải từ 1 đến 10.';
    ELSE
        BEGIN
            DECLARE EXIT HANDLER FOR 1062
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'User đã review title này rồi.';

            INSERT INTO reviews (title_id, user_id, rating, review_text)
            VALUES (p_title_id, p_user_id, p_rating, p_review_text);
        END;
    END IF;
END;
//

DELIMITER ;
```

---

## Chiến lược Migration trong Laravel

> Schema đã được định nghĩa trực tiếp bằng SQL thuần (triggers, procedures, views). Cách triển khai trong Laravel:

```
database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php       ← sửa lại thêm cột role, is_active
│   ├── 2026_03_08_000001_create_countries_table.php
│   ├── 2026_03_08_000002_create_languages_table.php
│   ├── 2026_03_08_000003_create_titles_table.php
│   ├── 2026_03_08_000004_create_series_table.php
│   ├── 2026_03_08_000005_create_seasons_table.php
│   ├── 2026_03_08_000006_create_episodes_table.php
│   ├── 2026_03_08_000007_create_studios_table.php
│   ├── 2026_03_08_000008_create_title_studios_table.php
│   ├── 2026_03_08_000009_create_persons_table.php
│   ├── 2026_03_08_000010_create_roles_table.php
│   ├── 2026_03_08_000011_create_title_person_roles_table.php
│   ├── 2026_03_08_000012_create_reviews_table.php
│   ├── 2026_03_08_000013_create_title_audit_log_table.php
│   └── 2026_03_08_000014_create_db_objects.php        ← Indexes, Views, Triggers, Functions
└── seeders/
    ├── DatabaseSeeder.php
    ├── CountrySeeder.php
    ├── LanguageSeeder.php
    ├── RoleSeeder.php
    ├── UserSeeder.php
    └── TitleSeeder.php
```

### Lưu ý migration `create_db_objects.php`

```php
// Migration này dùng DB::unprepared() để tạo triggers, views, procedures
public function up(): void
{
    DB::unprepared(file_get_contents(database_path('sql/triggers.sql')));
    DB::unprepared(file_get_contents(database_path('sql/views.sql')));
    DB::unprepared(file_get_contents(database_path('sql/procedures.sql')));
}
```

Tách SQL phức tạp thành các file riêng trong `database/sql/`:

- `database/sql/triggers.sql`
- `database/sql/views.sql`
- `database/sql/procedures.sql`
