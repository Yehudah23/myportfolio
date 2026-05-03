# Docker Deployment Guide

This guide helps you deploy the portfolio project using Docker and Docker Compose.

## Prerequisites

- **Docker**: [Install Docker](https://docs.docker.com/get-docker/)
- **Docker Compose**: [Install Docker Compose](https://docs.docker.com/compose/install/)

## Quick Start

### 1. Clone/Navigate to the Repository
```bash
cd /path/to/myportfolio
```

### 2. Build and Start the Containers
```bash
docker-compose up --build
```

This command will:
- Build the PHP/Apache web container
- Start a MySQL 8.0 database container
- Initialize the database with `database.sql`
- Expose the web app on `http://localhost`

### 3. Access Your Application
- **Web App**: http://localhost
- **phpMyAdmin** (optional, add to docker-compose.yml): http://localhost:8081
- **MySQL**: localhost:3306 (from host machine)

## Database Setup

The `database.sql` file is automatically imported into MySQL when the container starts for the first time.

**Default credentials:**
- Username: `root`
- Password: `root_password`
- Database: `my-portfolio`

To access MySQL CLI:
```bash
docker exec -it myportfolio-db mysql -uroot -proot_password my-portfolio
```

## Admin Login

After the database initializes, use:
- **Username**: `admin`
- **Password**: `yehudah23`

## Environment Variables

Edit `docker-compose.yml` to customize:
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
- MySQL `MYSQL_ROOT_PASSWORD`
- Port mappings (default: 80 for web, 3306 for MySQL)

## Common Commands

### Stop Containers
```bash
docker-compose down
```

### Stop and Remove All Data (WARNING: Deletes database!)
```bash
docker-compose down -v
```

### View Logs
```bash
docker-compose logs -f web
docker-compose logs -f db
```

### Rebuild Containers
```bash
docker-compose up --build --force-recreate
```

### Access Web Container Shell
```bash
docker exec -it myportfolio-web /bin/bash
```

## Production Deployment

### For Production on Your Server:

1. **Use environment variables** for sensitive data:
   - Create `.env` file (don't commit to git)
   - Reference in `docker-compose.yml` as `${VAR_NAME}`

2. **Use volume mounts** for persistence:
   - Database: `db_data` volume (auto-managed)
   - Logs: Mount `/var/www/html/logs` to host

3. **Configure reverse proxy** (Nginx/Caddy):
   - Route traffic to the web container
   - Handle SSL/TLS certificates

4. **Example Production docker-compose.yml:**
   ```yaml
   services:
     web:
       restart: always
       environment:
         - DB_HOST=db
         - DB_USER=${DB_USER}
         - DB_PASS=${DB_PASS}
   ```

## Troubleshooting

### Database Connection Error
- Ensure `db` service is running: `docker-compose logs db`
- Check credentials match in `docker-compose.yml`
- Wait 10-15 seconds after starting (MySQL takes time to initialize)

### Port Already in Use
- Change port in `docker-compose.yml`:
  ```yaml
  ports:
    - "8080:80"  # Access on http://localhost:8080
  ```

### Permissions Error
- The Dockerfile sets proper ownership to `www-data`
- If issues persist, rebuild: `docker-compose up --build`

### Database Not Initializing
- Delete volume and rebuild:
  ```bash
  docker-compose down -v
  docker-compose up --build
  ```

## Deployment to Cloud Platforms

### Docker Hub
```bash
docker tag myportfolio-web:latest your-username/myportfolio:latest
docker push your-username/myportfolio:latest
```

### Heroku, DigitalOcean App Platform, AWS ECS, etc.
- Use the Dockerfile directly or push to Docker Hub first
- Set environment variables in the platform dashboard
- Configure MySQL service (or use managed database)

## Next Steps

- Update `php/config.php` to accept environment variables if deploying to cloud
- Add HTTPS/SSL configuration
- Consider adding Nginx as reverse proxy for production
- Set up automated backups for database volume
