SELECT si.id, si.name,
       SUM(dr.quantity_requested) AS total_dispensed
FROM stock_items si
JOIN dispense_requests dr
ON dr.stock_item_id = si.id
WHERE dr.status = 'fulfilled'
GROUP BY si.id, si.name;
