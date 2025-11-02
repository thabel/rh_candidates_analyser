# Docker Setup Notes

## CV Uploads Directory Permissions

CV files are uploaded to `var/uploads/cv/`. In your Docker container, ensure this directory has proper write permissions:

```bash
# In your Dockerfile or docker-compose entrypoint:
chmod -R 777 var/uploads/cv
```

Or in your `docker-compose.yml`:

```yaml
services:
  app:
    # ... other config ...
    volumes:
      - ./var/uploads:/app/var/uploads
    # Run this command on container startup:
    entrypoint: |
      sh -c "
        mkdir -p var/uploads/cv
        chmod -R 777 var/uploads/cv
        php-fpm
      "
```

## Database Migrations

After updating the database schema, run:

```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

This will apply the migration to make `cv_text` nullable.
