-- Business DB v1 — aligned with agreed requirements (services, text lines, party-level payments, no product catalog).
-- SQLite 3. Execute after empty file: sqlite3 business_v1.sqlite < schema_v1.sql

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------------
-- staffs: system users who create or own records
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staffs (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name         TEXT NOT NULL,
  email             TEXT,
  last_login_at     TEXT,
  is_active         INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  source_row_json   TEXT,
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------------
-- clients
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  legacy_number     TEXT NOT NULL,
  legacy_match_key  TEXT NOT NULL UNIQUE,
  business_name     TEXT,
  first_name        TEXT,
  last_name         TEXT,
  email             TEXT,
  phone_primary     TEXT,
  phone_secondary   TEXT,
  address_line1     TEXT,
  address_line2     TEXT,
  city              TEXT,
  state_region      TEXT,
  postal_code       TEXT,
  country_code      TEXT,
  notes             TEXT,
  assigned_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_clients_legacy ON clients(legacy_number);
CREATE INDEX IF NOT EXISTS idx_clients_match ON clients(legacy_match_key);
CREATE INDEX IF NOT EXISTS idx_clients_deleted ON clients(is_deleted);

-- ---------------------------------------------------------------------------
-- client_contacts: extra contact rows per client (optional)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS client_contacts (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  client_id         INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
  label             TEXT,
  first_name        TEXT,
  last_name         TEXT,
  email             TEXT,
  phone_home        TEXT,
  phone_mobile      TEXT,
  notes             TEXT,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_client_contacts_client ON client_contacts(client_id);

-- ---------------------------------------------------------------------------
-- suppliers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  legacy_number     TEXT UNIQUE,
  business_name     TEXT,
  first_name        TEXT,
  last_name         TEXT,
  email             TEXT,
  phone_primary     TEXT,
  phone_secondary   TEXT,
  address_line1     TEXT,
  address_line2     TEXT,
  city              TEXT,
  state_region      TEXT,
  postal_code       TEXT,
  country_code      TEXT,
  notes             TEXT,
  assigned_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_suppliers_legacy ON suppliers(legacy_number);
CREATE INDEX IF NOT EXISTS idx_suppliers_deleted ON suppliers(is_deleted);

CREATE TABLE IF NOT EXISTS supplier_contacts (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  supplier_id       INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
  first_name        TEXT,
  last_name         TEXT,
  email             TEXT,
  phone_home        TEXT,
  phone_mobile      TEXT,
  notes             TEXT,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_supplier_contacts_supplier ON supplier_contacts(supplier_id);

-- ---------------------------------------------------------------------------
-- invoices: commercial document (no embedded payment schedule)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoices (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  legacy_invoice_no TEXT,
  client_id         INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
  document_date     TEXT NOT NULL,
  issue_date        TEXT,
  due_date          TEXT,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  discount_amount   TEXT NOT NULL DEFAULT '0',
  total_amount      TEXT NOT NULL,
  notes             TEXT,
  status            TEXT NOT NULL DEFAULT 'issued' CHECK (status IN ('draft', 'issued', 'void')),
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE (legacy_invoice_no)
);

CREATE INDEX IF NOT EXISTS idx_invoices_client ON invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(document_date);

-- ---------------------------------------------------------------------------
-- invoice_lines: free text only — no product / service catalog FK
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoice_lines (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_id        INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
  line_order        INTEGER NOT NULL DEFAULT 0,
  title             TEXT NOT NULL,
  description       TEXT,
  unit_price        TEXT NOT NULL,
  quantity          TEXT NOT NULL DEFAULT '1',
  line_total        TEXT NOT NULL,
  source_row_json   TEXT,
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_invoice_lines_invoice ON invoice_lines(invoice_id);

-- ---------------------------------------------------------------------------
-- purchase_orders
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_orders (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  legacy_po_no      TEXT,
  supplier_id       INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
  document_date     TEXT NOT NULL,
  due_date          TEXT,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  discount_amount   TEXT NOT NULL DEFAULT '0',
  total_amount      TEXT NOT NULL,
  notes             TEXT,
  status            TEXT NOT NULL DEFAULT 'issued' CHECK (status IN ('draft', 'issued', 'void')),
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE (legacy_po_no)
);

CREATE INDEX IF NOT EXISTS idx_po_supplier ON purchase_orders(supplier_id);

CREATE TABLE IF NOT EXISTS purchase_order_lines (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  purchase_order_id INTEGER NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
  line_order        INTEGER NOT NULL DEFAULT 0,
  title             TEXT NOT NULL,
  description       TEXT,
  unit_price        TEXT NOT NULL,
  quantity          TEXT NOT NULL DEFAULT '1',
  line_total        TEXT NOT NULL,
  source_row_json   TEXT,
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_pol_po ON purchase_order_lines(purchase_order_id);

-- ---------------------------------------------------------------------------
-- client_payments: many rows per client; NOT tied to a single invoice (per agreement)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS client_payments (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  client_id         INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
  amount            TEXT NOT NULL,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  paid_at           TEXT NOT NULL,
  method            TEXT,
  bank_reference      TEXT,
  notes             TEXT,
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_client_payments_client ON client_payments(client_id);
CREATE INDEX IF NOT EXISTS idx_client_payments_paid_at ON client_payments(paid_at);

-- ---------------------------------------------------------------------------
-- supplier_payments: many rows per supplier
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_payments (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  supplier_id       INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
  amount            TEXT NOT NULL,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  paid_at           TEXT NOT NULL,
  method            TEXT,
  bank_reference      TEXT,
  notes             TEXT,
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_supplier_payments_supplier ON supplier_payments(supplier_id);

-- ---------------------------------------------------------------------------
-- expenses: narrative / free text — no vendor master FK
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expenses (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  description       TEXT NOT NULL,
  amount            TEXT NOT NULL,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  expense_date      TEXT NOT NULL,
  notes             TEXT,
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_expenses_date ON expenses(expense_date);

-- ---------------------------------------------------------------------------
-- income_entries: other cash-in lines (legacy Incomes not modeled as client_payment)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS income_entries (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  description       TEXT NOT NULL,
  amount            TEXT NOT NULL,
  currency_code     TEXT NOT NULL DEFAULT 'ILS',
  income_date       TEXT NOT NULL,
  notes             TEXT,
  recorded_by_staff_id INTEGER REFERENCES staffs(id) ON DELETE SET NULL,
  source_row_json   TEXT,
  is_deleted        INTEGER NOT NULL DEFAULT 0 CHECK (is_deleted IN (0, 1)),
  created_at        TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_income_entries_date ON income_entries(income_date);

-- ---------------------------------------------------------------------------
-- Legacy catalog (not used in new business flows; preserves XML for audit)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS legacy_catalog_products (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  payload_json      TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS legacy_catalog_projects (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  payload_json      TEXT NOT NULL
);

-- ---------------------------------------------------------------------------
-- import_audit: optional traceability for migration batches
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS import_audit (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  batch_name        TEXT NOT NULL,
  source            TEXT NOT NULL,
  notes             TEXT,
  created_at        TEXT NOT NULL DEFAULT (datetime('now'))
);
