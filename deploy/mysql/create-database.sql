-- Ejecutar en el servidor MySQL que ya usa integracorp-api.
-- Sustituye ESTE_SERVIDOR_MARKETING por la IP (privada) de la caja de tdg-marketing
-- y CLAVE_FUERTE por una contraseña larga.

CREATE DATABASE IF NOT EXISTS tdg_marketing
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'tdg_marketing'@'ESTE_SERVIDOR_MARKETING' IDENTIFIED BY 'CLAVE_FUERTE';
GRANT ALL PRIVILEGES ON tdg_marketing.* TO 'tdg_marketing'@'ESTE_SERVIDOR_MARKETING';
FLUSH PRIVILEGES;
