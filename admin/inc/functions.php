<?php
/**
 * Helper functions for CyberTrendHub E-commerce
 * Originally from CodeAstro "Ecommerce Website in PHP MySQL" by Hammad Hassan
 * Adapted for PostgreSQL compatibility
 */

/**
 * Get the next auto-increment ID for a table (PostgreSQL compatible).
 * Returns the next ID that will be assigned by the SERIAL column's sequence,
 * without actually consuming it. This is useful for naming uploaded files
 * before inserting a record (e.g., product-featured-{next_id}.jpg).
 *
 * @param PDO    $pdo        The PDO database connection
 * @param string $table_name The table name (e.g., 'tbl_product')
 * @return int The next auto-increment ID
 */
function get_next_id($pdo, $table_name)
{
    // --- SQLite driver ---
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $stmt = $pdo->query("SELECT seq FROM sqlite_sequence WHERE name = " . $pdo->quote($table_name));
        $last = $stmt ? (int) $stmt->fetchColumn() : 0;
        return $last + 1;
    }

    // --- PostgreSQL driver ---
    // Get the primary key column name for this table
    $stmt = $pdo->prepare(
        "SELECT column_name
         FROM information_schema.key_column_usage
         WHERE table_name = ?
           AND constraint_name LIKE '%pkey%'
         ORDER BY ordinal_position
         LIMIT 1"
    );
    $stmt->execute([$table_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Fallback: find the serial column name
        $stmt = $pdo->prepare(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_name = ? AND column_default LIKE 'nextval%'
             ORDER BY ordinal_position
             LIMIT 1"
        );
        $stmt->execute([$table_name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0;
        }
    }

    $column = $row['column_name'];

    // Build the sequence name: {table}_{column}_seq
    $seq_name = $table_name . '_' . $column . '_seq';

    // Get the last assigned value from the sequence and add 1.
    // COALESCE handles the case where no values have been assigned yet (NULL)
    $stmt = $pdo->prepare("SELECT COALESCE((SELECT last_value FROM {$seq_name}), 0) + 1");
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

/**
 * Get the real IP address of the client, checking various headers.
 *
 * @return string The real IP address
 */
function get_real_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For may contain multiple IPs; take the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Generate an HTML <option> tag with the 'selected' attribute if $selected
 * matches $value. Commonly used in dropdown selects in CodeAstro projects.
 *
 * Usage: textpilihan($current_value, $option_value, 'Display Text');
 *
 * @param string $selected The currently selected value
 * @param string $value    The value for this option
 * @param string $text     The display text
 * @return string HTML option tag
 */
function textpilihan($selected, $value, $text)
{
    $sel = ($selected == $value) ? 'selected="selected"' : '';
    echo "<option value=\"{$value}\" {$sel}>{$text}</option>";
}

/**
 * Get the next auto-increment ID for a table (alias for get_next_id).
 * Provided for backward compatibility with CodeAstro naming conventions.
 *
 * @param PDO    $pdo        The PDO database connection
 * @param string $table_name The table name
 * @return int The next auto-increment ID
 */
function get_ai_id($pdo, $table_name)
{
    return get_next_id($pdo, $table_name);
}
?>