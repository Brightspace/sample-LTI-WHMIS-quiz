ENV["VAGRANT_DEFAULT_PROVIDER"] = 'virtualbox'

Vagrant::configure("2") do |config|

	config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"
	config.vm.box = "phusion/ubuntu-14.04-amd64"

	config.vm.provider "virtualbox" do |v, o|
		o.vm.network "forwarded_port", guest: 80, host: 55555

		# this does not work the way it appears, it will still run when doing an aws provided call, but leave for now
		unless Vagrant.has_plugin?("vagrant-vbguest")
			raise 'Please install the vagrant-vbguest plugin! (with the following command: vagrant plugin install vagrant-vbguest)'
		end

		v.memory = 1024
		v.customize ["modifyvm", :id, "--memory", "1024", "--cpus", "2", "--ioapic", "on", "--chipset", "ich9", "--ostype", "Ubuntu_64"]
	end

	config.vm.provision "shell",
		inline: "if [[ $(docker ps -aq) ]]; then docker rm -f $(docker ps -aq); fi"

	config.vm.provision "docker" do |d|
		d.run "php:5.6-apache",
			args: "--name lti-quiz-sample --net=host -v /vagrant:/var/www/html -e OAUTH_KEY=key -e OAUTH_SECRET=secret \
				-e SITE_URL=http://localhost:55555"
	end

	# autostart is broken
	config.vm.provision :shell, run: :always,
		inline: "if [[ $(docker ps -aq) ]]; then docker start $(docker ps -aq); fi"
end
