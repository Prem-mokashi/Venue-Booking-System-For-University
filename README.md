# 🏛️ VTU Venue Booking System

A comprehensive web-based venue booking system for **Visvesvaraya Technological University (VTU)** Belagavi campus. This system allows students and staff to book venues for events, conferences, seminars, and other activities with VTU ID verification. 🎓✨

## 🌟 Features

### 👤 User Features
- 🔐 **VTU ID Verification**: Secure registration with VTU ID validation against CSV database
- 🏢 **Venue Browsing**: View all available venues with detailed information
- 📅 **Smart Booking**: Book venues with date and time slot selection
- 🗓️ **Interactive Calendar**: View booking availability with color-coded calendar
- 📊 **Booking Management**: Track and manage your bookings
- 📄 **PDF Generation**: Download booking confirmations as PDF
- 📧 **Email Notifications**: Receive booking confirmations via email
- 📱 **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

### 👨‍💼 Admin Features
- 📈 **Dashboard**: Overview of all bookings and statistics
- ✅ **Booking Management**: Approve, reject, or cancel bookings
- 🏛️ **Venue Management**: Add, edit, or remove venues
- 👥 **User Management**: View and manage registered users
- 📊 **Excel Export**: Export booking data to Excel format
- 📬 **Email Notifications**: Send automated emails to users

### 🏢 Venue Features
- 🎭 **Dr. A P J Abdul Kalam Auditorium** (500 capacity)
- 🎪 **Jnana Samvada 1** (150 capacity)
- 🎪 **Jnana Samvada 2** (120 capacity)

## 🛠️ Technology Stack

- 🎨 **Frontend**: HTML5, CSS3, JavaScript
- ⚙️ **Backend**: PHP 7.4+
- 🗄️ **Database**: MySQL 5.7+
- 📚 **Libraries**: 
  - 🎨 Font Awesome 6.0 (Icons)
  - 📧 PHPMailer (Email)
  - 📄 TCPDF (PDF Generation)
  - 📊 PhpSpreadsheet (Excel Export)

## 📋 Prerequisites

- 🔧 **XAMPP** (or similar) with:
  - 🌐 Apache 2.4+
  - 🐘 PHP 7.4+ with extensions:
    - ✅ `mysqli`
    - ✅ `pdo_mysql`
    - 🖼️ `gd` (for image processing)
    - 📝 `mbstring`
    - 🔒 `openssl`
  - 🗄️ MySQL 5.7+

## 🚀 Installation

### 1️⃣ Clone or Download the Project
```bash
git clone <repository-url>
# OR download and extract the ZIP file
```

### 2️⃣ Move to XAMPP Directory
```bash
# Move the project folder to:
C:\xampp\htdocs\venue-booking-system
```

### 3️⃣ Database Setup
1. ▶️ Start XAMPP (Apache and MySQL)
2. 🌐 Open phpMyAdmin: `http://localhost/phpmyadmin`
3. 🆕 Create a new database named `venue_booking`
4. 📥 Import the database:
   - 👆 Click on `venue_booking` database
   - 📂 Go to "Import" tab
   - 📄 Choose file: `db.sql` or `backups/backup_venue_booking_*.sql`
   - ✅ Click "Go"

### 4️⃣ Configure Database Connection
Edit `api/config.php`:
```php
$host = 'localhost';
$dbname = 'venue_booking';
$username = 'root';
$password = ''; // Your MySQL password
```

### 5️⃣ Configure Email (Optional)
Edit `api/email_helper.php`:
```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->setFrom('your-email@gmail.com', 'VTU Venue Booking');
```

### 6️⃣ Setup VTU Data Files
The system includes sample VTU student data:
- 📍 Location: `data/vtu_data/students/vtu_students_mca.csv`
- 📋 Format: `USN,Student Name,Expiry_Date`

To add more data:
1. 📝 Create CSV files in the same format
2. 📂 Place in `data/vtu_data/students/` or `data/vtu_data/staff/`

### 7️⃣ Access the System
Open your browser and navigate to:
```
http://localhost/venue-booking-system/
```

## 👥 Default Credentials

