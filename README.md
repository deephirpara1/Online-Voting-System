# VoteSecure — Online Voting System

A production-quality Online Voting System built with PHP 8, MySQL, Bootstrap 5, Chart.js, and XAMPP.

## Features

### Admin Panel
- 📊 Dashboard with live statistics and Chart.js charts
- 🗳️ Create, edit, delete, start, and end elections
- 👤 Manage candidates with photo upload, party, symbol, and manifesto
- 👥 Manage voters — create, edit, delete, enable/disable accounts
- 📈 View live voting statistics and final results
- 📥 Export election results to CSV
- 📋 Full audit log viewer
- 🔑 Secure password change

### Voter Portal
- 🗳️ View active elections and cast votes
- 🚫 One vote per election (enforced at DB + PHP level)
- 📊 View results for ended elections only
- 👤 View and update profile
- 🔑 Secure password change

### Security
- 🔐 Bcrypt password hashing (`password_hash()` / `password_verify()`)
- 🛡️ CSRF protection on all forms (token-per-session, regenerated after use)
- 💉 SQL injection prevention (PDO prepared statements throughout)
- 🚫 XSS prevention (`htmlspecialchars()` on all output)
- ⏱️ Login rate limiting (5 attempts per 15 minutes)
- 🕐 Session inactivity timeout (30 minutes)
- 🔒 Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- 📁 `.htaccess` protection for sensitive directories
- 📝 Complete audit trail for all actions

---

## Technology Stack

| Component      | Technology                        |
|----------------|-----------------------------------|
| Backend        | PHP 8+                            |
| Database       | MySQL 5.7+ / MariaDB 10.3+       |
| Local Server   | XAMPP                             |
| Frontend       | HTML5, CSS3, JavaScript           |
| CSS Framework  | Bootstrap 5.3                     |
| Icons          | Font Awesome 6                    |
| Charts         | Chart.js 4                        |
| Tables         | DataTables 1.13                   |
| Alerts         | SweetAlert2 11                    |
| Typography     | Inter (Google Fonts)              |

---

## Setup Instructions (XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed (PHP 8.0+ required)
- Apache and MySQL running in XAMPP Control Panel

### Step 1: Copy Project Files
Copy the entire `voting System` folder to your XAMPP htdocs directory:
```
C:\xampp\htdocs\voting System\
```

### Step 2: Create the Database
1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Browse to `voting System/database/voting_system.sql`
4. Click **Go** to import

Or create manually:
1. Click **SQL** tab in phpMyAdmin
2. Copy the contents of `database/voting_system.sql`
3. Paste and click **Go**

### Step 3: Configure Database Connection
Edit `config/db.php` if your MySQL credentials differ from defaults:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP has no password
```

### Step 4: Access the Application
Open your browser and navigate to:
```
http://localhost/voting System/
```

---

## Default Login Credentials

### Admin
| Field    | Value       |
|----------|-------------|
| Username | `admin`     |
| Password | `admin123`  |

### Test Voters
| Username     | Password     |
|--------------|--------------|
| `john_doe`   | `admin123`   |
| `jane_smith` | `admin123`   |
| `bob_wilson` | `admin123`   |

> ⚠️ **Important:** Change all default passwords after first login!

---

## Project Structure

```
voting System/
├── .htaccess                 # Root security rules
├── index.php                 # Landing page
├── config/
│   ├── .htaccess             # Block direct access
│   └── db.php                # PDO connection + constants
├── database/
│   ├── .htaccess             # Block direct access
│   └── voting_system.sql     # Full schema + seed data
├── includes/
│   ├── .htaccess             # Block direct access
│   ├── auth.php              # Sessions, CSRF, rate limiting
│   ├── functions.php         # Shared utilities
│   ├── header.php            # HTML head + CDN links
│   ├── footer.php            # JS scripts
│   ├── sidebar.php           # Admin sidebar
│   └── voter_sidebar.php     # Voter sidebar
├── admin/
│   ├── index.php             # Dashboard
│   ├── login.php             # Admin login
│   ├── logout.php            # Admin logout
│   ├── elections.php         # Election CRUD
│   ├── candidates.php        # Candidate CRUD
│   ├── voters.php            # Voter CRUD
│   ├── results.php           # Results + CSV export
│   ├── audit_logs.php        # Audit log viewer
│   ├── change_password.php   # Change password
│   └── process/
│       ├── auth_process.php
│       ├── election_process.php
│       ├── candidate_process.php
│       └── voter_process.php
├── voter/
│   ├── index.php             # Voter dashboard
│   ├── login.php             # Voter login
│   ├── logout.php            # Voter logout
│   ├── elections.php         # Active elections
│   ├── vote.php              # Ballot page
│   ├── profile.php           # View/edit profile
│   ├── results.php           # View results (ended only)
│   ├── change_password.php   # Change password
│   └── process/
│       ├── auth_process.php
│       └── vote_process.php
├── assets/
│   ├── css/style.css         # Custom stylesheet
│   ├── js/app.js             # Shared JavaScript
│   └── images/
└── uploads/
    ├── .htaccess             # Allow images only, block PHP
    ├── candidates/           # Candidate photos
    └── voters/               # Voter photos
