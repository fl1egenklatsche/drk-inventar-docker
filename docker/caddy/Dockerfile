FROM caddy:2

# Copy Caddyfile into image
COPY Caddyfile /etc/caddy/Caddyfile

# Expose ports
EXPOSE 80
EXPOSE 443

# Run Caddy
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
