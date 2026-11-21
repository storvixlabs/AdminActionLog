# StorvixLabs_AdminActionLog

Records who changed what in the Magento admin — with old and new values — for Magento Open Source,
which has no built-in action log.

> Need help with installation, priority support or a custom feature? Email **[storvixlabs@gmail.com](mailto:storvixlabs@gmail.com)**.

## What is recorded

| Source | Recorded |
|---|---|
| Admin panel | Create, update and delete of tracked records, with a field-by-field diff |
| Stores > Configuration | Every changed setting, with scope, old and new value; "Use Default" resets |
| Catalog mass "Update attributes" | New values and the affected product IDs |
| REST / SOAP with admin tokens or integrations | Same as admin (optional; ERP/PIM changes become visible) |
| Admin login | Successful logins and failed attempts (username + IP; never the password) |

Each entry stores: date (UTC), user, user type (admin / integration), IP, area, admin action or
API route, entity, record name and ID, and the list of changed fields.

**Tracked entities:** products, categories, CMS pages and blocks, customers, customer groups,
cart and catalog price rules, orders, invoices, shipments, credit memos, admin users, admin roles,
URL rewrites, tax rates, store views and configuration. More can be added in `di.xml`
(`StorvixLabs\AdminActionLog\Model\EntityRegistry`).

**Not recorded:** storefront activity, customer/guest API calls, cron jobs, CLI commands
(`config:set`, imports), and direct SQL.

## How diffs stay accurate

- Diffs are taken before save, while the original data is intact, and written after save in the
  same transaction — a failed save leaves no entry.
- Only real stored fields are compared (EAV attributes or table columns), so transient flags that
  controllers set on models never show up as "changes".
- Numbers are compared numerically (`10` = `10.0000`); empty and null are treated as equal.
- Passwords, tokens, keys, hashes and encrypted configuration values are masked.
- Logging errors are caught and written to `var/log/system.log`; they never block the admin save.

## Admin

- **System > Action Logs > Admin Action Log** — filterable grid (date, user, action, entity, record, IP).
- **View** shows the full entry with an Old / New table.
- ACL: `StorvixLabs_AdminActionLog::log` (view), `StorvixLabs_AdminActionLog::config` (settings).
- Entries cannot be edited or deleted from the admin.

## Configuration

Stores > Configuration > Advanced > Admin Action Log

| Setting | Default |
|---|---|
| Enable Logging | Yes |
| Log Admin Logins and Failed Logins | Yes |
| Log Changes Made Through REST and SOAP | Yes |
| Keep Log Entries For (days) | 180 (0 = forever) |

A daily cron job (`storvixlabs_actionlog_cleanup`, 02:30) removes expired entries in batches.

## Install

```
app/code/StorvixLabs/AdminActionLog
bin/magento module:enable StorvixLabs_AdminActionLog
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Support & Contact

- **Bug reports and questions:** open a GitHub issue (best effort, free).
- **Installation, priority support, custom features or Magento development:**
  [storvixlabs@gmail.com](mailto:storvixlabs@gmail.com)
