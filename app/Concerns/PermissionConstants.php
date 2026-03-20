<?php

namespace App\Concerns;

/**
 * Single source of truth for all application permission strings.
 *
 * Pattern: <menu>.<action>  (e.g. clients.create, clients.read)
 * Actions are always: create · read · update · delete
 */
final class PermissionConstants
{
    /** All application menus that have individual CRUD permissions. */
    public const MENUS = [
        'clients',
        'suppliers',
        'contacts',
        'proposals',
        'calendar',
        'customer_orders',
        'supplier_orders',
        'work_orders',
        'financial_bank',
        'financial_current_account',
        'financial_invoices',
        'digital_archive',
        'access_users',
        'access_permissions',
        'settings_countries',
        'settings_roles',
        'settings_calendar_types',
        'settings_calendar_actions',
        'settings_articles',
        'settings_vat',
        'settings_logs',
        'settings_company',
    ];

    /** Human-readable labels for each menu key (used in permission matrix UI). */
    public const MENU_LABELS = [
        'clients' => 'Clients',
        'suppliers' => 'Suppliers',
        'contacts' => 'Contacts',
        'proposals' => 'Proposals',
        'calendar' => 'Calendar',
        'customer_orders' => 'Customer Orders',
        'supplier_orders' => 'Supplier Orders',
        'work_orders' => 'Work Orders',
        'financial_bank' => 'Bank Accounts',
        'financial_current_account' => 'Customer Accounts',
        'financial_invoices' => 'Supplier Invoices',
        'digital_archive' => 'Digital Archive',
        'access_users' => 'Users',
        'access_permissions' => 'Permission Groups',
        'settings_countries' => 'Countries',
        'settings_roles' => 'Contact Roles',
        'settings_calendar_types' => 'Calendar Types',
        'settings_calendar_actions' => 'Calendar Actions',
        'settings_articles' => 'Articles (catalogues)',
        'settings_vat' => 'VAT Rates',
        'settings_logs' => 'Activity Logs',
        'settings_company' => 'Company Settings',
    ];

    /** The four CRUD action suffixes. */
    public const ACTIONS = ['create', 'read', 'update', 'delete'];

    /**
     * Returns every permission string in <menu>.<action> format.
     *
     * @return string[]
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::MENUS as $menu) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$menu}.{$action}";
            }
        }

        return $permissions;
    }
}
