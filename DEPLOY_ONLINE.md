# Deploy HARVEE to Render

The repository includes a PHP/Apache Docker service and `render.yaml`. Node/Firebase is not required by the PHP runtime.

## Setup

1. Provision a MySQL 8 compatible database reachable from Render. Render managed PostgreSQL is incompatible with this application. Create a dedicated database/user and arrange backups.
2. Import `database/harvee_marketplace_all_in_one.sql` into the selected database using your provider's SQL client. Do not import the legacy trigger migrations alongside it. Back up existing installations first; this script is not a complete migration system.
3. Rotate the database password previously committed in `config/database.php`. Removing it from current source does not remove it from Git history.
4. Push the repository, then choose **New > Blueprint** in Render and select this repository.
5. Supply `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and the correct `DB_PORT`. Keep `APP_ENV=production` and `APP_DEBUG=0`.
6. For providers requiring verified MySQL TLS, add the provider CA as a Render secret file and set `DB_SSL_CA` to its absolute path (for example `/etc/secrets/mysql-ca.pem`).
7. Deploy. `/health.php` returns 200 only when the database and essential schema columns are accessible. Import the schema before deploying.

The Blueprint uses a paid Starter service and a 1 GB persistent disk mounted at `/var/data`. Review/delivery uploads and sessions persist there. Startup links `assets/uploads` to that disk. This setup supports one instance; horizontal scaling needs shared storage/sessions. Render files outside the disk are ephemeral.

Apache listens on Render's PORT (default 10000). Render supplies HTTPS; secure session cookies require HTTPS. Internal SQL, config, logs, dotfiles and deployment files are denied by Apache. Uploaded PHP scripts cannot execute.

`.env.example` is reference only; PHP does not load it automatically. Set variables through Render. Never commit credentials.

## Deployment smoke checklist

- `/health.php` returns HTTP 200 and status ok.
- Register/login as customer, farmer and driver; unauthorized dashboard access redirects to login.
- Create a farmer product and customer shipping address, add to cart, and place a COD order.
- Verify totals, shipping recipient/address, stock deduction and confirmation.
- Confirm as farmer, claim/dispatch/deliver as driver and upload proof.
- Rate a delivered order with an image; redeploy and verify uploaded images persist.
- Requests to `/database/harvee_marketplace_all_in_one.sql`, `/config/database.php`, `/.git/config`, `/deploy/start.sh` return 403/404.
- Address remaining items in `FUNCTIONALITY_AUDIT.md` before accepting real orders.

## Local Docker verification

Build: `docker build -t harvee .`

Run: `docker run --rm -p 10000:10000 --env-file .env -v harvee-data:/var/data harvee`

Create a private `.env` with reachable database credentials first. Health checks work over HTTP; authentication needs HTTPS because container session cookies are secure.

References: https://render.com/docs/blueprint-spec and https://render.com/docs/disks
