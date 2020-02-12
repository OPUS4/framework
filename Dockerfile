FROM ubuntu:16.04

# The parts of the script are combined by &&. If something changes, always the update of the system is done, to find new packages or versions.
# Update Ubuntu
RUN apt-get update \

# Install system-packages
&& apt-get install -y debconf-utils\
    composer\
    wget\
    unzip\
    ant\
    openjdk-8-jdk\
    sudo

# Install PHP
&& apt-get install -y php\
       php-cli\
       php-dev\
       php-mbstring\
       php-mysql\
       php-curl

