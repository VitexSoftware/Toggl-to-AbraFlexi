Toggl to FlexiBee
=================


Process all time records within given scope into invoice with items grouped by Clients


Commandline tool i used to issue FlexiBee invoice using Toggl API



Configuration
-------------


Example environment or .env file contents 

```
TOGGLE_WORKSPACE=123455
TOGGLE_SCOPE=last_month
TOGGLE_TOKEN=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

FLEXIBEE_URL="https://demo.flexibee.eu:5434"
FLEXIBEE_LOGIN="winstrom"
FLEXIBEE_PASSWORD="winstrom"
FLEXIBEE_COMPANY="demo"
FLEXIBEE_CUSTOMER="DEMO"
FLEXIBEE_TYP_FAKTURY="FAKTURA"
FLEXIBEE_CENIK="WORK"
```

Running
-------

run src/importer.php


Installation
------------

```shell
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.cz $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install flexibee-toggl-importer
```	    