```

---

## Database Schema

### Tables
| Table        | Purpose                              |
|--------------|--------------------------------------|
| `admins`     | Admin accounts                       |
| `voters`     | Voter accounts with status           |
| `elections`  | Elections with dates and status       |
| `candidates` | Candidates linked to elections       |
| `votes`      | Vote records with UNIQUE constraint  |
| `audit_logs` | Complete action audit trail          |

### Key Constraints
- `votes.UNIQUE(election_id, voter_id)` — prevents duplicate voting at DB level
- `candidates.election_id` → `elections.id` ON DELETE CASCADE
- `votes.candidate_id` → `candidates.id` ON DELETE CASCADE
- `elections.created_by` → `admins.id` ON DELETE SET NULL

---

## Testing Checklist

### Authentication
- [ ] Admin login with correct credentials → Dashboard
- [ ] Admin login with wrong password → Error message
- [ ] 6 failed admin logins → Rate limit message
- [ ] Voter login with correct credentials → Voter Dashboard
- [ ] Disabled voter login → "Account disabled" message
- [ ] Session expires after 30 min inactivity → Redirected to login
- [ ] Admin cannot access voter pages and vice versa
- [ ] Change password works for both roles

### Elections
- [ ] Create new election → Appears in list
- [ ] Edit election title/dates → Updates saved
- [ ] Start upcoming election → Status changes to "Active"
- [ ] End active election → Status changes to "Ended"
- [ ] Delete election with 0 votes → Deleted
- [ ] Delete election with votes → Blocked with error

### Candidates
- [ ] Add candidate with photo → Photo uploaded and displayed
- [ ] Add candidate without photo → Default avatar shown
- [ ] Edit candidate → Changes saved
- [ ] Delete candidate → Removed from list

### Voters
- [ ] Create voter → Auto-generated Voter ID (VTR-XXXXX)
- [ ] Edit voter → Changes saved
- [ ] Disable voter → Status badge changes to "Disabled"
- [ ] Disabled voter cannot login
- [ ] Delete voter → Removed

### Voting
- [ ] Voter sees only active elections
- [ ] Click "Cast Vote" → Candidate selection page
- [ ] Select candidate → Card highlights with checkmark
- [ ] Confirm vote → SweetAlert confirmation dialog
- [ ] Vote submitted → Success message
- [ ] Try voting again in same election → "Already voted" message
- [ ] Direct URL to vote page after voting → Redirected

### Results
- [ ] Admin can see results for all elections
- [ ] Voter can only see results for ended elections
- [ ] Charts display correctly with vote counts
- [ ] CSV export downloads with correct data
- [ ] Winner has crown icon in first place

### Security
- [ ] Access admin page without login → Redirected to login
- [ ] Submit form without CSRF token → Rejected
- [ ] Try SQL injection in login form → No effect
- [ ] Try `<script>alert(1)</script>` in inputs → Escaped, not executed
- [ ] Browse to `/config/db.php` directly → 403 Forbidden
- [ ] Browse to `/database/voting_system.sql` → 403 Forbidden
- [ ] Upload `.php` file as candidate photo → Rejected

---

## License

This project is for educational purposes. Built with ❤️ for learning PHP and web security.
#   O n l i n e - V o t i n g - S y s t e m  
 #   O n l i n e - V o t i n g - S y s t e m  
 #   O n l i n e - V o t i n g - S y s t e m  
 