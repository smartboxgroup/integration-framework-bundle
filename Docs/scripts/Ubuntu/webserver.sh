sudo apt-get install -y mysql-server mysql-client
sudo apt-get install -y apache2
sudo apt-get install -y php7.0 libapache2-mod-php7.0 php7.0-cli php7.0-common php7.0-mbstring php7.0-gd php7.0-intl php7.0-xml php7.0-mysql php7.0-mcrypt php7.0-zip php7.0-dev
sudo apt-get install -y php7.0-curl php7.0-xml php7.0-soap php-apcu php-apcu-bc
sudo service apache2 restart
php --ini