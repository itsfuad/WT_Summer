# CrowdFund Platform Setup Instructions

## Prerequisites
- XAMPP (PHP 7.4+ and MySQL)
- Web browser
- Text editor (optional)

## Installation Steps

### 1. Start XAMPP Services
- Start **Apache** and **MySQL** services in XAMPP Control Panel
- Make sure both services are running (green status)

### 2. Database Setup
1. Open your web browser and navigate to: `http://localhost/Dev/WT_Summer/Final/project/database/`
2. First run: `setup.php` - This will create the database and tables
3. Then run: `generate_dummy_data.php` - This will populate the database with sample data

### 3. Access the Platform
Navigate to: `http://localhost/Dev/WT_Summer/Final/project/home/view/index.php`

## Default Login Credentials

### Admin Account
- **Email:** admin@crowdfund.com
- **Password:** admin123

### Sample Fundraiser Account
- **Email:** techinnovatorsllc@fundraiser.com
- **Password:** password123

### Sample Backer Account
- **Email:** john.smith@backer.com
- **Password:** password123

## Features Implemented

### ✅ Database Integration
- MySQL database with proper schema
- User management (Admin, Fundraiser, Backer roles)
- Fund/Campaign management
- Donation tracking
- Comment system
- Categories and reporting

### ✅ Homepage (Guest View)
- Displays top 6 featured funds from database
- Real-time statistics (amount raised, backers, days left)
- Progress bars showing funding percentage
- Category icons and colors
- Responsive design

### 🚧 Next Steps to Implement
1. **Authentication System**
   - Login/Logout functionality
   - Session management
   - Role-based access control

2. **Admin Dashboard**
   - Overview of all funds
   - Analytics and reports
   - User management
   - Fund approval/removal

3. **Fundraiser Dashboard**
   - Create new funds
   - Manage existing funds
   - View analytics
   - Respond to comments

4. **Backer Dashboard**
   - Browse and search funds
   - Make donations
   - View donation history
   - Comment on funds

## Database Structure

### Main Tables
- `users` - User accounts (admin, fundraiser, backer)
- `funds` - Fundraising campaigns
- `donations` - Donation records
- `comments` - User comments on funds
- `categories` - Fund categories
- `reports` - Content reporting system

### Sample Data Generated
- 1 Admin account
- 15 Fundraiser accounts
- 20 Backer accounts
- 20 Fund campaigns with real descriptions
- Random donations and comments
- 8 Categories with appropriate icons

## File Structure
```
project/
├── config/
│   └── database.php          # Database connection
├── database/
│   ├── schema.sql           # Database structure
│   ├── setup.php            # Database creation script
│   └── generate_dummy_data.php # Sample data generator
├── includes/
│   └── functions.php        # Helper functions and classes
├── home/
│   ├── view/
│   │   └── index.php        # Homepage (updated to use database)
│   └── css/
│       └── style.css        # Homepage styles
├── login/
├── signup/
├── admin/
├── fundraiser/ (will be renamed to fundraiser)
├── backer/
└── shared/
    └── fontawesome/         # Icons
```

## Troubleshooting

### Database Connection Issues
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database name is `crowdfund_db`

### Page Not Loading
- Check file paths are correct
- Ensure Apache is running
- Clear browser cache

### No Data Showing
- Run the database setup scripts first
- Check if dummy data was generated successfully
- Verify database tables exist in phpMyAdmin

## Next Development Phase
The next phase will involve implementing the authentication system and creating role-specific dashboards for Admin, Fundraiser, and Backer users.
