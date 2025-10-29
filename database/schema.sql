DELIMITER $$

CREATE TRIGGER trg_generate_produto_id
BEFORE INSERT ON produtos
FOR EACH ROW
BEGIN
  DECLARE novo_id INT;

  IF NEW.id IS NULL THEN
    REPEAT
      SET novo_id = FLOOR(RAND() * 900000 + 100000); -- Gera número de 6 dígitos
    UNTIL (SELECT COUNT(*) FROM produtos WHERE id = novo_id) = 0
    END REPEAT;

    SET NEW.id = novo_id;
  END IF;
END$$

DELIMITER ;


CREATE TABLE IF NOT EXISTS `produtos` (
	`id` INT NOT NULL,
	`quantidade` INT NOT NULL,
	`referencia` VARCHAR(50) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`local` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`nome` VARCHAR(60) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	PRIMARY KEY (`id`) USING BTREE
);


CREATE TABLE IF NOT EXISTS vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- tabela de auditoria
CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actor VARCHAR(128) NULL,
  actor_ip VARCHAR(45) NULL,
  db_table VARCHAR(64) NOT NULL,
  action ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  row_id INT NULL,
  old_json JSON NULL,
  new_json JSON NULL,
  info VARCHAR(255) NULL,
  INDEX (db_table),
  INDEX (row_id),
  INDEX (event_time)
)



DELIMITER $$

-- produtos AFTER INSERT
CREATE TRIGGER trg_produtos_after_insert
AFTER INSERT ON produtos
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (actor, actor_ip, db_table, action, row_id, new_json)
  VALUES (COALESCE(NULLIF(@actor,''), USER()), COALESCE(NULLIF(@actor_ip,''), NULL),
          'produtos', 'INSERT', NEW.id,
          JSON_OBJECT('id', NEW.id, 'nome', NEW.nome, 'referencia', NEW.referencia, 'quantidade', NEW.quantidade, 'local', NEW.`local`));
END$$

-- produtos AFTER UPDATE
CREATE TRIGGER trg_produtos_after_update
AFTER UPDATE ON produtos
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (actor, actor_ip, db_table, action, row_id, old_json, new_json)
  VALUES (COALESCE(NULLIF(@actor,''), USER()), COALESCE(NULLIF(@actor_ip,''), NULL),
          'produtos', 'UPDATE', NEW.id,
          JSON_OBJECT('id', OLD.id, 'nome', OLD.nome, 'referencia', OLD.referencia, 'quantidade', OLD.quantidade, 'local', OLD.`local`),
          JSON_OBJECT('id', NEW.id, 'nome', NEW.nome, 'referencia', NEW.referencia, 'quantidade', NEW.quantidade, 'local', NEW.`local`));
END$$

-- produtos AFTER DELETE
CREATE TRIGGER trg_produtos_after_delete
AFTER DELETE ON produtos
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (actor, actor_ip, db_table, action, row_id, old_json)
  VALUES (COALESCE(NULLIF(@actor,''), USER()), COALESCE(NULLIF(@actor_ip,''), NULL),
          'produtos', 'DELETE', OLD.id,
          JSON_OBJECT('id', OLD.id, 'nome', OLD.nome, 'referencia', OLD.referencia, 'quantidade', OLD.quantidade, 'local', OLD.`local`));
END$$

-- vendas AFTER INSERT
CREATE TRIGGER trg_vendas_after_insert
AFTER INSERT ON vendas
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (actor, actor_ip, db_table, action, row_id, new_json, info)
  VALUES (COALESCE(NULLIF(@actor,''), USER()), COALESCE(NULLIF(@actor_ip,''), NULL),
          'vendas', 'INSERT', NEW.id,
          JSON_OBJECT('id', NEW.id, 'produto_id', NEW.produto_id, 'quantidade', NEW.quantidade, 'created_at', DATE_FORMAT(NEW.created_at, '%Y-%m-%d %H:%i:%s')),
          CONCAT('produto_id=', NEW.produto_id));
END$$

DELIMITER ;
