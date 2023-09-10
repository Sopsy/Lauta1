Vagrant.configure(2) do |config|
    # Base box
    config.vm.box = "ubuntu/focal64"

    # Port forwardings
    config.vm.network "forwarded_port", guest: 80, host: 9002, id: "http"

    # Virtual machine details
    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 4
        vb.memory = 8192
        vb.name = "Ylilauta"
        vb.default_nic_type = "virtio"
        vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]

        if Vagrant::Util::Platform.windows? then
            vb.customize ["modifyvm", :id, "--uartmode1", "client", "NUL"]
        else
            vb.customize ["modifyvm", :id, "--uartmode1", "file", "/dev/null"]
        end
    end

    config.vm.synced_folder ".", "/vagrant", disabled: false

    # Provisioning
    config.vm.provision "shell", path: "vagrant_provision.sh"
end