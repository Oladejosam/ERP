USE ERP_db;

DELIMITER $$
CREATE TRIGGER trg_after_insert_invoice
AFTER INSERT ON invoices
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs(user_id, action, details)
    VALUES (NULL, 'invoice_created', CONCAT('Invoice created: ', NEW.invoice_number));
END$$

CREATE TRIGGER trg_after_update_inventory_stock
AFTER UPDATE ON inventory_items
FOR EACH ROW
BEGIN
    IF OLD.current_stock <> NEW.current_stock THEN
        INSERT INTO audit_logs(user_id, action, details)
        VALUES (NULL, 'inventory_stock_changed', CONCAT('Inventory item updated: ', NEW.item_code));
    END IF;
END$$
DELIMITER ;
