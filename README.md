# TUV EAST — Inspection Management System

Web application for the TUV EAST inspection workflow:
**Client Request → Admin Assignment → Inspector Job / Sub-job → Checklist → QC Review → Certificate & Sticker**,
with four portals (Admin, Inspector, QC, Customer) and controlled documents that reproduce
F-7.5.1-CL 01 (Boom Truck checklist) and F-7.5.1-06 (MPI report) exactly.

Full specification (requirements, workflow diagram, schema, API, permissions, plan, open questions):
[`docs/SPECIFICATION.md`](docs/SPECIFICATION.md)

## Requirements
- PHP 8.0+ with `pdo_mysql`, `gd`, `mbstring`, `fileinfo`, `zip` (XAMPP default)
- MariaDB 10.4+ / MySQL 8+
- Apache with `mod_rewrite` and `AllowOverride All` (XAMPP default)
- Composer dependencies are already in `vendor/` (dompdf 3.1, chillerlan/php-qrcode 5)

## Install (XAMPP)
1. Start Apache and MySQL from the XAMPP control panel.
2. Check DB credentials in `config/config.php` (default `root` / empty password, database `tuvsa_ims`).
   For another environment create `config/config.local.php` returning only the keys to override.
3. Create the database with demo data:
   ```
   php database/install.php
   ```
   or reference data + admin only: `php database/install.php --no-demo`
   (**drops and recreates** the IMS tables).
4. Open http://localhost/tuvsa/

**Upgrading an existing database** (keeps all data): `php database/migrate.php` — adds new inspection types /
checklist forms from `database/seed_templates.php`; a changed checklist is saved as a new template version.

## Demo accounts (local only)
All seeded accounts share the password defined as `DEMO_PASSWORD` in `database/install.php` — change it after first login.

| Portal | Email |
|---|---|
| Admin | admin@tuveast.test |
| Inspector | omar.khalid@tuveast.test · faisal.rahman@tuveast.test · yousef.haddad@tuveast.test |
| QC | sara.nasser@tuveast.test |
| Customer | customer@alnoor.test · customer@gulfsteel.test |

KPI scorecards: **Admin → KPI & Reports**, **Inspector → My KPIs**, **QC → QC KPIs**, each with *Export to Excel*; all list pages export to Excel or CSV.
The installer also loads 6 months of demo history for the KPIs (`database/demo_history.php`; second QC reviewer nadia.farouk@tuveast.test).

Inspector ID cards (**Admin → ID Card System**): *Issue Card* creates a CR80 card (front and back print as separate
pages, or both for a duplex card printer); after the printed card is given to the inspector, *Mark as handed over*
makes it valid, shows it under the inspector's **My ID Card** and revokes their previous card. *Validate Card* looks a
card up by ID code, QR link or camera scan. The QR on the back opens `/verify/card/{token}` with a valid / not yet
issued / expired / revoked message. ID photos can be uploaded by the inspector (My ID Card) or the admin (user page or card).

The demo data contains jobs in every state: approved with issued certificates, awaiting QC, returned for rework,
in progress, assigned, and awaiting assignment, plus website / email / phone / portal requests.

## Project layout
```
index.php                front controller + all routes
app/core/                DB, Router, Auth, CSRF, View, Audit/Notify, Workflow (statuses & transitions), helpers
app/services/            business logic (requests, jobs & assignment, inspection, QC, certificates, stickers, templates, documents/PDF)
app/controllers/         thin HTTP controllers per portal + JSON API
app/views/               layouts, portals (admin/inspector/qc/customer), documents (checklist, certificate, sticker)
assets/                  app.css (design system), doc.css (A4 forms), app.js, icons, branding images
database/                schema.sql, seed templates, installer
storage/                 uploads, PDF font cache (not web-accessible)
docs/SPECIFICATION.md    specification
```

## Production checklist
- Set `'env' => 'production'` in `config.local.php` (hides errors and demo logins), use a dedicated DB user.
- Serve over HTTPS (session cookie becomes `secure` automatically).
- For Linux servers add `storage/fonts/Carlito-Regular.ttf` and `Carlito-Bold.ttf` (Calibri-metric font) for PDFs.
- Back up the database and `storage/uploads/`.
