-- Schema khởi đầu cho Đề tài 37: Podcast / Media Library
-- Tạo database và tài khoản bằng Docker Compose/.env; file này chỉ tạo bảng.

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS episodes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description TEXT NULL,
    audio_path VARCHAR(500) NOT NULL,
    cover_path VARCHAR(500) NULL,
    duration_seconds INT UNSIGNED NULL,
    episode_number INT UNSIGNED NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_episodes_slug (slug),
    KEY idx_episodes_status_published (status, published_at),
    KEY idx_episodes_category (category_id),
    CONSTRAINT fk_episodes_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Mỗi dòng biểu thị một lượt bắt đầu phát. Không lưu IP trong schema cơ bản.
CREATE TABLE IF NOT EXISTS play_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    episode_id BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    seconds_listened INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_play_events_episode_started (episode_id, started_at),
    CONSTRAINT fk_play_events_episode
        FOREIGN KEY (episode_id) REFERENCES episodes (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Lượt phát theo tập:
-- SELECT e.id, e.title, COUNT(p.id) AS play_count
-- FROM episodes e LEFT JOIN play_events p ON p.episode_id = e.id
-- GROUP BY e.id, e.title ORDER BY play_count DESC;
