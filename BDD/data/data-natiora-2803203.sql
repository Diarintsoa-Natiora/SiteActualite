-- =========================
-- USERS
-- =========================
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@mail.com', 'hashed_password', 'admin'),
('Redacteur', 'redac@mail.com', 'hashed_password', 'writer');

-- =========================
-- MEDIA
-- =========================
INSERT INTO media (file_path, alt_text, mime_type, size) VALUES
('uploads/iran1.jpg', 'Conflit en Iran', 'image/jpeg', 204800),
('uploads/iran2.jpg', 'Carte géopolitique Iran', 'image/jpeg', 305000);

-- =========================
-- CATEGORIES
-- =========================
INSERT INTO categories (name, slug) VALUES
('Actualités', 'actualites'),
('Economie', 'economie'),
('International', 'international');

-- =========================
-- TAGS
-- =========================
INSERT INTO tags (name, slug) VALUES
('iran', 'iran'),
('guerre', 'guerre'),
('petrole', 'petrole'),
('geopolitique', 'geopolitique');

-- =========================
-- ARTICLES
-- =========================
INSERT INTO articles 
(title, slug, content, meta_title, meta_description, featured_image_id, status, views, author_id, published_at)
VALUES
(
'Guerre en Iran : impacts économiques',
'guerre-iran-impacts-economiques',

'<h1>Guerre en Iran</h1>
<p>Le conflit en Iran a des impacts majeurs sur l économie mondiale.</p>
<h2>Conséquences</h2>
<p>Les prix du pétrole augmentent fortement.</p>',

'Guerre Iran économie',
'Analyse des impacts économiques de la guerre en Iran',

1,
'published',
120,
1,
NOW()
),

(
'Tensions internationales autour de l Iran',
'tensions-internationales-iran',

'<h1>Tensions internationales</h1>
<p>Plusieurs pays réagissent face à la situation en Iran.</p>',

'Tensions Iran',
'Réactions internationales face à la crise iranienne',

2,
'published',
85,
2,
NOW()
);

-- =========================
-- ARTICLE_CATEGORY
-- =========================
INSERT INTO article_category (article_id, category_id) VALUES
(1, 2), -- Economie
(1, 3), -- International
(2, 1), -- Actualités
(2, 3); -- International

-- =========================
-- ARTICLE_TAG
-- =========================
INSERT INTO article_tag (article_id, tag_id) VALUES
(1, 1), -- iran
(1, 2), -- guerre
(1, 3), -- petrole
(2, 1), -- iran
(2, 4); -- geopolitique

-- =========================
-- COMMENTS
-- =========================
INSERT INTO comments (article_id, author_name, content, status) VALUES
(1, 'Jean', 'Article très intéressant', 'approved'),
(1, 'Marie', 'Bonne analyse économique', 'approved'),
(2, 'Paul', 'Sujet important', 'approved');