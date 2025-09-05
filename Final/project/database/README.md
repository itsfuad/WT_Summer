# CrowdFund Database Setup

## Unified Database Structure

This folder contains a simplified, unified database setup with just 3 files:

### Files:

1. **`create_tables.php`** 
   - Creates the database and all table structures
   - Inserts default categories
   - Run this FIRST

2. **`generate_dummy_data.php`**
   - Creates admin account + sample data for testing
   - Run this AFTER create_tables.php

3. **`test.php`**
   - Tests database connectivity and structure

### Setup Instructions:

1. **First time setup:**
   ```
   http://localhost/Dev/WT_Summer/Final/project/database/create_tables.php
   ```

2. **Add sample data (for development/testing):**
   ```
   http://localhost/Dev/WT_Summer/Final/project/database/generate_dummy_data.php
   ```

### Admin Login:
- **Email:** admin@crowdfund.com
- **Password:** password

### What was removed:
- `setup.php` (redundant with create_tables.php)
- `create_db.php` (merged into create_tables.php)
- `schema.sql` (converted to PHP)
- `debug_admin.php` (no longer needed)

This unified approach eliminates redundancy and confusion!
