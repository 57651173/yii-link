FROM mysql:8.0

# 将 MySQL 配置打进镜像，避免运行时再挂载覆盖
COPY conf/my.cnf /etc/mysql/conf.d/my.cnf

# 可选：初始化脚本（首次初始化数据卷时执行）
# COPY init/ /docker-entrypoint-initdb.d/
