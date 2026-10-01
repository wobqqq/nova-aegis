<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Enums;

/**
 * Admin paths every vulnerability scanner tries first.
 */
enum InsecurePath: string
{
    case NOVA = 'nova';
    case ADMIN = 'admin';
    case ADMINISTRATOR = 'administrator';
    case ADMIN_PANEL = 'adminpanel';
    case ADMIN_PANEL_DASH = 'admin-panel';
    case ADMIN_AREA = 'admin_area';
    case BACKEND = 'backend';
    case BACKOFFICE = 'backoffice';
    case CONTROL_PANEL = 'controlpanel';
    case CP = 'cp';
    case DASHBOARD = 'dashboard';
    case MANAGE = 'manage';
    case MANAGER = 'manager';
    case PANEL = 'panel';
    case CMS = 'cms';
    case LOGIN = 'login';
    case WP_ADMIN = 'wp-admin';
    case SYSTEM = 'system';
}
