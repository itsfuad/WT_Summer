# CrowdFund - Community Fundraising Platform

A comprehensive web-based crowdfunding platform that enables users to create, manage, and support fundraising campaigns. Built with PHP, MySQL, and modern web technologies to provide a seamless fundraising experience.

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Screenshots](#screenshots)
- [Technology Stack](#technology-stack)
- [Configuration](#configuration)
- [User Roles](#user-roles)

## 🎯 About

CrowdFund is a full-featured crowdfunding platform designed to connect project creators with potential backers. The platform provides a secure, user-friendly environment where fundraisers can showcase their projects and supporters can contribute to causes they believe in.

### What it does:

- **Campaign Creation**: Users can create detailed fundraising campaigns with rich descriptions, images, and funding goals
- **Community Features**: Like, comment, and share functionality to build engagement
- **Analytics Dashboard**: Real-time tracking of campaign performance and donor insights
- **Admin Management**: Comprehensive admin panel for platform oversight and moderation
- **Email Notifications**: Automated email system for OTP verification and campaign updates

## ✨ Features

### For Fundraisers
- 📝 **Campaign Management**: Create, edit, and manage fundraising campaigns
- 📊 **Analytics Dashboard**: Track donations, views, and engagement metrics
- 💰 **Goal Tracking**: Monitor progress towards funding goals
- 📧 **Donor Communication**: Engage with supporters through comments and updates
- 🎯 **Campaign Categories**: Organize campaigns by category for better discoverability

### For Backers
- 💳 **Easy Donations**: Simple and secure donation process
- 📋 **Donation History**: Track all contributions and supported campaigns
- ❤️ **Social Features**: Like and comment on campaigns
- 🔔 **Notifications**: Stay updated on campaign progress
- 👤 **Profile Management**: Manage personal information and donation preferences

### For Administrators
- 🛡️ **Content Moderation**: Review and manage reported content
- 🎪 **Featured Campaigns**: Promote outstanding campaigns
- ❄️ **Campaign Control**: Freeze or suspend campaigns when necessary
- 📊 **Platform Analytics**: Monitor overall platform performance
- 👥 **User Management**: Oversee user accounts and activities

### Security & Quality
- 🔐 **Authentication System**: Secure login with OTP-based password recovery
- 🛡️ **Data Protection**: Secure handling of sensitive user and financial data
- 📱 **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- ⚡ **Performance Optimized**: Fast loading times and efficient database queries

## 📸 Screenshots

### Homepage
*Screenshot of the main landing page showing featured campaigns and navigation*

<img width="750" height="738" alt="image" src="https://github.com/user-attachments/assets/fcd3c49e-e479-40c9-b82e-419cb6dcb901" />


### Campaign Creation
*Interface for creating new fundraising campaigns*

<img width="413" height="686" alt="image" src="https://github.com/user-attachments/assets/48bd6acb-2ad9-43b4-b1c4-ebb1244c9c28" />


### Campaign View
*Detailed campaign page with donation options and progress tracking*

<img width="501" height="742" alt="image" src="https://github.com/user-attachments/assets/3c051a82-75a5-43e3-b98f-60fce4099cd9" />


### Analytics Dashboard
*Fundraiser analytics showing campaign performance metrics*

<img width="1276" height="896" alt="image" src="https://github.com/user-attachments/assets/180bb676-c16a-4fa8-9a26-1ac718e6f9a6" />


### Backer Dashboard
*Backer's view of supported campaigns and donation history*

<img width="1229" height="890" alt="image" src="https://github.com/user-attachments/assets/3ae82357-9656-450b-b3fc-39d03cb374e7" />

*Baker's Analytics*

<img width="1402" height="891" alt="image" src="https://github.com/user-attachments/assets/7d06a472-30a8-455d-94e6-8de89210a59b" />


### Admin Panel
*Administrator interface for platform management*

<img width="1229" height="897" alt="image" src="https://github.com/user-attachments/assets/5974c3d4-908e-445a-956c-690d9b4c1515" />

### Login/Signup/
*Login View*

<img width="1606" height="919" alt="localhost_WT_Summer_Final_Project_login_view_index php" src="https://github.com/user-attachments/assets/879015e7-9bed-4736-bfad-353d30ad8e31" />

*Signup View*

<img width="1588" height="1139" alt="localhost_WT_Summer_Final_Project_signup_view_index php" src="https://github.com/user-attachments/assets/0b5ed03f-df2d-469d-a5ba-e1f42d3141d3" />


### Password reset with OTP
*Email Form*

<img width="1606" height="919" alt="localhost_WT_Summer_Final_Project_forgot_password_view_index php" src="https://github.com/user-attachments/assets/474c243f-8886-4bbc-8a30-8a6c1b612274" />

*Email Sent successfull*

<img width="1606" height="919" alt="localhost_WT_Summer_Final_Project_forgot_password_view_index php (1)" src="https://github.com/user-attachments/assets/e8f0138e-a362-47fb-8c53-865515de40ed" />

*Email Received*

<img width="939" height="605" alt="image" src="https://github.com/user-attachments/assets/57f5d5b3-d5f3-4706-8d93-7adc0f9c6702" />

*After verification*

<img width="1606" height="919" alt="localhost_WT_Summer_Final_Project_forgot_password_view_index php (3)" src="https://github.com/user-attachments/assets/49c4b7df-7187-4de3-88d0-c915aa3a973c" />

*After Reset*

<img width="1606" height="919" alt="localhost_WT_Summer_Final_Project_login_view_index php_reset=success" src="https://github.com/user-attachments/assets/1808783f-fb36-441a-a7a6-fa1a47fe9e0f" />


## 🛠️ Technology Stack

### Backend
- **PHP 8.0+**: Server-side scripting
- **MySQL 8.0+**: Database management
- **PDO**: Database abstraction layer
- **Custom MVC Architecture**: Organized code structure

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Modern styling with Flexbox/Grid
- **JavaScript (ES6+)**: Interactive functionality
- **Font Awesome**: Icon library
- **Chart.js**: Data visualization

### Infrastructure
- **XAMPP**: Local development environment
- **SMTP Integration**: Email delivery system
- **File Upload System**: Image handling for campaigns
- **Environment Configuration**: Secure configuration management

### Security Features
- **Password Hashing**: Secure password storage
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Session Management**: Secure user sessions

## ⚙️ Configuration

### Environment Variables

Create a `.env` file in the project root with the following configuration:

```env
# SMTP Configuration
SMTP_HOST=mail.smtp2go.com
SMTP_PORT=465
SMTP_USERNAME=your_username
SMTP_PASSWORD=your_password
SMTP_FROM_EMAIL=support@yoursite.com
SMTP_FROM_NAME=CrowdFund Support
```

### Database Setup

Run the database setup scripts:

```bash
# Create database and tables
php database_setup/create_db.php

# Generate sample data (optional)
php database_setup/generate_dummy_data.php

# Test database connection
php database_setup/test.php
```

## 👥 User Roles

### Common Features (Available to All Users)
1. **User Registration** - Sign up for an account with role selection
2. **User Login** - Secure authentication with session management
3. **User Logout** - Safe session termination and cleanup
4. **Password Reset** - OTP-based password recovery via email
5. **Profile Management** - View, edit, and update personal information
6. **Browse Campaigns** - Access and explore all public fundraising campaigns

### Guest (Non-authenticated Users)
1. **Browse Public Campaigns** - View all active campaigns without registration
2. **View Campaign Details** - Access complete campaign information and comments
3. **Search and Filter Campaigns** - Find campaigns by keywords, category, or status
4. **View Campaign Statistics** - Monitor progress, backers count, and days remaining
5. **View Public Profiles** - Access fundraiser and backer public profile pages
6. **Social Sharing** - Share campaign links on social media platforms

### Fundraiser
1. **Create Campaigns** - Design new fundraising campaigns with media uploads
2. **Edit Campaign Details** - Modify campaign information, descriptions, and images
3. **Campaign Analytics** - Access detailed performance metrics with interactive charts
4. **Track Campaign Performance** - Monitor donations, backers, engagement, and goal progress
5. **Donor Communication** - Engage with supporters through comments and campaign updates
6. **Campaign Status Management** - Organize campaigns by categories and track completion status

### Backer
1. **Make Donations** - Contribute to campaigns with optional anonymity and personalized messages
2. **Donation History** - Comprehensive analytics dashboard showing contribution patterns
3. **Track Supported Campaigns** - Monitor progress of all backed campaigns with filtering options
4. **Social Engagement** - Like campaigns and participate in comment discussions
5. **Donation Analytics Dashboard** - Visual charts showing donation trends and category breakdown
6. **Campaign Filtering by Donation Status** - Sort and filter campaigns by donation date and amount

### Administrator
1. **Content Moderation** - Review and manage reported campaigns and user comments
2. **Feature/Unfeature Campaigns** - Promote outstanding campaigns on platform homepage
3. **Freeze/Unfreeze Campaigns** - Suspend campaigns that violate platform policies
4. **Platform Analytics** - Monitor comprehensive platform statistics and user activity
5. **User Management** - Oversee user accounts, roles, and platform-wide activities
6. **Report Management** - Handle user reports and take appropriate moderation actions
