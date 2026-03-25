FROM mariadb:11

# Install curl for downloading SQL files
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Copy init script
COPY docker/db/init.sh /docker-entrypoint-initdb.d/99-init.sh
RUN chmod +x /docker-entrypoint-initdb.d/99-init.sh

# Expose port
EXPOSE 3306
