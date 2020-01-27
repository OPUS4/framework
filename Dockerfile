FROM ubuntu:16.04

# Update Ubuntu
RUN apt-get update
RUN apt-get update

# Install system-packages
RUN apt-get install -y debconf-utils\
    composer\
    wget\
    unzip\
    ant\
    openjdk-8-jdk\
    sudo

# Install PHP
RUN apt-get install -y php\
    php-cli\
    php-dev\
    php-mbstring\
    php-mysql\
    php-curl
