# Deploy HARVEE to Render

The repository includes PostgreSQL support and a PHP/Apache Docker service and `render.yaml`. Node/Firebase is not required by the PHP runtime.

## Setup

1. Push the repository, then choose **New > Blueprint** in Render and select this repository.
2. The Blueprint creates a free managed PostgreSQL 17 database and connects it using its internal `DATABASE_URL`. Both services use Singapore.
3. Deploy. Startup runs `php deploy/migrate.php`, which imports `database/postgresql.sql` once and records the migration. Later restarts skip the applied migration. The health check verifies essential tables.
4. For an existing PostgreSQL database, set `DATABASE_URL` to that database and back up first. The initializer targets fresh installations; it is not a MySQL data converter or an upgrade of an arbitrary existing schema.
5. Existing MySQL records are not copied automatically. Export and transform them separately, preserve foreign-key IDs, and reset PostgreSQL identity sequences after import before accepting new writes.
6. Rotate the database password previously committed in this repository; deleting it from source does not remove it from Git history.

Use only `database/postgresql.sql`. The other SQL files are retained as legacy MySQL references and must not be imported into PostgreSQL.

The Blueprint uses only a **Free web service and Free PostgreSQL database**, with no paid disk or other paid resources. `/var/data` is a temporary runtime directory. Uploads and PHP sessions are lost on redeploy, restart or idle spin-down; users need to log in again and uploaded review/delivery images may stop loading. Durable image uploads require separately configured external storage; none is configured here.

Render's free web service sleeps after 15 minutes without traffic and takes about a minute to wake up. Free PostgreSQL has a 1 GB limit and expires 30 days after creation. Export data before expiry; this is a demo/testing deployment. Do not assume the database is permanent. Only one free PostgreSQL database can be active per workspace.

Service plans are free, but Render separately meters bandwidth/build usage. For strict zero spending, do not enable paid upgrades and review the workspace's payment/spend settings; a YAML file cannot cap account-level charges. Without a payment method, exceeding the included allowances can suspend services or builds.

Apache listens on Render's PORT (default 10000). Render supplies HTTPS; secure session cookies require HTTPS. Internal SQL, config, logs, dotfiles and deployment files are denied by Apache. Uploaded PHP scripts cannot execute.

`.env.example` is reference only; PHP does not load it automatically. Set variables through Render. Never commit credentials.

## Deployment smoke checklist

- `/health.php` returns HTTP 200 and status ok.
- Register/login as customer, farmer and driver; unauthorized dashboard access redirects to login.
- Create a farmer product and customer shipping address, add to cart, and place a COD order.
- Verify totals, shipping recipient/address, stock deduction and confirmation.
- Confirm as farmer, claim/dispatch/deliver as driver and upload proof.
- Rate a delivered order with an image. Expect that image and the login session to disappear after a redeploy/restart on the free service; verify PostgreSQL order records remain until database expiry.
- Requests to `/database/postgresql.sql`, `/config/database.php`, `/.git/config`, `/deploy/start.sh` return 403/404.
- Address remaining items in `FUNCTIONALITY_AUDIT.md` before accepting real orders.

## Local Docker verification

Build: `docker build -t harvee .`

Run: `docker run --rm -p 10000:10000 --env-file .env harvee`

Create a private `.env` with a reachable PostgreSQL DATABASE_URL first. Health checks work over HTTP; authentication needs HTTPS because container session cookies are secure.

References: https://render.com/docs/blueprint-spec, https://render.com/docs/free, https://render.com/docs/postgresql-creating-connecting

## Automated PostgreSQL checks

Run `npm --prefix tests install`, then `npm --prefix tests test`. The tests use PGlite (PostgreSQL compiled to WebAssembly), import the schema twice, plan static PHP SQL statements, and exercise generated IDs/names, addresses, cart checkout, inventory, and case-insensitive search. They do not exercise PHP/PDO, dynamically assembled queries, concurrent checkout or the Render container.

PostgreSQL flag fields use constrained SMALLINT values 0/1 to match existing PHP forms. JSON payload fields remain serialized JSON text to preserve existing PHP decoding and image checks. Enum values use CHECK constraints. Updated timestamps are maintained with PostgreSQL triggers.
