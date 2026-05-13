# Dev README

---

## Git Rules

Main branch protection is enabled — you **cannot push directly to main**.

- All changes must go through a **Pull Request (PR)**
- Every PR requires **at least one approval** from another team member before it can be merged

---

## Environment Setup

A `.env` file holds **environment variables** — configuration values like database credentials that should not be hardcoded into the project or committed to Git. Each developer has their own local `.env` that is intentionally git-ignored.

To set up your local `.env`, copy the example file:

```bash
cp .env.example .env
```

Then open `.env` and fill in any missing values if needed.

> ⚠️ Do not commit your `.env` file. Ever.

---

## Project Structure

```
src/
  index.php             Entry point of the app

  css/                  All stylesheets go here
                        You are not limited to style.css — create as many
                        .css files as you need, but keep them all in this folder

  js/                   All JavaScript goes here
                        Same as above — main.js is just the starting point,
                        add more files as needed, all under this folder

  php/                  PHP includes and logic files (e.g. db.php)

  database/
    Schema/             SQL files for creating tables and defining the schema
                        → schema.sql goes here

    Data/               SQL files for seeding the database with initial/test data
                        → data.sql goes here
```

---

## Database

The database is **wiped fresh every time** you run `docker compose up`. This is intentional during development so the schema and seed data are always applied cleanly from scratch.

On startup Docker will automatically:

1. Run `Schema/schema.sql` _(if it exists)_
2. Run `Data/data.sql` _(if it exists)_

Keep your `CREATE TABLE` statements in `schema.sql` and your `INSERT` statements in `data.sql`.

---

## Starting the Project

```bash
docker compose up
```

> The first time you run it, use `docker compose up --build` so Docker builds the custom PHP image. After that, plain `docker compose up` is fine.

Once running, open your browser and go to:

| URL | What it is |
|---|---|
| http://localhost:8080 | The website |
| http://localhost:8081 | phpMyAdmin (database GUI) |

**phpMyAdmin login:**
- **Username:** `root`
- **Password:** whatever you have set as `DB_PASS` in your `.env`
