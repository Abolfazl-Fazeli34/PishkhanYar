<?php
declare(strict_types=1);
require_once '../config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('../account.php');
} else {
    redirect('../admin.php');
}
