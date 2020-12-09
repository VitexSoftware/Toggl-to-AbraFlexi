Toggl to AbraFlexi
=================


Process all time records within given scope into invoice with items grouped by Clients + Also the CSV Timesheet is attached to Invoice.


Commandline tool i used to issue AbraFlexi invoice using Toggl API



Configuration
-------------


Example environment or .env file contents 

```
TOGGLE_WORKSPACE=123455,12212121
TOGGLE_SCOPE=last_month
TOGGLE_TOKEN=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

ABRAFLEXI_URL="https://demo.abraflexi.eu:5434"
ABRAFLEXI_LOGIN="winstrom"
ABRAFLEXI_PASSWORD="winstrom"
ABRAFLEXI_COMPANY="demo"
ABRAFLEXI_CUSTOMER="DEMO"
ABRAFLEXI_TYP_FAKTURY="FAKTURA"
ABRAFLEXI_CENIK="WORK"

REPORTS_DIR="/tmp/"
```

If workspace number is empty, use all availble workspaces 

Scope can be: **last_month** or  **previous_month**, **two_months_ago**, **last_two_months**
Or month name:     January    February    March    April    May    June    July    August    September    October    November    December

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
sudo apt install abraflexi-toggl-importer
```	    




