CREATE DATABASE IF NOT EXISTS gestion_commerciale
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE gestion_commerciale;

DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS sorties;
DROP TABLE IF EXISTS entrees;
DROP TABLE IF EXISTS produits;

CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL UNIQUE,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entrees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    date_mouvement DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrees_produit FOREIGN KEY (produit_id) REFERENCES produits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE sorties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    date_mouvement DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sorties_produit FOREIGN KEY (produit_id) REFERENCES produits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    date_mouvement DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_commandes_produit FOREIGN KEY (produit_id) REFERENCES produits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

INSERT INTO produits (nom, stock) VALUES
('Sucre 25 kg', 0),
('Riz 50 kg', 0),
('Huile 5 L', 0);
