FROM caddy:2

# Set working directory
WORKDIR /var/www/html

# Copy application code (same as in PHP container)
COPY app/ /var/www/html/

# Copy Caddyfile
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

# Expose ports
EXPOSE 80
EXPOSE 443

CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
