# 📊 Sales Tracker — ระบบบันทึกและสรุปยอดขาย

ระบบบันทึกรายการขายสินค้าประจำวัน พร้อมสรุปยอดขายรายเดือน แยกตามสินค้าและประเภทงาน รองรับการ Export PDF

---

## ✨ ฟีเจอร์หลัก

| ฟีเจอร์ | รายละเอียด |
|---------|------------|
| 📝 บันทึกการขาย | บันทึกรายการขายประจำวัน ระบุสินค้า ลูกค้า ประเภทงาน จำนวน และยอดขาย |
| 📦 จัดการสินค้า | เพิ่ม/แก้ไข/ลบสินค้า พร้อมดูยอดขายรวมแยกตามประเภทงาน |
| 👥 จัดการลูกค้า | เพิ่ม/แก้ไข/ลบข้อมูลลูกค้า พร้อมประวัติการซื้อ |
| 📊 รายงานรายเดือน | สรุปยอดขายรายสินค้า แยกประเภทงาน 1 (ขายเฉพาะเครื่อง) และ 2 (ขายพร้อมติดตั้ง) |
| 📄 Export PDF | ดาวน์โหลดรายงานเป็น PDF รองรับภาษาไทย |
| 🔍 ตัวกรอง | กรองรายการขายตามเดือน/ปี สินค้า และประเภทงาน |

---

## 🏗️ โครงสร้างโปรเจกต์

```
sales-tracker/
├── assets/css/          # Custom CSS
├── config/
│   └── database.php     # Database connection + .env loader
├── migrations/
│   └── 001_initial_schema.sql  # Database schema + seed
├── src/
│   ├── helpers.php      # Global helper functions
│   └── Controllers/
│       ├── SaleController.php
│       ├── ProductController.php
│       ├── CustomerController.php
│       └── ReportController.php
├── views/
│   ├── layout/          # Header + Footer templates
│   ├── sales/           # Sale list + form views
│   ├── products/        # Product list + form views
│   ├── customers/       # Customer list + form views
│   └── reports/         # Monthly report view
├── .env                 # Environment variables (ไม่ commit)
├── .env.example         # Template สำหรับ .env
├── composer.json        # PHP dependencies
├── docker-compose.yml   # Docker environment
├── Dockerfile
├── index.php            # Front controller (entry point)
└── migrate.php          # Database migration runner
```

---

## 🐳 การติดตั้งสำหรับ Development (Docker)

