<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
if (!defined('FASP_TXN_TABLE')) define('FASP_TXN_TABLE', $wpdb->prefix . 'fasp_transactions');

/**
 * Admin → Transactions list (filter + CSV export)
 */
