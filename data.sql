-- ==========================================
-- DONNEES DE TEST
-- ==========================================

-- TYPES DE CONTENU
INSERT INTO type_contenu (libelle) VALUES
('Actualité'),
('Analyse'),
('Chronologie'),
('Armes militaires'),
('Impact économique');


-- TAGS
INSERT INTO tag (libelle) VALUES
('Iran'),
('Israel'),
('Missiles'),
('Drones'),
('Petrole'),
('Geopolitique');


-- CONTENUS
INSERT INTO contenu (id_type, titre, slug, details)
VALUES
(1,
'Attaque de missiles en Iran',
'attaque-missiles-iran',
'Une attaque de missiles a été signalée dans plusieurs zones stratégiques.'
),

(2,
'Les drones militaires iraniens',
'drones-militaires-iraniens',
'Analyse des drones utilisés par les forces iraniennes.'
),

(3,
'Chronologie du conflit Iran Israël',
'chronologie-conflit-iran-israel',
'Résumé des événements majeurs du conflit.'
);


-- IMAGES
INSERT INTO image (id_contenu, url)
VALUES
(1, 'images/missile1.jpg'),
(1, 'images/missile2.jpg'),
(2, 'images/drone1.jpg'),
(3, 'images/map1.jpg');


-- RELATION CONTENU TAG
INSERT INTO contenu_tag (id_contenu, id_tag)
VALUES
(1,1),
(1,3),
(2,1),
(2,4),
(3,1),
(3,2),
(3,6);

INSERT INTO admin (login, pswd)
VALUES ('admin', '$2y$10$06uhmy/BV5X1.oO1CmdateLqp2RrrIVmHfkQfNSVGt8jL01x7As9u');
