# API BileMo
*Web Service exposant une API REST*

[Lien vers le site](https://bilemo.magali.website)   

[Lien vers la documenation](https://magali-thuaire.github.io/oc-bilemo/)

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/6da11f24de9b463a817d88204aa11c84)](https://www.codacy.com/gh/magali-thuaire/oc-bilemo/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=magali-thuaire/oc-bilemo&amp;utm_campaign=Badge_Grade)

## Compétences

-	Exposer une API REST avec Symfony
-	Lancer une authentification à chaque requête http
-	Produire une documentation technique
-	Concevoir une architecture efficace et adaptée
-	Suivre la qualité́ d’un projet 

## Setup

**Get the git Repository**

Clone over SSH

```
git clone git@github.com:magali-thuaire/oc-bilemo.git
```

Clone over HTTPS

```
git clone https://github.com/magali-thuaire/oc-bilemo.git
```

**Server**

```
Apache 2.4.46
PHP version >=8.0.2
MySQL version >=8.0.28
```


**Download Composer dependencies**

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

**Database Setup**

The code comes with a `docker-compose.yaml` file.
You will still have PHP installed
locally, but you'll connect to a database inside Docker.

First, make sure you have [Docker installed](https://docs.docker.com/get-docker/)
and running. To start the container, run:

```
docker-compose up -d
```

Next, build the database and execute the migrations with:

```
# "symfony console" is equivalent to "bin/console"
# but its aware of the database container
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load
```

(If you get an error about "MySQL server has gone away", just wait
a few seconds and try again - the container is probably still booting).

If you do *not* want to use Docker, just make sure to start your own
database server and update the `DATABASE_URL` environment variable in
`.env` or `.env.local` before running the commands above.

**Generate the SSL keys for Json Web Token**

This application uses JWT authentication.
Generate the SSL keys by running:

```
$ symfony console lexik:jwt:generate-keypair
```

Your keys will land in config/jwt/private.pem and config/jwt/public.pem

**Start the Symfony web server**

You can use Nginx or Apache, but Symfony's local web server
works even better.

To install the Symfony local web server, follow
"Downloading the Symfony client" instructions found
here: [Symfony CLI](https://symfony.com/download) - you only need to do this
once on your system.

Then, to start the web server, open a terminal, move into the
project, and run:

```
symfony serve -d
```

(If this is your first time using this command, you may see an
error that you need to run `symfony server:ca:install` first).

Now check out the site at `https://localhost:8000`

## Documentation

[Documentation](https://magali-thuaire.github.io/oc-bilemo)

**Live documentation**
```
https://localhost:8000/api/doc.json
```

**Generate documentation**
```
symfony console nelmio:apidoc:dump --format=json > json-pretty-formatted.json
```
Go to [Swagger Editor](https://editor-next.swagger.io/) : paste the json-pretty-formatted.json and Generate Client in html2.

## Default Connexions
```
email: client@bilemo.fr
password: bilemo
```

## Functional Tests

```
symfony console doctrine:database:create --env=test
symfony console doctrine:migrations:migrate --env=test
symfony run bin/phpunit 
```

## Deployment

For Apache users that have the error "401 - JWT Token not found", the solution is to rewrite HTTP Authorization header of request, by placing following instructions on your virtualhost :

```
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```
