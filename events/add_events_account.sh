#!/bin/bash
# Precondition: Put /usr/lib/openssh/sftp-server in /etc/shells list
EXPECTED_ARGS=5
E_BADARGS=65
if [ $# -ne $EXPECTED_ARGS ]
then
 echo "Adds an event account that gives database access and inserts that user into the database"
 echo "Usage: sudo $0 event_short_name firstName lastName email 'event name with spaces'"
 exit $E_BADARGS
fi
event_short_name=$1
firstName=$2
lastName=$3
email=$4
event_name_with_spaces=$5
password=`tr -cd '[:alnum:]' < /dev/urandom | fold -w8 | head -n1`
RANDOM=`date +%N|sed s/...$//`
passkey=`printf "%s" "$password$RANDOM" | md5sum`

MYSQL=`which mysql`
Q1="CREATE TABLE IF NOT EXISTS events.$event_short_name ( id int(8) NOT NULL, tags varchar(64) COLLATE utf8_bin DEFAULT NULL, honorific varchar(16) COLLATE utf8_bin DEFAULT NULL COMMENT 'eg Dr., Pdt.', firstName varchar(64) COLLATE utf8_bin NOT NULL, lastName varchar(64) COLLATE utf8_bin DEFAULT NULL, email varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '', phone varchar(64) COLLATE utf8_bin DEFAULT NULL, organization varchar(64) COLLATE utf8_bin DEFAULT NULL, title varchar(64) COLLATE utf8_bin DEFAULT NULL, isComing tinyint(1) NOT NULL DEFAULT '2', oneBedRoom varchar(16) COLLATE utf8_bin DEFAULT NULL, twoBedRoom varchar(16) COLLATE utf8_bin DEFAULT NULL, needVisa tinyint(1) NOT NULL DEFAULT '0', passportName varchar(64) COLLATE utf8_bin DEFAULT NULL, passportCountry varchar(32) COLLATE utf8_bin DEFAULT NULL, passportNumber varchar(32) COLLATE utf8_bin DEFAULT NULL, passportExpiryDate date DEFAULT NULL, arrivalDate date DEFAULT NULL, arrivalTime time DEFAULT NULL, arrivalFlightNumber varchar(16) COLLATE utf8_bin DEFAULT NULL, departureDate date DEFAULT NULL, departureTime time DEFAULT NULL, departureFlightNumber varchar(16) COLLATE utf8_bin DEFAULT NULL, lang varchar(2) COLLATE utf8_bin DEFAULT NULL, cc varchar(256) COLLATE utf8_bin DEFAULT NULL, notes text COLLATE utf8_bin, passkey varchar(32) COLLATE utf8_bin NOT NULL, PRIMARY KEY (id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
Q2="GRANT ALL ON events.$event_short_name TO '$event_short_name'@'localhost' IDENTIFIED BY '$password';"
Q3="FLUSH PRIVILEGES;"
Q4="INSERT INTO events.$event_short_name (id, tags, honorific, firstName, lastName, email, phone, organization, title, isComing, oneBedRoom, twoBedRoom, needVisa, passportName, passportCountry, passportNumber, passportExpiryDate, arrivalDate, arrivalTime, arrivalFlightNumber, departureDate, departureTime, departureFlightNumber, lang, cc, notes, passkey) VALUES ($RANDOM, 'manager', NULL, '$firstName', '$lastName', '$email', NULL, NULL, NULL, '2', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$passkey');"
SQL="${Q1}${Q2}${Q3}${Q4}"
$MYSQL -uroot -ps6QAsw5x -e "$SQL"

path=/var/www/event/$event_short_name
sudo mkdir $path
sudo chown production $path
sudo chgrp developers $path

sudo mkdir $path/classes
sudo chown production $path/classes
sudo chgrp developers $path/classes

printf "<?php\ndefine('EVENT_NAME', '$event_name_with_spaces');\ndefine('EVENT_USERNAME', '$event_short_name');\ndefine('EVENT_PASSWORD', '$password');\n?>" > DatabaseConstants.php
sudo chmod 600 DatabaseConstants.php
sudo chown production DatabaseConstants.php
sudo chgrp developers DatabaseConstants.php
sudo mv DatabaseConstants.php $path/classes/

sudo ln /var/www/events/index.php $path/index.php
sudo chmod 644 $path/index.php

sudo ln /var/www/events/index.js $path/index.js
sudo chmod 644 $path/index.js

sudo ln /var/www/events/classes/Participant.php $path/classes/Participant.php
sudo chmod 644 $path/classes/Participant.php

echo "Event account $event_short_name added, password = $password"