### 👨‍💼 Admin Login
- 🌐 **URL**: `http://localhost/venue-booking-system/public/admin_login.php`
- 👤 **Username**: `admin`
- 🔑 **Password**: `admin123`

### 🧪 Test User Accounts
- 👤 **Username**: `malik` | 🔑 **Password**: Check database
- 👤 **Username**: `ramesh` | 🔑 **Password**: Check database

## 📁 Project Structure

```
venue-booking-system/
├── api/                          # Backend API files
│   ├── config.php               # Database configuration
│   ├── email_helper.php         # Email functionality
│   ├── excel_reader.php         # VTU ID verification
│   ├── verify_vtu_id.php        # VTU ID verification API
│   └── users.php                # User management API
├── public/                       # Public web files
│   ├── index.php                # Homepage
│   ├── login.php                # User login
│   ├── register_vtu.php         # User registration
│   ├── venues.php               # Venue listing
│   ├── venue_details.php        # Venue details
│   ├── book_venue.php           # Booking form
│   ├── user_dashboard.php       # User dashboard
│   ├── my_bookings.php          # User bookings
│   ├── calendar_api.php         # Calendar data API
│   ├── generate_booking_pdf.php # PDF generation
│   ├── images/                  # Image assets
│   └── .htaccess               # URL rewriting rules
├── admin/                        # Admin panel
│   ├── index.php                # Admin dashboard
│   ├── bookings.php             # Booking management
│   ├── venues.php               # Venue management
│   └── users.php                # User management
├── data/                         # Data files
│   └── vtu_data/                # VTU verification data
│       ├── students/            # Student CSV files
│       └── staff/               # Staff CSV files
├── backups/                      # Database backups
├── .htaccess                    # Root URL rewriting
├── db.sql                       # Database schema
└── README.md                    # This file
```

## 🎨 Key Features Explained

### 1️⃣ VTU ID Verification
- ✅ Users must verify their VTU ID during registration
- 📂 System checks against CSV files in `data/vtu_data/`
- 🔍 Validates name, ID, and expiry date
- 🚫 Prevents duplicate registrations

### 2️⃣ Booking System
- 📅 **Date Selection**: Choose event date (minimum 24 hours in advance)
- ⏰ **Time Slots**: Select start and end time (8 AM - 6 PM)
- 🏢 **Venue Selection**: Browse and select from available venues
- 📊 **Status Tracking**: Pending → Approved/Rejected
- 🛡️ **Conflict Prevention**: System prevents double bookings

### 3️⃣ Calendar View
- 🎨 **Color Coding**:
  - 🟢 Green: Available dates
  - 🔴 Red: Booked by others
  - 🔵 Blue: Your bookings
  - 🟡 Yellow: Today
- 🖱️ **Interactive**: Click dates to see booking details
- 📆 **Monthly Navigation**: Browse different months

### 4️⃣ Admin Panel
- 📊 **Dashboard**: Statistics and recent bookings
- ✅ **Booking Management**: Approve/reject with remarks
- 🏛️ **Venue Management**: Add/edit/delete venues
- 📥 **Excel Export**: Download booking reports
- 📧 **Email Notifications**: Automated status updates

## 🔧 Configuration

### 🖼️ Enable GD Extension (for PDF generation)
1. 📂 Open `php.ini` (in XAMPP: `C:\xampp\php\php.ini`)
2. 🔍 Find `;extension=gd`
3. ✏️ Remove the semicolon: `extension=gd`
4. 🔄 Restart Apache

### 📧 Email Configuration
For Gmail:
1. 🔐 Enable 2-Factor Authentication
2. 🔑 Generate App Password
3. ⚙️ Use App Password in `email_helper.php`

### 🔗 URL Rewriting
The system uses `.htaccess` for clean URLs:
- ✅ Ensure `mod_rewrite` is enabled in Apache
- 📂 Root `.htaccess` redirects to `public/` folder
- 🔌 API folder is accessible directly

## 📱 Responsive Design

The system is fully responsive and works on:
- 🖥️ **Desktop**: Full features with optimal layout
- 📱 **Tablet**: Adapted layout with touch-friendly controls
- 📲 **Mobile**: Simplified navigation with mobile-optimized forms

## 🔒 Security Features

