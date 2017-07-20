# -*- mode: ruby -*-
# vi: set ft=ruby :

# Original vagrant file that was used to create the rabbitmq.box

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"

  config.vm.provision "shell", inline: <<-SHELL
    apt-get update
    wget https://storage.googleapis.com/golang/go1.8.3.linux-amd64.tar.gz
    tar -C /usr/local -xzf go1.8.3.linux-amd64.tar.gz

  SHELL
end