### ข้อกำหนด
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 4.x
- [Composer](https://getcomposer.org/) ≥ 2.x

### ขั้นตอน

**1. Clone หรือวาง project ไว้ในโฟลเดอร์ที่ต้องการ**

```bash
cd sales-tracker
```

**2. ตรวจสอบไฟล์ `.env`**

ไฟล์ `.env` สำหรับ Docker ถูกสร้างไว้แล้ว ค่าเริ่มต้นคือ:

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=sales_tracker
DB_USER=sales_user
DB_PASS=sales_password
```

**3. Build และ Start containers**

```bash
docker-compose up -d --build
```

**4. ติดตั้ง Composer dependencies**

```bash
docker-compose exec app composer install
```

**5. รัน Database Migration**

```bash
docker-compose exec app php migrate.php
```

**6. เปิดเบราว์เซอร์**

```
http://localhost:8080
```

### คำสั่งที่ใช้บ่อย

```bash
# ดู logs
docker-compose logs -f app

# หยุด containers
docker-compose down

# หยุดและลบ volume (database จะถูกลบด้วย)
docker-compose down -v

# รัน migration อีกครั้ง
docker-compose exec app php migrate.php

# เข้า shell ใน container
docker-compose exec app bash

# เข้า MySQL
docker-compose exec db mysql -u sales_user -psales_password sales_tracker
```

---

## 🖥️ การ Deploy บน XAMPP (Production)

### ข้อกำหนด
- [XAMPP](https://www.apachefriends.org/) พร้อม PHP 8.0+ และ MySQL/MariaDB
- [Composer](https://getcomposer.org/) (ดาวน์โหลดและติดตั้งบน Windows)

### ขั้นตอน

**1. วาง project ใน XAMPP htdocs**

```
C:\xampp\htdocs\sales-tracker\
```

**2. สร้างไฟล์ `.env`**

คัดลอกจาก `.env.example`:
```bash
copy .env.example .env
```

แก้ไขค่าใน `.env` ให้ตรงกับ XAMPP:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sales_tracker
DB_USER=root
DB_PASS=
APP_URL=http://localhost/sales-tracker
APP_TIMEZONE=Asia/Bangkok
```

> ⚠️ หาก XAMPP MySQL มีรหัสผ่าน root ให้ใส่ `DB_PASS` ด้วย

**3. สร้าง Database ใน phpMyAdmin**

- เปิด `http://localhost/phpmyadmin`
- คลิก **New** → ตั้งชื่อ database: `sales_tracker`
- Collation: `utf8mb4_unicode_ci`
- คลิก **Create**

**4. ติดตั้ง Composer dependencies**

เปิด Command Prompt ใน folder โปรเจกต์:

```bash
cd C:\xampp\htdocs\sales-tracker
composer install --no-dev --optimize-autoloader
```

> 💡 หากยังไม่ได้ติดตั้ง Composer บน Windows ดาวน์โหลดได้ที่ https://getcomposer.org/Composer-Setup.exe

**5. รัน Database Migration**

วิธีที่ 1 — ผ่าน Command Prompt:
```bash
php migrate.php
```

วิธีที่ 2 — ผ่านเบราว์เซอร์:
```
http://localhost/sales-tracker/migrate.php
```

**6. เปิดระบบ**

```
http://localhost/sales-tracker/
```

---

## 🔄 การรัน Migration

Migration system จะ:
- ตรวจสอบว่า migration ไหนยังไม่ได้รัน
- รันเฉพาะ migration ที่ยังไม่ได้รัน (ไม่รันซ้ำ)
- บันทึกผลลัพธ์ใน table `migrations`

```bash
# CLI
php migrate.php

# Docker
docker-compose exec app php migrate.php
```

หากต้องการเพิ่ม migration ใหม่ ให้สร้างไฟล์ใน `migrations/` โดยตั้งชื่อตาม pattern:

```
migrations/002_add_new_column.sql
migrations/003_create_another_table.sql
```

---

## 🗄️ Database Schema

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  customers  │     │    sales     │     │  products   │
├─────────────┤     ├──────────────┤     ├─────────────┤
│ id          │◄────│ customer_id  │────►│ id          │
│ name        │     │ product_id   │     │ name        │
│ phone       │     │ work_type_id │     │ created_at  │
│ note        │     │ sale_date    │     │ updated_at  │
│ created_at  │     │ quantity     │     └─────────────┘
│ updated_at  │     │ price        │
└─────────────┘     │ note         │     ┌─────────────┐
                    │ created_at   │     │ work_types  │
                    │ updated_at   │     ├─────────────┤
                    └──────────────┘     │ id          │
                           │             │ name        │
                           └────────────►│ 1: ขายเฉพาะเครื่อง│
                                         │ 2: ขายพร้อมติดตั้ง│
                                         └─────────────┘
```

---

## 📋 Routing

ระบบใช้ query string routing ผ่าน `index.php`:

| URL | หน้าที่ |
|-----|---------|
| `index.php` หรือ `?page=sales` | รายการขายประจำเดือน |
| `?page=sales&action=create` | บันทึกการขายใหม่ |
| `?page=sales&action=edit&id=X` | แก้ไขรายการขาย |
| `?page=reports` | รายงานสรุปรายเดือน |
| `?page=reports&action=pdf&month=M&year=Y` | ดาวน์โหลด PDF |
| `?page=products` | จัดการสินค้า |
| `?page=customers` | จัดการลูกค้า |

---

## 📦 Dependencies

| Package | Version | ใช้สำหรับ |
|---------|---------|----------|
| `tecnickcom/tcpdf` | ^6.6 | สร้าง PDF รองรับภาษาไทย |
| Bootstrap | 5.3 (CDN) | UI Framework |
| Bootstrap Icons | 1.11 (CDN) | Icons |

---

## 🔧 Environment Variables

| Variable | Default | คำอธิบาย |
|----------|---------|----------|
| `DB_HOST` | `localhost` | MySQL host (`db` สำหรับ Docker) |
| `DB_PORT` | `3306` | MySQL port |
| `DB_NAME` | `sales_tracker` | ชื่อ database |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | _(ว่าง)_ | MySQL password |
| `APP_URL` | `http://localhost:8080` | Base URL ของแอป |
| `APP_TIMEZONE` | `Asia/Bangkok` | PHP timezone |

---

## ❓ แก้ปัญหาที่พบบ่อย

### ❌ "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"
- ตรวจสอบว่า MySQL กำลังทำงาน (XAMPP Control Panel หรือ `docker-compose ps`)
- ตรวจสอบค่า `DB_HOST`, `DB_USER`, `DB_PASS` ในไฟล์ `.env`
- สำหรับ Docker: `DB_HOST` ต้องเป็น `db` (ชื่อ service ใน docker-compose)

### ❌ "กรุณาติดตั้ง Composer Dependencies ก่อน"
```bash
# Docker
docker-compose exec app composer install

# XAMPP
composer install
```

### ❌ PDF ไม่มีข้อความภาษาไทย
- ตรวจสอบว่า TCPDF ถูกติดตั้งแล้ว (`vendor/` มีโฟลเดอร์ `tecnickcom/`)
- รัน `composer install` ใหม่อีกครั้ง

### ❌ หน้าเว็บแสดง 500 Error บน XAMPP
- เปิด PHP error log ที่ `C:\xampp\php\logs\php_error_log`
- ตรวจสอบว่า PHP extension `pdo_mysql` เปิดใช้งานใน `php.ini`

### ❌ ไม่สามารถลบสินค้า/ลูกค้าได้
- ระบบป้องกันการลบข้อมูลที่มีรายการขายอ้างอิงอยู่
- ต้องลบรายการขายที่เกี่ยวข้องออกก่อน

---

## 📝 License

MIT License — Free to use and modify.