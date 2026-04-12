# Deploy Guide (Railway + Vercel)

## 1) Backend Laravel - Railway

1. Open Railway and create a new project.
2. Choose "Deploy from GitHub repo".
3. Select this repo and choose branch `deploy-railway-vercel`.
4. Set root directory to `backend`.
5. After first deploy, add a MySQL service in the same Railway project.
6. In backend service variables, set:
   - APP_ENV=production
   - APP_DEBUG=false
   - APP_URL=https://<railway-backend-domain>
   - FRONTEND_URL=https://<vercel-frontend-domain>
   - CORS_ALLOWED_ORIGINS=https://<vercel-frontend-domain>
   - DB_CONNECTION=mysql
   - DB_HOST=${{MySQL.MYSQLHOST}}
   - DB_PORT=${{MySQL.MYSQLPORT}}
   - DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   - DB_USERNAME=${{MySQL.MYSQLUSER}}
   - DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   - JWT_SECRET=<generate random string>
   - APP_KEY=<generate with `php artisan key:generate --show`>
7. Run migration once in Railway backend service shell:
   - php artisan migrate --force
   - php artisan db:seed --force (optional)
8. Verify API health:
   - https://<railway-backend-domain>/api/lapangan

## 2) Frontend Vue - Vercel

1. Open Vercel and import the same GitHub repo.
2. Set framework to Vite.
3. Set root directory to `frontend`.
4. Add environment variables:
   - VITE_API_URL=https://<railway-backend-domain>/api
   - VITE_MIDTRANS_CLIENT_KEY=<your-key>
   - VITE_MIDTRANS_IS_PRODUCTION=false
5. Deploy.
6. Test frontend URL and login/admin flows.

## 3) Final checks

1. Check CORS and auth on login endpoint.
2. Check public API response returns lapangan data.
3. Check admin routes with token authentication.
4. Update FRONTEND_URL/CORS_ALLOWED_ORIGINS if you later attach a custom domain.
