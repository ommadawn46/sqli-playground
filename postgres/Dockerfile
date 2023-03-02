FROM postgres:11

RUN rm /etc/apt/sources.list.d/pgdg.list && \
  apt-get update && apt-get -y install apt-transport-https && \
  echo "deb https://apt-archive.postgresql.org/pub/repos/apt/ stretch-pgdg main" > /etc/apt/sources.list.d/pgdg.list && \
  apt-get update && apt-get -y install git gcc postgresql-server-dev-11
