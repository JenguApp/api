########################################################################################################################
## Vagrant Configuration File
########################################################################################################################

########################################################################################################################
## Bootstrapping / Requirements
########################################################################################################################
Vagrant.require_version ">= 1.9.0"

unless Vagrant.has_plugin?("vagrant-hostsupdater")
  raise 'vagrant-hostsupdater is not installed! Run "vagrant plugin install vagrant-hostsupdater"'
end

Vagrant.configure("2") do |config|

    ####################################################################################################################
    ## Shared Ansible Configuration Method
    ####################################################################################################################
    def shared_ansible_config(ansible)
        ansible.playbook = "ansible/playbook.yml"
        ansible.host_key_checking = true
        ansible.become = true
    end
    
    ####################################################################################################################
    ## dev-api.jengu.app (resolves to local connection)
    ####################################################################################################################
        
    config.vm.define "dev" do |dev|
        dev.vm.network "private_network", ip: "172.28.145.110"
        dev.vm.hostname = "dev-api.jengu.app"
        dev.hostsupdater.aliases = [
            "dev-assets.jengu.app",
            "dev-socket.jengu.app",
        ]

        dev.vm.box = "ubuntu/bionic64"
        dev.vm.synced_folder ".", "/vagrant",
              type:"nfs",
              mount_options: %w{rw,async,fsc,nolock,vers=3,udp,rsize=32768,wsize=32768,hard,noatime,actimeo=2}

        dev.vm.provider :virtualbox do |vb|
            vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
            vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
            vb.customize ["modifyvm", :id, "--ostype", "Ubuntu_64"]
            vb.customize ["guestproperty", "set", :id, "/VirtualBox/GuestAdd/VBoxService/--timesync-set-threshold", 10000]
            
            vb.memory = 2048
            vb.name = "Jengu_API"
        end


        ## if we're upping this, do the following tasks:
        if ARGV[0] == 'up'
            ## add the key
            dev.vm.provision "file", source: "~/.ssh/id_rsa.pub", destination: "/tmp/key.pub"
            ## add the user
            dev.vm.provision "shell", path: "vagrant-do-provision.sh"
        end

        dev.vm.provision "ansible" do |ansible|
            shared_ansible_config ansible
            ansible.host_key_checking = false ## override for local
            ansible.extra_vars = {
                server_name: dev.vm.hostname,
                socket_server_name: "dev-socket.jengu.app",
                asset_server_name: "dev-assets.jengu.app",
                server_env: "development",
                notification_email: "dev@jengu.app"
            }
        end
    end

    ####################################################################################################################
    ## api.jengu.app ** PRODUCTION **
    ####################################################################################################################

    # config.vm.define "prod" do |prod|

    #     unless Vagrant.has_plugin?("vagrant-digitalocean")
    #       raise 'vagrant-digitalocean is not installed! Run "vagrant plugin install vagrant-digitalocean"'
    #     end

    #     ## Local git-ignored credential file: ./vagrant-credentials.rb
    #     ## Should contain (without dashes) (where the provider token is the private Digital Ocean Token)
    #     ## --------
    #     ## PROVIDER_TOKEN = 'xyz'
    #     ## --------
    #     load 'vagrant-credentials.rb'

    #     prod.vm.hostname = "api.jengu.app"

    #     prod.vm.box = "digital_ocean"
    #     prod.vm.box_url = "https://github.com/devopsgroup-io/vagrant-digitalocean/raw/master/box/digital_ocean.box"
  	 #    prod.vm.synced_folder ".", "/vagrant", disabled: "true"

    #     prod.vm.provider :digital_ocean do |provider, override|
    #         override.ssh.private_key_path = "/Users/brycemeyer/.ssh/id_rsa"
    #         override.ssh.username = "vagrant"
    #         provider.ssh_key_name = "Bryce"
    #         provider.token = PROVIDER_TOKEN
    #         provider.image = "ubuntu-16-04-x64"
    #         provider.region = "nyc3"
    #         provider.size = "s-1vcpu-1gb"
    #         provider.backups_enabled = "true"
    #     end

    #     ## if we're upping this, do the following tasks:
    #     if ARGV[0] == 'up'
    #         ## add the key
    #         prod.vm.provision "file", source: "/Users/brycemeyer/.ssh/id_rsa.pub", destination: "/tmp/id_rsa.pub"
    #         ## add the user
    #         prod.vm.provision "shell", path: "vagrant-do-provision.sh"
    #     end

    #     prod.vm.provision "ansible" do |ansible|
    #         shared_ansible_config ansible
    #         ansible.host_key_checking = false ## override for local
    #         ansible.extra_vars = {
    #             server_name: prod.vm.hostname,
    #             asset_server_name: "assets.jengu.app",
    #             server_env: "production",
    #             notification_email: "dev@jengu.app"
    #         }
    #     end
    # end
end