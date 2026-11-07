<?php

namespace App\Enums;

enum PermissionKey: string
{
    case OrganizationView = 'organization.view';
    case OrganizationUpdate = 'organization.update';
    case OrganizationLifecycle = 'organization.lifecycle';
    case BranchView = 'branch.view';
    case BranchCreate = 'branch.create';
    case BranchUpdate = 'branch.update';
    case BranchLifecycle = 'branch.lifecycle';
    case BranchSetPrimary = 'branch.set_primary';
    case MembershipView = 'membership.view';
    case MembershipInvite = 'membership.invite';
    case MembershipSuspend = 'membership.suspend';
    case MembershipActivate = 'membership.activate';
    case MembershipRevoke = 'membership.revoke';
    case RoleView = 'role.view';
    case RoleCreate = 'role.create';
    case RoleUpdate = 'role.update';
    case RoleDelete = 'role.delete';
    case RoleAssign = 'role.assign';
    case ProductView = 'product.view';
    case ProductCreate = 'product.create';
    case ProductUpdate = 'product.update';
    case ProductArchive = 'product.archive';
    case CatalogueView = 'catalogue.view';
    case CatalogueManage = 'catalogue.manage';
    case StockView = 'stock.view';
    case StockMove = 'stock.move';
    case StockAdjust = 'stock.adjust';
    case StockTransfer = 'stock.transfer';
    case StockCount = 'stock.count';
    case SupplierView = 'supplier.view';
    case SupplierCreate = 'supplier.create';
    case SupplierUpdate = 'supplier.update';
    case SupplierArchive = 'supplier.archive';
    case PurchaseView = 'purchase.view';
    case PurchaseReceive = 'purchase.receive';
    case CustomerView = 'customer.view';
    case CustomerCreate = 'customer.create';
    case CustomerUpdate = 'customer.update';
    case CustomerArchive = 'customer.archive';
    case InvoiceView = 'invoice.view';
    case InvoiceCreate = 'invoice.create';
    case InvoiceUpdate = 'invoice.update';
    case InvoiceIssue = 'invoice.issue';
    case InvoiceVoid = 'invoice.void';
    case InvoiceSend = 'invoice.send';
    case InvoiceManage = 'invoice.manage';
    case ExpenseCategoryView = 'expense_category.view';
    case ExpenseCategoryCreate = 'expense_category.create';
    case ExpenseCategoryUpdate = 'expense_category.update';
    case ExpenseCategoryArchive = 'expense_category.archive';
    case ExpenseView = 'expense.view';
    case ExpenseCreate = 'expense.create';
    case ExpenseUpdate = 'expense.update';
    case ExpenseVoid = 'expense.void';

    public static function keys(): array
    {
        return array_column(self::cases(), 'value');
    }
}
