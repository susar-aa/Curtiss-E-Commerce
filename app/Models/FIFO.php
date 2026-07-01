<?php
class FIFO {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Failsafe DDL migration to create FIFO-required tables and columns
     */
    private function initDatabase() {
        // Handled centrally by MigrationManager
    }

    /**
     * Record a new stock receipt batch from GRN
     */
    public function recordReceipt($itemId, $variationOptionId, $grnId, $quantity, $unitCost) {
        $this->db->query("INSERT INTO stock_batches (item_id, variation_option_id, grn_id, quantity_received, quantity_remaining, unit_cost) 
                          VALUES (:item_id, :variation_option_id, :grn_id, :qty, :qty, :cost)");
        $this->db->bind(':item_id', $itemId);
        $this->db->bind(':variation_option_id', $variationOptionId ? $variationOptionId : null);
        $this->db->bind(':grn_id', $grnId);
        $this->db->bind(':qty', $quantity);
        $this->db->bind(':cost', $unitCost);
        $this->db->execute();
    }

    /**
     * Deplete stock using First-In First-Out (FIFO) method.
     * Returns the average cost per unit for this depletion.
     */
    public function depleteStock($itemId, $variationOptionId, $quantityNeeded, $invoiceItemId = null, $salesInvoiceItemId = null) {
        $quantityNeeded = floatval($quantityNeeded);
        if ($quantityNeeded <= 0) return 0.00;

        $remainingNeeded = $quantityNeeded;
        $totalCost = 0.00;

        // Query oldest available stock batches for this item/variation
        if ($variationOptionId) {
            $this->db->query("SELECT * FROM stock_batches 
                              WHERE item_id = :item_id AND variation_option_id = :var_id AND quantity_remaining > 0 
                              ORDER BY created_at ASC, id ASC");
            $this->db->bind(':item_id', $itemId);
            $this->db->bind(':var_id', $variationOptionId);
        } else {
            // If variation option is NULL or 0, query batches where variation is NULL or 0
            $this->db->query("SELECT * FROM stock_batches 
                              WHERE item_id = :item_id AND (variation_option_id IS NULL OR variation_option_id = 0) AND quantity_remaining > 0 
                              ORDER BY created_at ASC, id ASC");
            $this->db->bind(':item_id', $itemId);
        }

        $batches = $this->db->resultSet() ?: [];

        foreach ($batches as $batch) {
            $batchRemaining = floatval($batch->quantity_remaining);
            $take = min($remainingNeeded, $batchRemaining);

            if ($take <= 0) continue;

            // Deduct from batch
            $this->db->query("UPDATE stock_batches SET quantity_remaining = quantity_remaining - :take WHERE id = :id");
            $this->db->bind(':take', $take);
            $this->db->bind(':id', $batch->id);
            $this->db->execute();

            // Record depletion linkage
            $this->db->query("INSERT INTO invoice_item_batches (invoice_item_id, sales_invoice_item_id, stock_batch_id, quantity, unit_cost) 
                              VALUES (:inv_item_id, :sales_inv_item_id, :batch_id, :qty, :cost)");
            $this->db->bind(':inv_item_id', $invoiceItemId);
            $this->db->bind(':sales_inv_item_id', $salesInvoiceItemId);
            $this->db->bind(':batch_id', $batch->id);
            $this->db->bind(':qty', $take);
            $this->db->bind(':cost', $batch->unit_cost);
            $this->db->execute();

            $totalCost += ($take * floatval($batch->unit_cost));
            $remainingNeeded -= $take;

            if ($remainingNeeded <= 0.0001) {
                break;
            }
        }

        // Fallback if there is still remaining quantity needed but no more batches exist in DB
        // (e.g. initial stock imported via CSV or migrated prior to FIFO)
        if ($remainingNeeded > 0) {
            // Fetch fallback cost from items table
            $this->db->query("SELECT cost, cost_price FROM items WHERE id = :id");
            $this->db->bind(':id', $itemId);
            $itemRow = $this->db->single();
            $fallbackCost = 0.00;
            if ($itemRow) {
                $fallbackCost = floatval($itemRow->cost > 0 ? $itemRow->cost : ($itemRow->cost_price > 0 ? $itemRow->cost_price : 0.00));
            }
            $totalCost += ($remainingNeeded * $fallbackCost);
        }

        // Calculate the average cost at time of sale
        $avgCost = $totalCost / $quantityNeeded;

        // Save this average cost in the invoice item table
        if ($invoiceItemId) {
            $this->db->query("UPDATE invoice_items SET cost_at_sale = :cost WHERE id = :id");
            $this->db->bind(':cost', $avgCost);
            $this->db->bind(':id', $invoiceItemId);
            $this->db->execute();
        } elseif ($salesInvoiceItemId) {
            $this->db->query("UPDATE sales_invoice_items SET cost_at_sale = :cost WHERE id = :id");
            $this->db->bind(':cost', $avgCost);
            $this->db->bind(':id', $salesInvoiceItemId);
            $this->db->execute();
        }

        return $avgCost;
    }

    /**
     * Revert all FIFO depletions linked to a given invoice item
     */
    public function revertDepletion($invoiceItemId = null, $salesInvoiceItemId = null) {
        if ($invoiceItemId) {
            $this->db->query("SELECT * FROM invoice_item_batches WHERE invoice_item_id = :id");
            $this->db->bind(':id', $invoiceItemId);
        } elseif ($salesInvoiceItemId) {
            $this->db->query("SELECT * FROM invoice_item_batches WHERE sales_invoice_item_id = :id");
            $this->db->bind(':id', $salesInvoiceItemId);
        } else {
            return;
        }

        $linkages = $this->db->resultSet() ?: [];

        foreach ($linkages as $link) {
            // Restore batch quantity remaining
            $this->db->query("UPDATE stock_batches SET quantity_remaining = quantity_remaining + :qty WHERE id = :id");
            $this->db->bind(':qty', $link->quantity);
            $this->db->bind(':id', $link->stock_batch_id);
            $this->db->execute();
        }

        // Delete linkages
        if ($invoiceItemId) {
            $this->db->query("DELETE FROM invoice_item_batches WHERE invoice_item_id = :id");
            $this->db->bind(':id', $invoiceItemId);
        } else {
            $this->db->query("DELETE FROM invoice_item_batches WHERE sales_invoice_item_id = :id");
            $this->db->bind(':id', $salesInvoiceItemId);
        }
        $this->db->execute();
    }
}
