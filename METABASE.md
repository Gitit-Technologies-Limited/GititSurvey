# Metabase Automation for LimeSurvey

Complete automation for creating Metabase dashboards - **production ready**.

## Quick Start

### 1. Start Containers

```bash
docker-compose up -d
```

**That's it!** Database views are created automatically for ALL active surveys.

### 2. Set Up Metabase (One Time)

1. Open http://localhost:3000
2. Complete setup wizard
3. Connect to database:
   - Host: **mysql**, Port: **3306**, Database: **dbname**
   - Username: **metabase_readonly**, Password: **metabase_readonly_password**

### 3. Create Queries (Per Survey)

```bash
./setup-metabase-all-queries 123456 your@email.com password
```

**Done!** Browse to "Survey 123456 - All Queries" in Metabase.

### Adding New Surveys

```bash
docker-compose restart  # Auto-detects new surveys
# OR
./add-survey 999999  # Instant, no restart
```

## 📁 Essential Files

| File | Purpose | When It Runs |
|------|---------|--------------|
| **scripts/init_metabase_views.sh** | Auto-creates ALL views |  On container startup |
| **add-survey** | Manually add single survey |  Optional |
| **setup-metabase-all-queries** | Create Metabase queries | Required for dashboards |


## 🎯 Complete Workflow

```bash
# 1. Start containers (views created automatically!)
docker-compose up -d

# Check logs (optional)
docker logs surveyapp-migrate | tail -50

# 2. Set up Metabase (web UI)
# Open http://localhost:3000 and connect database

# 3. Create queries for each survey
./setup-metabase-all-queries 123456 admin@example.com password
./setup-metabase-all-queries 789012 admin@example.com password
```

##  What Gets Created Automatically

### Global Views
- `v_survey_overview` - All surveys with metadata
- `v_question_details` - All questions from all surveys

### Per-Survey Views
- `v_survey_responses_2`
- `v_survey_responses_122673`
- `v_survey_responses_427656`
- etc. (one for each active survey)

Each view includes:
- Response metadata (id, submitdate, status)
- Completion tracking
- Time dimensions (hour, day, week, month)
- ALL question columns (auto-detected)
- Token column (or NULL for anonymous surveys)

### Metabase Queries (Manual)
Run `./setup-metabase-all-queries` to create **23+ queries in 4 collections:**

1. **Overview** - KPIs, trends, volume
2. **Analysis** - Status, patterns, dropouts
3. **Questions** - Catalog, individual analysis
4. **Monitoring** - Latest responses, activity

## How It Works

### Automatic: On Startup

`scripts/init_metabase_views.sh` runs automatically:

1. Waits for MySQL 
2. Creates global views 
3. Finds ALL active surveys 
4. For each survey:
   - Detects if anonymous (no token column) 
   - Detects all question columns 
   - Creates `v_survey_responses_<id>` 
   - Grants permissions 

**Runs every restart** - new surveys auto-detected!

### Manual: ./add-survey (Optional)

Add single survey without restarting:

```bash
./add-survey 123456
```

Steps: [1/7] Check survey → [2/7] Check table → [3/7] Check structure → [4/7] Detect columns → [5/7] Create view → [6/7] Grant permissions → [7/7] Verify

### Manual: ./setup-metabase-all-queries (Required)

Creates 23+ queries in Metabase UI:

```bash
./setup-metabase-all-queries 123456 your@email.com yourpassword
```

1. Logs into Metabase 
2. Gets question structure 
3. Creates 4 organized collections 
4. Creates 23+ queries with visualizations 

## Troubleshooting

### Views Not Created

```bash
# Check logs
docker logs surveyapp-migrate | tail -50

# Manually trigger
docker-compose restart
```

### Survey Not Found

- Survey must exist in SurveyApp
- Survey must be **activated** (creates `survey_XXXXX` table)
- Restart containers after activating: `docker-compose restart`


### Metabase Issues

**Login failed:** Complete setup wizard first at http://localhost:3000

**Database not found:** Add database in Settings → Admin → Databases

**Views exist but queries fail:** Sync database or restart Metabase


## 🔄 Multiple Surveys

```bash
# Views exist for ALL active surveys automatically
# Just create the Metabase queries:

./setup-metabase-all-queries 111111 admin@example.com password
./setup-metabase-all-queries 222222 admin@example.com password
./setup-metabase-all-queries 333333 admin@example.com password

# Each takes ~30 seconds
```

## 📚 Environment Variables

Scripts read from `.env`:

```env
MYSQL_USER=surveyapp_user
MYSQL_PASSWORD=your_password
MYSQL_DATABASE=surveyapp
MYSQL_ROOT_PASSWORD=root_password
METABASE_READONLY_PASSWORD=metabase_readonly_pass
```

## Summary

**Setup:**

```bash
# 1. Start (views auto-created)
docker-compose up -d

# 2. Setup Metabase (web UI, one time)
# http://localhost:3000

# 3. Create queries
./setup-metabase-all-queries <id> email password
```

**Automatic:**
- ✅ All database views
- ✅ Anonymous survey support
- ✅ All question columns detected
- ✅ Permissions granted

**Manual:**
- Metabase setup (one time)
- Query creation (30 sec per survey)

**Result:** 23+ queries, 4 collections, production-ready dashboards.
