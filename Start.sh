sudo
if [ "" == $(dpkg-query -W --showformat='${Status}\n' php|grep "i") ]; then
  apt-get --yes install php
fi  
if [ "" == $(dpkg-query -W --showformat='${Status}\n' php-cgi|grep "i") ]; then
  apt-get --yes install php-cgi
fi
if [ "" == $(dpkg-query -W --showformat='${Status}\n' wmctrl|grep "i") ]; then
  apt-get --yes install wmctrl
fi
wmctrl -r :ACTIVE: -N "Lion Web Server"
php 'Server/Start.php'