- 🔐 **Password Hashing**: Using PHP `password_hash()`
- 🛡️ **SQL Injection Prevention**: PDO prepared statements
- 🚫 **XSS Protection**: Input sanitization with `htmlspecialchars()`
- 🔑 **CSRF Protection**: Session-based validation
- 👮 **Access Control**: Role-based permissions (User/Admin)
- ✅ **VTU ID Verification**: Prevents unauthorized registrations

## 📊 Database Schema

### 📋 Main Tables
- 👥 **users**: User accounts (students/staff)
- 👨‍💼 **admins**: Admin accounts
- 🏢 **venues**: Venue information
- 📅 **bookings**: Booking records

### 🔗 Key Relationships
- 📌 `bookings.user_id` → `users.id`
- 📌 `bookings.venue_id` → `venues.id`

## 🐛 Troubleshooting

### ⚠️ Common Issues

**1️⃣ "Network error" during VTU ID verification**
- ✅ Check if Apache is running
- 📄 Verify `.htaccess` files are present
- 🔧 Ensure `mod_rewrite` is enabled

**2️⃣ PDF generation fails**
- 🖼️ Enable GD extension in `php.ini`
- 📂 Check file permissions on `public/` folder
- 📚 Verify TCPDF library is installed

**3️⃣ Calendar not showing bookings**
- 👤 Check if user is logged in
- 🔌 Verify `calendar_api.php` is accessible
- 🔍 Check browser console for errors

**4️⃣ Email not sending**
- 📧 Verify SMTP credentials in `email_helper.php`
- 🔒 Check if `openssl` extension is enabled
- 🔄 Test with a different email provider

**5️⃣ Database connection error**
- 🗄️ Verify MySQL is running
- 🔑 Check credentials in `api/config.php`
- ✅ Ensure database `venue_booking` exists

## 📈 Future Enhancements

- [ ] 💳 Payment gateway integration
- [ ] 📱 SMS notifications
- [ ] 🌍 Multi-language support
- [ ] 🔍 Advanced search and filters
- [ ] 📊 Booking analytics and reports
- [ ] 📲 Mobile app (React Native)
- [ ] ⚡ Real-time availability updates
- [ ] ⭐ Venue rating and reviews

## 👨‍💻 Development

### 🏢 Adding New Venues
1. 🚀 Go to Admin Panel → Venues
2. ➕ Click "Add New Venue"
3. ✏️ Fill in details (name, location, capacity, etc.)
4. 🖼️ Upload venue image
5. 💾 Save

### 📊 Adding VTU Data
1. 📝 Create CSV file with format: `USN,Name,Expiry_Date`
2. 📂 Place in `data/vtu_data/students/` or `staff/`
3. 🔄 System automatically reads all CSV files

### 📧 Customizing Email Templates
Edit `api/email_helper.php`:
- ✏️ Modify HTML templates
- 👤 Change sender information
- 🎨 Customize email content

## 📄 License

This project is developed for **Visvesvaraya Technological University (VTU)** Belagavi. 🎓

## 🤝 Support

For issues or questions:
- 📧 **Email**: venues@example.ac.in
- 📞 **Phone**: +91-xxx-xxxxxxx
- 📍 **Location**: VTU Belagavi, Karnataka 590018

## 🙏 Acknowledgments

- 🏛️ **VTU Belagavi** for providing the requirements
- 🎨 **Font Awesome** for icons
- 📧 **PHPMailer** for email functionality
- 📄 **TCPDF** for PDF generation

---

**📦 Version**: 1.0.0  
**📅 Last Updated**: December 2024  
**🏛️ Developed for**: Visvesvaraya Technological University, Belagavi

---

## ⚡ Quick Start Commands

```bash
# 🚀 Start XAMPP
# Open XAMPP Control Panel and start Apache & MySQL

# 🌐 Access the application
http://localhost/venue-booking-system/

# 👨‍💼 Admin panel
http://localhost/venue-booking-system/public/admin_login.php

# 🗄️ phpMyAdmin
http://localhost/phpmyadmin
```

---

<div align="center">

### 🎉 **Happy Booking!** 🎉

Made with ❤️ for **VTU Belagavi** 🏛️

⭐ **Star this repo if you find it helpful!** ⭐

</div>
