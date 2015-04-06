LTI WHMIS Quiz Sample
---------------------

This is an example LTI quiz with a WHMIS theme.

To install, copy these files to the root directory of your webserver and change the variable `$SITE_URL` in `whmis.php` to match your server name (if it is on a weird port, use syntax like `http://example.com:1234`)

The LTI launch point for the quiz is `whmis.php`

# Using Vagrant

If you don't want to install PHP, you can use vagrant to run the app in a virtual machine:

1. Install [Virtualbox](https://www.virtualbox.org/wiki/Downloads)

2. Install [Vagrant](http://www.vagrantup.com/downloads.html)

3. `vagrant up` (this will take a while - e.g. 20 minutes - the first time)

You're done! The application should be running on [localhost:55555/whmis.php](http://localhost:55555/whmis.php)

You may need to run `vagrant provision` if you `git pull` any changes to the Vagrant file.

# Building the Docker container

1. Install [Docker](http://docs.docker.com/installation/)

2. `./build.sh` from the directory containing the Dockerfile

# Running the Docker container

This will expose the app on port 8080:

`docker run --name quiz -p 8080:80 -e OAUTH_KEY=key -e OAUTH_SECRET=secret -e SITE_URL=http://localhost:8080 rschick/lti-quiz-sample`

Environment vars:

**OAUTH\_KEY/OAUTH_SECRET**: OAuth key/secret shared with LTI tool consumer

**SITE_URL**: Base URL for the site (no trailing slash)
