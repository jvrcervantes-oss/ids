-- =====================================================================
--  IDS Fincas — Portal de clientes · Esquema MySQL
--  Codificación: utf8mb4 (soporta acentos, ñ, emojis)
--  Ejecutar una vez en la base de datos de Hostinger (phpMyAdmin → Importar)
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
--  USUARIOS
--  Sin auto-registro: los crea el admin. La contraseña la fija el propio
--  usuario vía enlace con token (set_password_token).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(120)  NOT NULL,
  email               VARCHAR(190)  NOT NULL,
  password_hash       VARCHAR(255)  DEFAULT NULL,            -- NULL hasta que fija contraseña
  rol                 ENUM('user','admin') NOT NULL DEFAULT 'user',
  activo              TINYINT(1)    NOT NULL DEFAULT 1,
  set_password_token  CHAR(64)      DEFAULT NULL,            -- hash del token de alta/reset
  token_expira        DATETIME      DEFAULT NULL,
  last_login          DATETIME      DEFAULT NULL,
  created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  COMUNIDADES DE VECINOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS communities (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(160)  NOT NULL,
  direccion   VARCHAR(255)  DEFAULT NULL,
  descripcion TEXT          DEFAULT NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  RELACIÓN N:N  usuario <-> comunidad
--  (un usuario puede pertenecer a varias comunidades)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_communities (
  user_id      INT UNSIGNED NOT NULL,
  community_id INT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, community_id),
  KEY idx_uc_community (community_id),
  CONSTRAINT fk_uc_user      FOREIGN KEY (user_id)      REFERENCES users(id)       ON DELETE CASCADE,
  CONSTRAINT fk_uc_community FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  CATEGORÍAS DE DOCUMENTOS (editables desde el admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doc_categories (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  orden  INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cat_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  DOCUMENTOS
--  El archivo real se guarda en /private/uploads con nombre UUID.
--  Nunca se sirve por URL directa: solo vía download.php con control de acceso.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  community_id      INT UNSIGNED NOT NULL,
  category_id       INT UNSIGNED DEFAULT NULL,
  titulo            VARCHAR(200) NOT NULL,
  descripcion       VARCHAR(500) DEFAULT NULL,
  filename_real     VARCHAR(80)  NOT NULL,          -- UUID.ext en disco
  filename_original VARCHAR(255) NOT NULL,          -- nombre que ve el usuario al descargar
  filesize          INT UNSIGNED NOT NULL DEFAULT 0,
  mime              VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
  uploaded_by       INT UNSIGNED DEFAULT NULL,
  uploaded_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_doc_community (community_id),
  KEY idx_doc_category (category_id),
  CONSTRAINT fk_doc_community FOREIGN KEY (community_id) REFERENCES communities(id)   ON DELETE CASCADE,
  CONSTRAINT fk_doc_category  FOREIGN KEY (category_id)  REFERENCES doc_categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_doc_uploader  FOREIGN KEY (uploaded_by)  REFERENCES users(id)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  AUDITORÍA  (logins, descargas, acciones admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED DEFAULT NULL,
  accion     VARCHAR(60)  NOT NULL,        -- login, login_fail, download, doc_upload, user_create...
  target     VARCHAR(255) DEFAULT NULL,
  ip         VARCHAR(45)  DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  INTENTOS DE LOGIN  (rate limiting / anti fuerza bruta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(190) DEFAULT NULL,
  ip         VARCHAR(45)  DEFAULT NULL,
  exito      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_att_ip (ip, created_at),
  KEY idx_att_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SEMILLA: categorías por defecto (editables luego en el admin)
-- ---------------------------------------------------------------------
INSERT INTO doc_categories (nombre, orden) VALUES
  ('Actas',        1),
  ('Presupuestos', 2),
  ('Contratos',    3),
  ('Juntas',       4),
  ('Normativa',    5),
  ('Otros',        6)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
