# -*- mode: ruby -*-
# vi: set ft=ruby :

# Original vagrant file that was used to create the rabbitmq.box

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"

  config.vm.provision "shell", inline: <<-SHELL
    sudo add-apt-repository ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install -y php7.1 php7.1-dom

    wget -q https://storage.googleapis.com/golang/go1.8.3.linux-amd64.tar.gz
    tar -C /usr/local -xzf go1.8.3.linux-amd64.tar.gz
    echo "export PATH=$PATH:/usr/local/go/bin" > .bashrc
  SHELL
end
