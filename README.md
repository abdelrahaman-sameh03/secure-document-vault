# Secure Document Vault System

Secure Document Vault is a secure web-based platform that allows users to upload, store, encrypt, digitally sign, verify, download, and manage sensitive documents.

The system is designed to demonstrate modern security concepts such as authentication, authorization, password hashing, JWT, OAuth, Two-Factor Authentication, document encryption, digital signatures, integrity verification, HTTPS, and system activity logging.

---

## Features

### Authentication and User Management

- User registration and login
- Secure password hashing using bcrypt
- Password policy validation
- JWT-based authentication using secure cookies
- Logout functionality
- GitHub OAuth login
- Google OAuth login
- Two-Factor Authentication using TOTP QR code

### Role-Based Access Control

The system supports three user roles:

| Role | Permissions |
|---|---|
| Admin | Manage users, update roles, view system logs |
| Manager | Review and verify uploaded documents |
| User | Upload, view, download, verify, and delete own documents |

### Secure Document Management

- Upload documents through a web interface
- View uploaded documents and metadata
- Download documents securely
- Delete documents
- Validate file type and file size before upload

### Document Security

For every uploaded document, the system performs:

- SHA-256 hash generation
- RSA digital signature generation
- AES-256-GCM encryption before storage
- Integrity verification after upload
- Signature verification to detect tampering

### HTTPS and Secure Communication

- The system is configured to run using HTTPS on XAMPP
- HTTP requests are automatically redirected to HTTPS
- Local self-signed SSL certificates are used for development

### Admin System Logs

The Admin dashboard includes system logs for important actions such as:

- Login success and failure
- OAuth login
- Logout
- Document upload
- Document download
- Document deletion
- Integrity verification
- 2FA enable/disable
- Manager review actions
- Admin role updates

---

## Technologies Used

- PHP
- MySQL / MariaDB
- XAMPP
- HTML
- CSS
- JavaScript
- Bootstrap / Custom UI
- OpenSSL
- GitHub OAuth
- Google OAuth
- JWT
- bcrypt
- AES-256-GCM
- RSA Digital Signature
- SHA-256

---

## Project Structure

```text
secure_document_vault_xampp/
│
├── app/
│   ├── auth.php
│   ├── config.php
│   ├── crypto.php
│   ├── db.php
│   ├── jwt.php
│   ├── logger.php
│   ├── oauth.php
│   └── totp.php
│
├── public/
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── upload.php
│   ├── documents.php
│   ├── download.php
│   ├── verify_document.php
│   ├── admin.php
│   ├── manager.php
│   ├── profile.php
│   ├── logout.php
│   └── assets/
│
├── database/
│   └── secure_document_vault.sql
│
├── docs/
│   └── project documentation files
│
├── storage/
│   ├── documents/
│   └── keys/
│
├── ssl/
│   └── local SSL configuration files
│
├── .gitignore
└── README.md
```

---

## Requirements

Before running the project, make sure you have:

- XAMPP installed
- PHP 8 or higher
- MySQL / MariaDB
- Apache enabled
- OpenSSL PHP extension enabled
- cURL PHP extension enabled
- Git installed

---

## Installation and Setup

### 1. Clone the Repository

```bash
git clone https://github.com/abdelrahaman-sameh03/secure-document-vault.git
```

Move the project folder to:

```text
C:\xampp\htdocs\secure_document_vault_xampp
```

---

### 2. Start XAMPP Services

Open XAMPP Control Panel and start:

```text
Apache
MySQL
```

---

### 3. Import the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a new database:

```sql
secure_document_vault
```

Then import:

```text
database/secure_document_vault.sql
```

---

### 4. Configure Database Connection

Open:

```text
app/config.php
```

Make sure the database settings match your local XAMPP setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_document_vault');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## HTTPS Setup on XAMPP

The project is designed to run using HTTPS.

### 1. Generate Local SSL Certificate

Go to:

```text
ssl/
```

Run the certificate generation script if available.

The generated certificate files should be:

