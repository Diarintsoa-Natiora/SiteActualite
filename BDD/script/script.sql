CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','editor','writer') DEFAULT 'writer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,

    content LONGTEXT NOT NULL,

    meta_title VARCHAR(255),
    meta_description TEXT,


    status ENUM('draft','published','delete') DEFAULT 'draft',

    views INT DEFAULT 0,

    author_id INT,

    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    mime_type VARCHAR(100),
    size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_article INT ,
    FOREIGN KEY (id_article) REFERENCES articles(id)
);


CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE article_category (
    article_id INT,
    category_id INT,

    PRIMARY KEY (article_id, category_id),

    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);


CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    slug VARCHAR(50) UNIQUE
);

CREATE TABLE article_tag (
    article_id INT,
    tag_id INT,

    PRIMARY KEY (article_id, tag_id),

    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    author_name VARCHAR(100),
    content TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);