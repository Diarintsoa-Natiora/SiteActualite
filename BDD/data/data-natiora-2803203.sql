-- =========================
-- USERS
-- =========================
INSERT INTO users (name, email, password, role ,is_hashed) VALUES
('Admin', 'admin@mail.com', 'hashed_password', 'admin' , false),
('Redacteur', 'redac@mail.com', 'hashed_password', 'writer' ,false);

-- =========================
-- ARTICLES
-- =========================
INSERT INTO articles (id, title, slug, content, meta_title, meta_description, status, views, author_id, published_at) VALUES
(1,
 'Armada americaine verrouille le detroit d''Ormuz',
 'armada-americaine-detroit-hormuz',
 '<p>Le porte-avions Gerald R. Ford et son escorte croisent au large de l''Iran pour assurer la liberte de navigation et tenir l''oeil sur les missiles iraniens.</p>\n<p>La pont d''envol charge de F/A-18 est pret a intervenir contre toute action contre les navires marchands reliant l''energie du Golfe a l''Europe.</p>',
 'Armada americaine au large de l''Iran',
 'Le Gerald R. Ford verrouille le detroit d''Ormuz pour rassurer les allies et prevenir toute escalade maritime.',
 'published',
 0,
 1,
 '2026-03-31 04:30:00'
),
(2,
 'Secours israeliens dans les degats des derniers raids',
 'secours-israeliens-degats-raids',
 '<p>Les equipes de secours travaillent encore dans les ruines de Tel-Aviv apres la salve de missiles et de drones tires depuis la region.</p>\n<p>Les premieres evaluations parlent de quartiers entierement pulverises tandis que les habitants restent refugies dans les abris.</p>',
 'Israel tente de reconstruire apres les frappes',
 'Les sauveteurs fouillent les ruines de Tel-Aviv apres les attaques nocturnes et recherchent encore des survivants.',
 'published',
 0,
 2,
 '2026-03-31 04:35:00'
),
(3,
 'Peuple iranien mobilise pour soutenir l''effort de guerre',
 'peuple-iranien-mobilise-effort-guerre',
 '<p>Des milliers de partisans se rassemblent a Teheran pour afficher leur soutien aux operations iraniennes contre les forces occidentales.</p>\n<p>Les leaders religieux appellent a maintenir la mobilisation sur les fronts militaire, economique et diplomatique.</p>',
 'La mobilisation populaire iranienne continue',
 'Rassemblement massif dans les rues iraniennes pour soutenir l''axe de resistance face a Washington et Tel-Aviv.',
 'published',
 0,
 2,
 '2026-03-31 04:40:00'
);

-- =========================
-- MEDIA
-- =========================
INSERT INTO media (file_path, alt_text, mime_type, size, id_article) VALUES
('/assets/upload/ArmadaAmericain.png', 'Armada americaine verrouille le detroit d''Ormuz', 'image/png', 220230, 1),
('/assets/upload/DegatIsrael.png', 'Secours israeliens dans les degats des derniers raids', 'image/png', 268229, 2),
('/assets/upload/peupleIranien.png', 'Peuple iranien mobilise pour soutenir l''effort de guerre', 'image/png', 249331, 3);
