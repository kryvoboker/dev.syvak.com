FROM mariadb:10.11.5

ARG DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Kyiv

RUN apt-get update \
    && apt-get -y install tzdata \
    && rm -rf /var/lib/apt/lists/*

# set time zone in container
RUN ln -fs /usr/share/zoneinfo/Europe/Kyiv /etc/localtime

# Import UID and GID from docker compose
ARG UID=1000
ARG GID=1000
ARG UMASK=0002

ENV UID=${UID}
ENV GID=${GID}
ENV UMASK=${UMASK}

ENV USERNAME=mysql

# Create mysql group and user with specific UID/GID if they don't match
RUN groupmod -o -g ${GID} ${USERNAME} \
    && usermod -o -u ${UID} -g ${GID} ${USERNAME}

# Ensure proper ownership of MariaDB directories
RUN chown -R ${USERNAME}:${USERNAME} /var/lib/mysql \
    && chown -R ${USERNAME}:${USERNAME} /var/run/mysqld \
    && chown -R ${USERNAME}:${USERNAME} /etc/mysql

# Copy custom entrypoint
COPY .docker/dev/mariadb/entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

USER ${USERNAME}

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
CMD ["mysqld"]