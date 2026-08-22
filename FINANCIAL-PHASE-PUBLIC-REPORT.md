# Financial V2 — Public ZISWAF Report Hotfix

## Root Cause of the Hosting 404

The application route is correctly registered as a public `GET|HEAD` route:

```text
/laporan-ziswaf                 public.ziswaf.index
/laporan-ziswaf/dana/{fundCode} public.ziswaf.fund
```

It is declared in `routes/web.php` before the authenticated admin group,
uses the correct `PublicZiswafReportController` namespace, has no `auth`
middleware, is not shadowed by a wildcard route, and compiles successfully
with `php artisan route:cache`.

Therefore the hosting-only 404 is a deployment-state defect: the deployed
release has not loaded this route definition, or it is serving a stale route
cache. It is not a second route requirement and must not be worked around by
adding a hosting-only endpoint.

## Fix Applied

- Both public report views now extend `masjid.master-guest`.
- The report now receives the existing MRJ public guest navbar and footer via
  `guest_layout('_navbar')` and `guest_layout('_footer')`.
- The existing `Laporan -> Laporan ZISWAF` desktop and mobile links continue
  to use `route('public.ziswaf.index')`; no localhost URL is hardcoded.
- The controller and reporting service remain read-only and Ledger-backed.

## Local Route and Layout Validation

- `php artisan route:list --path=laporan-ziswaf` lists both public routes.
- `php artisan route:cache` completed successfully, followed by
  `php artisan route:clear` to restore the local development cache state.
- Guest feature coverage verifies HTTP 200, no auth middleware, default
  navbar/footer markup, submenu placement, and no Financial V2 write.
- Public report data continues to come through `PublicZiswafReportService`,
  which reads the approved V2 reporting layer and has no legacy-table source.

## Deployment-Safe Hosting Procedure

Deploy the release containing the public route and views, then execute from
the Laravel release directory:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan route:list --path=laporan-ziswaf
```

The last command must list both `public.ziswaf.index` and
`public.ziswaf.fund`. Then verify a guest request to
`/laporan-ziswaf` returns HTTP 200. These commands do not run migrations,
reset databases, or write Financial V2 accounting facts.

## Hosting Validation Status

No hosting URL or shell access is available in this workspace, so the final
HTTP 200 check must be performed immediately after the deployment commands
above. The source, route cache compilation, and local guest regression are
all verified here.
