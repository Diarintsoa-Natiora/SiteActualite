-- =========================
-- USERS
-- =========================
INSERT INTO users (name, email, password, role ,is_hashed) VALUES
('Admin', 'admin@mail.com', 'hashed_password', 'admin' , false),
('Redacteur', 'redac@mail.com', 'hashed_password', 'writer' ,false);