```text
ssl/localhost.crt
ssl/localhost.key
```

### 2. Configure Apache SSL

Open:

```text
C:\xampp\apache\conf\extra\httpd-ssl.conf
```

Set the certificate paths:

```apache
SSLCertificateFile "C:/xampp/htdocs/secure_document_vault_xampp/ssl/localhost.crt"
SSLCertificateKeyFile "C:/xampp/htdocs/secure_document_vault_xampp/ssl/localhost.key"
```

### 3. Enable HTTPS in the Project

Open:

```text
app/config.php
```

Make sure:

```php
define('APP_URL', getenv('APP_URL') ?: 'https://localhost/secure_document_vault_xampp/public');
define('HTTPS_ONLY', true);
```

### 4. Force HTTPS Redirect

Open:

```text
public/.htaccess
```

Make sure it contains:

```apache
RewriteEngine On

RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Restart Apache

Restart Apache from XAMPP.

Then open:

```text
https://localhost/secure_document_vault_xampp/public/
```

If the browser shows a security warning, click:

```text
Advanced → Proceed to localhost
```

This happens because the certificate is self-signed for local development.

---

## OAuth Setup

The system supports:

- GitHub OAuth
- Google OAuth

### GitHub OAuth

Create a GitHub OAuth App from:

```text
GitHub → Settings → Developer settings → OAuth Apps → New OAuth App
```

Use:

```text
Homepage URL:
https://localhost/secure_document_vault_xampp/public/

Authorization callback URL:
https://localhost/secure_document_vault_xampp/public/oauth_github_callback.php
```

Then add the credentials in:

```text
app/config.php
```

```php
define('GITHUB_CLIENT_ID', getenv('GITHUB_CLIENT_ID') ?: 'YOUR_GITHUB_CLIENT_ID');
define('GITHUB_CLIENT_SECRET', getenv('GITHUB_CLIENT_SECRET') ?: 'YOUR_GITHUB_CLIENT_SECRET');
```

### Google OAuth

Create OAuth credentials from Google Cloud Console.

Use this redirect URI:

```text
https://localhost/secure_document_vault_xampp/public/oauth_google_callback.php
```

Then add the credentials in:

```php
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
```

Important:

Do not upload real OAuth secrets to GitHub.

---

## Demo Accounts

Use these accounts for testing:

| Role | Email | Password |
|---|---|---|
| Admin | admin@vault.test | Admin@12345 |
| Manager | manager@vault.test | Manager@12345 |
| User | user@vault.test | User@12345 |

---

## How to Use the System

### User Workflow

1. Register a new account or login.
2. Upload a document.
3. The system validates the file type and size.
4. The system calculates a SHA-256 hash.
5. The hash is digitally signed using RSA.
6. The document is encrypted using AES-256-GCM.
7. The encrypted file is stored on the server.
8. The user can verify, download, or delete the document.

### Integrity Verification Workflow

When the user clicks verify:

1. The system decrypts the stored document.
2. A new SHA-256 hash is calculated.
3. The new hash is compared with the stored hash.
4. The RSA digital signature is verified.
5. If everything matches, the document is valid.
6. If the file was modified, verification fails.

---

## Tampering Test

To test integrity verification:

1. Upload a document.
2. Verify it from the website.
3. It should show as valid.
4. Go to:

```text
storage/documents/
```

5. Modify the encrypted stored file manually.
6. Run verification again from the website.
7. The system should detect the modification and show verification failed.

This proves that the system can detect document tampering after upload.

---

## Wireshark HTTPS Demonstration

The project includes HTTPS support to demonstrate secure communication.

For the Wireshark demo:

### HTTP Test

1. Open the project using HTTP.
2. Capture traffic using Wireshark.
3. Show that HTTP traffic may expose readable data.

### HTTPS Test

1. Open the project using HTTPS.
2. Capture traffic using Wireshark.
3. Show that HTTPS traffic is encrypted using TLS.

This demonstrates why HTTPS is important for secure web applications.

---

