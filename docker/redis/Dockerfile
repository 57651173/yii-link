FROM redis:7-alpine

# 将 Redis 配置打进镜像，避免运行时再挂载覆盖
COPY redis.conf /usr/local/etc/redis/redis.conf

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]

