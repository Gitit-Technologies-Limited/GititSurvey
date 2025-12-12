# Survey Application - Deployment Guide

> Complete guide for deploying the LBS-Survey + Metabase Analytics platform using Docker

## Table of Contents
- [System Overview](#system-overview)
- [Prerequisites](#prerequisites)
- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Installation Steps](#installation-steps)
- [Starting the Application](#starting-the-application)
- [Verification](#verification)
- [Post-Deployment Tasks](#post-deployment-tasks)
- [Troubleshooting](#troubleshooting)
- [Maintenance](#maintenance)
- [Security Considerations](#security-considerations)

---

## System Overview

### Architecture

This deployment consists of 5 Docker containers:

```
┌────────────────────────────────────────────────────┐
│                  Docker Host                       │
├────────────────────────────────────────────────────┤
│                                                    │
│  ┌──────────────┐         ┌──────────────┐         │
│  │  surveyapp   │         │  metabase    │         │
│  │ (LimeSurvey) │         │ (Analytics)  │         │
│  │  Port: 8080  │         │  Port: 3000  │         │
│  └──────┬───────┘         └──────┬───────┘         │
│         │                        │                 │
│         │  ┌──────────────┐      │                 │
│         └─►│    mysql     │◄─────┘                 │
│            │ (App Data)   │                        │
│            │ Port: 3306   │                        │
│            └──────────────┘                        │
│                                                    │
│            ┌──────────────┐                        │
│            │ metabase-db  │                        │
│            │ (PostgreSQL) │                        │
│            │ Port: 5432   │                        │
│            └──────────────┘                        │
│                                                    │
│  ┌──────────────────┐                              │
│  │ surveyapp-migrate│ (runs once on startup)       │
│  └──────────────────┘                              │
└────────────────────────────────────────────────────┘
```

### Services

1. **surveyapp** - PHP 8.1 + Apache running LimeSurvey
2. **mysql** - MySQL 8.0 database for survey data
3. **metabase** - Analytics and reporting dashboard
4. **metabase-db** - PostgreSQL 13 for Metabase metadata
5. **surveyapp-migrate** - One-time migration container

---

## Prerequisites

### Required Software

- **Docker Engine**: 20.10+ ([Install Guide](https://docs.docker.com/engine/install/))
- **Docker Compose**: 2.0+ ([Install Guide](https://docs.docker.com/compose/install/))
- **Git**: For cloning the repository
- **Minimum 4GB RAM** available for Docker
- **Minimum 10GB disk space** for images and data

### Server Requirements

- **Ports**: 8080, 3000 must be available
- **Network**: Outbound internet access for pulling images

### Verify Installation

```bash
# Check Docker version
docker --version
# Expected: Docker version 20.10.0 or higher

# Check Docker Compose version
docker-compose --version
# Expected: Docker Compose version 2.0.0 or higher

# Check Docker is running
docker ps
# Should return empty list or running containers (no errors)

# Check available ports
sudo lsof -i :8080
sudo lsof -i :3000
# Both should return empty (ports not in use)
```

---

## Pre-Deployment Checklist

### 1. Clone Repository

```bash
cd /opt  # or your preferred installation directory
git clone <repository-url> surveyapp
cd surveyapp
git checkout docker-setup-metabase
```

### 2. Create Environment File

```bash
# Copy the example environment file
cp .env.example .env
```

### 3. Configure Environment Variables

Edit the `.env` file and update ALL passwords:

```bash
nano .env  # or use your preferred editor
```

**Critical variables to update:**

```env
# Survey Application Database
MYSQL_ROOT_PASSWORD=<GENERATE_STRONG_PASSWORD>
MYSQL_DATABASE=surveyapp
MYSQL_USER=surveyapp_user
MYSQL_PASSWORD=<GENERATE_STRONG_PASSWORD>

# Metabase Database (PostgreSQL)
METABASE_DB_USER=metabase
METABASE_DB_PASSWORD=<GENERATE_STRONG_PASSWORD>
METABASE_DB_NAME=metabase

# Metabase MySQL Users
METABASE_MYSQL_USER=metabase_user
METABASE_MYSQL_PASSWORD=<GENERATE_STRONG_PASSWORD>
METABASE_READONLY_USER=metabase_readonly
METABASE_READONLY_PASSWORD=<GENERATE_STRONG_PASSWORD>

# Port Mappings (change if ports are already in use)
SURVEYAPP_PORT=8080
METABASE_PORT=3000
```

**Password Generation:**

```bash
# Generate secure random passwords (on Linux/macOS)
openssl rand -base64 32

# Or use this to generate multiple passwords
for i in {1..5}; do echo "Password $i: $(openssl rand -base64 32)"; done
```

### 4. Update Metabase Setup SQL

**IMPORTANT**: Update passwords in `scripts/metabase_setup.sql` to match your `.env` file.

```bash
nano scripts/metabase_setup.sql
```

Update these lines:
- **Line 21**: Replace `'metabase_secure_pass'` with your `METABASE_MYSQL_PASSWORD`
- **Line 26**: Replace `'metabase_readonly_pass'` with your `METABASE_READONLY_PASSWORD`

Example:
```sql
-- Line 21 - Before:
CREATE USER IF NOT EXISTS 'metabase_user'@'%' IDENTIFIED WITH mysql_native_password BY 'metabase_secure_pass';

-- Line 21 - After (use your actual password from .env):
CREATE USER IF NOT EXISTS 'metabase_user'@'%' IDENTIFIED WITH mysql_native_password BY 'your_actual_password_here';
```

### 5. Verify File Permissions

```bash
# Ensure docker-entrypoint.sh is executable
chmod +x docker-entrypoint.sh

# Verify file ownership (adjust user as needed)
sudo chown -R $USER:$USER .
```

### 6. Security Check

```bash
# Ensure .env is NOT committed to git
cat .gitignore | grep ".env"
# Should show: .env

# Verify .env is not tracked
git status .env
# Should show: nothing to commit, working tree clean
# Or: Untracked files: .env
```

---

## Installation Steps

### Step 1: Build Docker Images

```bash
# This will take 25-40 minutes on first run (compiling PHP extensions)
# Subsequent builds will be much faster (cached layers)
docker-compose build

# Monitor build progress
docker-compose build --progress=plain
```

**Expected output:**
```
[+] Building 1234.5s (15/15) FINISHED
 => [internal] load build definition
 => => transferring dockerfile: 1.23kB
 => [1/7] FROM docker.io/library/php:8.1-apache
 ...
 => => naming to docker.io/library/surveyapp
```

### Step 2: Create Docker Volumes

Volumes are created automatically, but you can verify:

```bash
docker volume create surveyapp_mysql_data
docker volume create surveyapp_metabase_data
docker volume create surveyapp_metabase_postgres_data
```

---

## Starting the Application

### First-Time Startup

```bash
# Start all services in foreground (to see logs)
docker-compose up

# Or start in background (detached mode)
docker-compose up -d
```

**Startup sequence:**
1. MySQL starts and initializes (creates databases, runs metabase_setup.sql)
2. PostgreSQL starts for Metabase
3. Migration container runs (checks tables, runs LimeSurvey migrations)
4. Survey app starts (Apache + PHP)
5. Metabase starts (may take 2-3 minutes to initialize)

### Monitor Startup

```bash
# Watch all logs
docker-compose logs -f

# Watch specific service
docker-compose logs -f surveyapp
docker-compose logs -f mysql
docker-compose logs -f metabase

# Check container status
docker-compose ps
```

**Expected healthy output:**
```
NAME                 STATUS                    PORTS
surveyapp            Up                        0.0.0.0:8080->80/tcp
mysql                Up (healthy)              3306/tcp
metabase             Up                        0.0.0.0:3000->3000/tcp
metabase-db          Up (healthy)              5432/tcp
surveyapp-migrate    Exited (0)                N/A
```

---

## Verification

### 1. Check All Containers Are Running

```bash
docker-compose ps
```

All services should show "Up" status (except `surveyapp-migrate` which should be "Exited (0)").

### 2. Test Survey Application

```bash
# Check if surveyapp responds
curl -I http://localhost:8080

# Expected response:
# HTTP/1.1 200 OK
# or
# HTTP/1.1 302 Found (redirect to installer)
```

**Browser test:**
- Open http://localhost:8080 (or http://your-server-ip:8080)
- Should see LimeSurvey homepage or installer

### 3. Test Metabase

```bash
# Check if Metabase responds
curl -I http://localhost:3000

# Expected response:
# HTTP/1.1 200 OK
```

**Browser test:**
- Open http://localhost:3000 (or http://your-server-ip:3000)
- Should see Metabase setup wizard

### 4. Test Database Connectivity

```bash
# Test MySQL connection
docker exec mysql mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e "SHOW DATABASES;"

# Expected output:
# +--------------------+
# | Database           |
# +--------------------+
# | information_schema |
# | metabase           |
# | surveyapp          |
# +--------------------+

# Test PostgreSQL connection
docker exec metabase-db psql -U metabase -d metabase -c "SELECT version();"

# Expected: PostgreSQL version output
```

### 5. Verify Environment Variables

```bash
# Check surveyapp environment
docker exec surveyapp printenv | grep DB_

# Expected output:
# DB_HOST=mysql
# DB_PORT=3306
# DB_NAME=surveyapp
# DB_USER=surveyapp_user
# DB_PASSWORD=<your-password>
```

### 6. Check Logs for Errors

```bash
# Check for errors in logs
docker-compose logs | grep -i error
docker-compose logs | grep -i warning

# If no critical errors, you're good to proceed
```

---

## Post-Deployment Tasks

### 1. Complete LimeSurvey Installation

1. Navigate to http://your-server:8080/installer
2. Follow the installation wizard
3. Database settings (use values from .env):
   - Database type: MySQL
   - Database host: `mysql`
   - Database port: `3306`
   - Database name: `surveyapp`
   - Database user: `surveyapp_user`
   - Database password: `<from .env MYSQL_PASSWORD>`
4. Create admin account
5. Complete installation

### 2. Configure Metabase

1. Navigate to http://your-server:3000
2. Complete initial setup wizard:
   - Create admin account
   - Skip "Add your data" (we'll do this manually)
3. Add MySQL data source:
   - Settings → Admin → Databases → Add Database
   - **Name**: Survey Data
   - **Database type**: MySQL
   - **Host**: `mysql`
   - **Port**: `3306`
   - **Database name**: `surveyapp`
   - **Username**: `metabase_readonly`
   - **Password**: `<from .env METABASE_READONLY_PASSWORD>`
   - Click "Save"
4. Verify connection is successful

### 3. Configure Application Settings

Update LimeSurvey configuration if needed:

```bash
# Edit config.php
docker exec -it surveyapp nano /var/www/html/application/config/config.php
```

## Troubleshooting

### Container Won't Start

**Problem**: Container fails to start or exits immediately

```bash
# Check logs
docker-compose logs <service-name>

# Common issues:
# 1. Port already in use
docker-compose ps
sudo lsof -i :8080
sudo lsof -i :3000

# Solution: Stop conflicting service or change port in .env

# 2. Permission issues
sudo chown -R $USER:$USER .
chmod +x docker-entrypoint.sh
```

### Database Connection Failed

**Problem**: Application can't connect to database

```bash
# Check MySQL is healthy
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection manually
docker exec mysql mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e "SHOW DATABASES;"

# If password incorrect, check .env matches config
cat .env | grep MYSQL_PASSWORD
docker exec surveyapp printenv DB_PASSWORD
```

### Port Already in Use Error

**Problem**: `Bind for 0.0.0.0:8080 failed: port is already allocated`

```bash
# Find what's using the port
sudo lsof -i :8080

# Option 1: Stop the conflicting service
docker stop <container-name>

# Option 2: Change port in .env
nano .env
# Set SURVEYAPP_PORT=8081

# Restart services
docker-compose down
docker-compose up -d
```

### Metabase Won't Start

**Problem**: Metabase container keeps restarting

```bash
# Check logs
docker-compose logs metabase

# Common causes:
# 1. PostgreSQL not ready - wait 2-3 minutes
# 2. Out of memory - increase Docker memory limit
# 3. Database migration failed

# Solution: Check Metabase database
docker exec metabase-db psql -U metabase -d metabase -c "\dt"

# If empty, recreate volume
docker-compose down
docker volume rm surveyapp_metabase_postgres_data
docker-compose up -d
```

### Migration Container Fails

**Problem**: `surveyapp-migrate` exits with error

```bash
# Check migration logs
docker-compose logs migrate

# Common issues:
# 1. Database not ready - increase healthcheck wait time
# 2. Migration files missing
# 3. Permission issues

# Manual migration
docker exec -it surveyapp bash
cd /var/www/html
php application/commands/console.php migrate up
```

### Slow First Build

**Problem**: Docker build takes 30+ minutes

This is **NORMAL** on first build. PHP extensions are compiled from source.

```bash
# Monitor build progress
docker-compose build --progress=plain

# To speed up future builds, don't delete images:
docker-compose down  # NOT: docker-compose down --volumes --rmi all
```

### Can't Access Application from Browser

**Problem**: Connection refused or timeout

```bash
# Check container is running
docker-compose ps

# Check port binding
docker port surveyapp

# Check firewall
sudo ufw status
sudo ufw allow 8080/tcp
sudo ufw allow 3000/tcp

# If on remote server, check cloud firewall (AWS Security Groups, etc.)
```

---

## Maintenance

### Viewing Logs

```bash
# All services
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# Specific service
docker-compose logs -f surveyapp

# Last 100 lines
docker-compose logs --tail=100
```

### Restarting Services

```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart surveyapp
docker-compose restart metabase

# Stop all services
docker-compose stop

# Start all services
docker-compose start
```

### Updating Application Code

```bash
# Pull latest code
git pull origin "branch"

# Rebuild containers (if Dockerfile changed)
docker-compose build

# Restart services
docker-compose down
docker-compose up -d

# Run migrations if needed
docker-compose run --rm migrate
```

### Cleaning Up

```bash
# Stop and remove containers (keeps data)
docker-compose down

# Remove containers AND volumes (deletes all data!)
docker-compose down -v

# Remove unused images
docker image prune -a

# Clean up everything Docker (use with caution!)
docker system prune -a --volumes
```


## Security Considerations

### 1. Environment Variables

- **Never commit `.env` to version control**
- Use strong, unique passwords (32+ characters)
- Store `.env` backup securely (encrypted)

### 2. Network Security

```bash
# Use firewall to restrict access
sudo ufw enable
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 8080/tcp # Survey app
sudo ufw allow 3000/tcp # Metabase

```

### 3. Database Security

- MySQL and PostgreSQL are not exposed to host (internal Docker network only)
- Use read-only user for Metabase (`metabase_readonly`)
- Regular backups (automated daily)
- Enable MySQL slow query log for monitoring

### 4. File Permissions

```bash
# Ensure proper ownership
docker exec surveyapp chown -R www-data:www-data /var/www/html/upload
docker exec surveyapp chown -R www-data:www-data /var/www/html/tmp

# Verify permissions
docker exec surveyapp ls -la /var/www/html/upload
```


## Quick Reference

### Common Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f

# Check status
docker-compose ps

# Restart service
docker-compose restart surveyapp

# Execute command in container
docker exec -it surveyapp bash

# Database backup
docker exec mysql mysqldump -uroot -p$MYSQL_ROOT_PASSWORD surveyapp > backup.sql

# Database restore
docker exec -i mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD surveyapp < backup.sql
```

### Service URLs

- **Survey Application**: http://localhost:8080
- **Metabase Analytics**: http://localhost:3000
- **MySQL** (internal): mysql:3306
- **PostgreSQL** (internal): metabase-db:5432

### Support

For issues or questions:
1. Check this documentation
2. Review logs: `docker-compose logs`
3. Check GitHub issues
4. Contact system administrator

---
