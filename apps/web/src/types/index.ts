// Shared TypeScript types for the web application.
// Shapes mirror the Laravel API resources (see packages/contracts/openapi/v1.yaml).

export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface User {
  id: string;
  name: string;
  email: string;
  status: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export type OrganizationStatus = 'active' | 'suspended' | 'closed';

export interface Organization {
  id: string;
  name: string;
  slug: string;
  legal_name: string | null;
  registration_number: string | null;
  tax_identification_number: string | null;
  phone: string | null;
  email: string | null;
  country_code: string | null;
  currency_code: string;
  timezone: string;
  status: OrganizationStatus;
  created_at: string;
  updated_at: string;
}

export type BranchStatus = 'active' | 'inactive' | 'closed';

export interface Branch {
  id: string;
  organization_id: string;
  name: string;
  code: string;
  phone: string | null;
  email: string | null;
  address_line_1: string | null;
  address_line_2: string | null;
  city: string | null;
  state: string | null;
  country_code: string | null;
  is_primary: boolean;
  status: BranchStatus;
  created_at: string;
  updated_at: string;
}

export interface MfaChallenge {
  mfa_required: true;
  challenge: string;
  expires_at: string;
  methods: string[];
}

// ============================================
// Catalogue and inventory
// Mirrors the Laravel API resources; see packages/contracts/openapi/v1.yaml.
// Money and quantities arrive as exact decimal strings and are never parsed
// into binary floats for authoritative use.
// ============================================

export type ProductType = 'stocked' | 'non_stocked' | 'service';

export type StockMovementType =
  | 'opening'
  | 'receipt'
  | 'sale'
  | 'return'
  | 'adjustment'
  | 'transfer_in'
  | 'transfer_out'
  | 'count';

export interface UnitOfMeasure {
  id: string;
  organization_id: string;
  code: string;
  name: string;
  precision: number;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: string;
  organization_id: string;
  name: string;
  description: string | null;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: string;
  organization_id: string;
  name: string;
  sku: string | null;
  type: ProductType;
  price: string;
  tax_code: string | null;
  unit_of_measure?: UnitOfMeasure | null;
  category?: Category | null;
  archived_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface InventoryLocation {
  id: string;
  organization_id: string;
  branch_id: string | null;
  code: string;
  name: string;
  archived_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface StockMovement {
  id: string;
  organization_id: string;
  product_id: string;
  location_id: string;
  type: StockMovementType;
  quantity: string;
  unit_cost: string | null;
  reference: string | null;
  note: string | null;
  occurred_at: string;
  corrects_movement_id: string | null;
  transfer_movement_id: string | null;
  created_at: string;
}

export interface StockTransfer {
  outbound: StockMovement;
  inbound: StockMovement;
}

export interface StockBalance {
  id: string;
  organization_id: string;
  product_id: string;
  location_id: string;
  quantity: string;
  product?: Product;
  location?: InventoryLocation;
  updated_at: string;
}

// Purchasing (see packages/contracts/openapi/v1.yaml, Purchasing tags).

export type GoodsReceiptStatus = 'recorded';

export interface Supplier {
  id: string;
  organization_id: string;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  archived_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface GoodsReceiptLine {
  id: string;
  goods_receipt_id: string;
  product_id: string;
  product?: Product;
  quantity: string;
  unit_cost: string;
  line_total: string;
}

export interface GoodsReceipt {
  id: string;
  organization_id: string;
  branch_id: string | null;
  supplier_id: string | null;
  supplier_name: string | null;
  location_id: string;
  location?: InventoryLocation;
  receipt_number: number;
  document_number: string;
  status: GoodsReceiptStatus;
  received_on: string;
  note: string | null;
  total_cost: string;
  lines: GoodsReceiptLine[];
  created_at: string;
  updated_at: string;
}
// Invoicing (see packages/contracts/openapi/v1.yaml, Invoicing tags).

export type InvoiceStatus = 'draft' | 'issued' | 'voided';

export type TaxMode = 'exclusive' | 'inclusive';

export type NotificationChannelName = 'email' | 'sms' | 'whatsapp';

export type NotificationDeliveryStatus = 'queued' | 'sent' | 'failed';

export interface Customer {
  id: string;
  organization_id: string;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  archived_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface InvoiceLine {
  id: string;
  invoice_id: string;
  product_id: string | null;
  description: string;
  quantity: string;
  unit_price: string;
  discount: string;
  tax_amount: string;
  line_total: string;
}

export interface Invoice {
  id: string;
  organization_id: string;
  branch_id: string | null;
  customer_id: string | null;
  customer_name: string | null;
  invoice_number: number | null;
  document_number: string | null;
  status: InvoiceStatus;
  issued_at: string | null;
  voided_at: string | null;
  due_on: string | null;
  currency: string;
  tax_rate_percent: string | null;
  tax_mode: TaxMode | null;
  subtotal: string;
  discount_total: string;
  tax_total: string;
  grand_total: string;
  note: string | null;
  lines: InvoiceLine[];
  created_at: string;
  updated_at: string;
}

export interface TaxSettings {
  organization_id: string;
  tax_enabled: boolean;
  tax_rate_percent: string | null;
  tax_mode: TaxMode;
  updated_at: string | null;
}

export interface NotificationDelivery {
  id: string;
  organization_id: string;
  entity_type: string;
  entity_id: string;
  channel: NotificationChannelName;
  driver: string;
  recipient: string;
  status: NotificationDeliveryStatus;
  error_message: string | null;
  sent_at: string | null;
  created_at: string;
}

// Reporting (see packages/contracts/openapi/v1.yaml, Reporting tags).

export interface RevenueMonthPoint {
  month: string;
  total: string;
}

export interface DashboardSummary {
  invoiced_total: string;
  issued_invoices: number;
  purchases_total: string;
  customers: number;
  suppliers: number;
  products: number;
  stock_units: string;
  products_in_stock: number;
  invoices_by_status: {
    draft: number;
    issued: number;
    voided: number;
  };
  revenue_by_month: RevenueMonthPoint[];
}
