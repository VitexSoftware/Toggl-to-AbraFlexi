FROM debian:latest

env DEBIAN_FRONTEND=noninteractive

RUN apt update ; apt install -y wget; echo "deb http://repo.vitexsoftware.cz buster main" | tee /etc/apt/sources.list.d/vitexsoftware.list ; wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
RUN apt-get update && apt-get install -y locales cron locales-all && rm -rf /var/lib/apt/lists/* \
    && localedef -i cs_CZ -c -f UTF-8 -A /usr/share/locale/locale.alias cs_CZ.UTF-8
ENV LANG cs_CZ.utf8

RUN apt update

RUN apt -y install abraflexi-toggl-importer

CMD [ "/usr/bin/toggl2abraflexi" ]
