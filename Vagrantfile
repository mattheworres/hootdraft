# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.hostname = 'hootdraft-dev'
  config.vm.box = "ubuntu/bionic64"
  config.vm.synced_folder "./", "/hootdraft",
    id: "app",
    owner: "vagrant",
    group: "vagrant",
    mount_options: ["dmode=775,fmode=664"]

  #NOTE: For app-specific settings:
  config.vm.synced_folder "../../phpdraft_settings", "/phpdraft-settings",
    id: "settings",
    owner: "vagrant",
    group: "vagrant",
    mount_options: ["dmode=775,fmode=6775"]

  config.vm.provider "virtualbox" do |vb|
    vb.name = "HootDraft Dev"
    vb.cpus = 1
    vb.memory = 1024
    # Disable the generation of the console log file
    # https://groups.google.com/forum/#!topic/vagrant-up/eZljy-bddoI
    vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected" ]
    vb.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/vagrant", "1"]
  end

  config.vm.provision :shell, path: 'vagrant-config/bootstrap.sh', keep_color: true

  config.vm.network "private_network", type: "dhcp"
  config.vm.network :forwarded_port, guest: 80, host: 8000, hostIp: '127.0.0.1'
  config.vm.network :forwarded_port, guest: 3306, host: 3307, hostIp: '127.0.0.1'
end
