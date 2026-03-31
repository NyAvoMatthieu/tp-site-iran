-- ==========================================
-- CREATION BASE DE DONNEES
-- ==========================================

CREATE DATABASE iran_war;

-- Se connecter ensuite avec :
\c iran_war


-- ==========================================
-- TABLE TYPE_CONTENU
-- ==========================================

CREATE TABLE type_contenu (
    id SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);


-- ==========================================
-- TABLE CONTENU
-- ==========================================

CREATE TABLE contenu (
    id SERIAL PRIMARY KEY,
    id_type INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    details TEXT NOT NULL,
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    keywords TEXT,
    author_name VARCHAR(120),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_type_contenu
    FOREIGN KEY (id_type)
    REFERENCES type_contenu(id)
    ON DELETE RESTRICT
);


-- ==========================================
-- TABLE IMAGE
-- ==========================================

CREATE TABLE image (
    id SERIAL PRIMARY KEY,
    id_contenu INT NOT NULL,
    url TEXT NOT NULL,

    CONSTRAINT fk_contenu_image
    FOREIGN KEY (id_contenu)
    REFERENCES contenu(id)
    ON DELETE CASCADE
);


-- ==========================================
-- TABLE TAG
-- ==========================================

CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);


-- ==========================================
-- TABLE CONTENU_TAG
-- relation many-to-many
-- ==========================================

CREATE TABLE contenu_tag (
    id_contenu INT NOT NULL,
    id_tag INT NOT NULL,

    PRIMARY KEY (id_contenu, id_tag),

    CONSTRAINT fk_contenu
    FOREIGN KEY (id_contenu)
    REFERENCES contenu(id)
    ON DELETE CASCADE,

    CONSTRAINT fk_tag
    FOREIGN KEY (id_tag)
    REFERENCES tag(id)
    ON DELETE CASCADE
);


-- ==========================================
-- INDEX POUR PERFORMANCE
-- ==========================================

CREATE INDEX idx_contenu_type
ON contenu(id_type);

CREATE INDEX idx_slug
ON contenu(slug);

CREATE INDEX idx_image_contenu
ON image(id_contenu);

CREATE INDEX idx_tag_libelle
ON tag(libelle);

CREATE TABLE admin (
    id SERIAL PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    pswd VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);