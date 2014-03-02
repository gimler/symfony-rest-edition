Symfony REST Edition
========================

[![Build Status](https://travis-ci.org/gimler/symfony-rest-edition.png?branch=2.3)](https://travis-ci.org/gimler/symfony-rest-edition) [![Total Downloads](https://poser.pugx.org/gimler/symfony-rest-edition/downloads.png)](https://packagist.org/packages/gimler/symfony-rest-edition)

Welcome to the Symfony REST Edition - a fully-functional Symfony2
application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start
using Symfony. For a more detailed explanation, see the [Installation][1]
chapter of the Symfony Documentation.

1) Installing the REST Edition
----------------------------------

When it comes to installing the Symfony REST Edition, you have the
following options.

### Use Composer (*recommended*)

As Symfony uses [Composer][2] to manage its dependencies, the recommended way
to create a new project is to use it.

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `create-project` command to generate a new Symfony application:

    php composer.phar create-project gimler/symfony-rest-edition --stability=dev path/to/install

Composer will install Symfony and all its dependencies under the
`path/to/install` directory.

### Download an Archive File

To quickly test Symfony, you can also download an [archive][3] of the Standard
Edition and unpack it somewhere under your web server root directory.

If you downloaded an archive "without vendors", you also need to install all
the necessary dependencies. Download composer (see above) and run the
following command:

    php composer.phar install

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

3) Browsing the Demo Application
--------------------------------

Congratulations! You're now ready to use Symfony.

From the `config.php` page, click the "Bypass configuration and go to the
Welcome page" link to load up your first Symfony page.

You can also use a web-based configurator by clicking on the "Configure your
Symfony Application online" link of the `config.php` page.

To see a real-live Symfony page in action, access the following page:

    web/app_dev.php/notes

Using the console after installing httpie.org first determine a valid session ID,
since the demo app uses a session to "persist" data for simplicity. You can either
use some tool to inspect response headers in your browser to determine an active
session ID or use httpie with a HEAD request copy the ``PHPSESSID`` value from the
``Set-Cookie`` header:

    http HEAD http://symfony-rest-edition.lo/app_dev.php/notes -a restapi:secretpw

So for example:

    HTTP/1.1 200 OK
    Allow: GET, POST
    Cache-Control: no-cache
    Content-Type: text/html; charset=UTF-8
    Date: Sun, 27 Oct 2013 07:05:03 GMT
    Server: Apache/2.2.24 (Unix) DAV/2 PHP/5.4.20 mod_ssl/2.2.24 OpenSSL/0.9.8y
    Set-Cookie: PHPSESSID=lclnc7aem1gdgnmo9nrr4t7hj0; path=/
    X-Debug-Token: 67f03d
    X-Powered-By: PHP/5.4.20

Would mean that the session ID is ``lclnc7aem1gdgnmo9nrr4t7hj0``. Now replace ``[sessionid]``
with the session ID you determined and run the following requests:

    http "http://symfony-rest-edition.lo/app_dev.php/notes" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw
    http POST "http://symfony-rest-edition.lo/app_dev.php/notes" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw < note.json
    http "http://symfony-rest-edition.lo/app_dev.php/notes/0" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw
    http DELETE "http://symfony-rest-edition.lo/app_dev.php/notes/0" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw
    http PUT "http://symfony-rest-edition.lo/app_dev.php/notes/0" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw < note.json
    http PUT "http://symfony-rest-edition.lo/app_dev.php/notes/1" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw < note.json
    http PUT "http://symfony-rest-edition.lo/app_dev.php/notes/2" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw < note.json
    http PUT "http://symfony-rest-edition.lo/app_dev.php/notes/3" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw < note.json
    http "http://symfony-rest-edition.lo/app_dev.php/notes?offset=1&limit=1" Cookie:PHPSESSID=[sessionid] --json -a restapi:secretpw

To run the tests install PHPUnit 3.7+ and call:

    phpunit -c app/

4) Getting started with Symfony
-------------------------------

This distribution is meant to be the starting point for your Symfony
applications, but it also contains some sample code that you can learn from
and play with.

A great way to start learning Symfony is via the [Quick Tour][4], which will
take you through all the basic features of Symfony2.

Once you're feeling good, you can move onto reading the official
[Symfony2 book][5].

A default bundle, `AcmeDemoBundle`, shows you Symfony2 in action. After
playing with it, you can remove it by following these steps:

  * delete the `src/Acme` directory;

  * remove the routing entries referencing AcmeBundle in
    `app/config/routing_dev.yml`;

  * remove the AcmeBundle from the registered bundles in `app/AppKernel.php`;

  * remove the `web/bundles/acmedemo` directory;

  * remove the `security.providers`, `security.firewalls.login` and
    `security.firewalls.secured_area` entries in the `security.yml` file or
    tweak the security configuration to fit your needs.

What's inside?
---------------

The Symfony REST Edition is configured with the following defaults:

  * Twig is the only configured template engine;

  * Translations are activated

  * Doctrine ORM/DBAL is configured;

  * Swiftmailer is configured;

  * Annotations for everything are enabled.

It comes pre-configured with the following bundles:

  * **FrameworkBundle** - The core Symfony framework bundle

  * [**SensioFrameworkExtraBundle**][6] - Adds several enhancements, including
    template and routing annotation capability

  * [**DoctrineBundle**][7] - Adds support for the Doctrine ORM

  * [**TwigBundle**][8] - Adds support for the Twig templating engine

  * [**SecurityBundle**][9] - Adds security by integrating Symfony's security
    component

  * [**SwiftmailerBundle**][10] - Adds support for Swiftmailer, a library for
    sending emails

  * [**MonologBundle**][11] - Adds support for Monolog, a logging library

  * [**AsseticBundle**][12] - Adds support for Assetic, an asset processing
    library

  * **WebProfilerBundle** (in dev/test env) - Adds profiling functionality and
    the web debug toolbar

  * **SensioDistributionBundle** (in dev/test env) - Adds functionality for
    configuring and working with Symfony distributions

  * [**SensioGeneratorBundle**][15] (in dev/test env) - Adds code generation
    capabilities

  * **AcmeDemoBundle** (in dev/test env) - A demo bundle with some example
    code

  * [**FOSRestBundle**][16] - Adds rest functionality

  * [**NelmioApiDocBundle**][17] - Add API documentation features

  * [**BazingaHateoasBundle**][18] - Adds HATEOAS support

  * [**HautelookTemplatedUriBundle**][19] - Adds Templated URIs (RFC 6570) support

  * [**BazingaRestExtraBundle**][20]

Enjoy!

[1]:  http://symfony.com/doc/2.1/book/installation.html
[2]:  http://getcomposer.org/
[3]:  https://github.com/gimler/symfony-rest-edition/archive/master.zip
[4]:  http://symfony.com/doc/2.1/quick_tour/the_big_picture.html
[5]:  http://symfony.com/doc/2.1/index.html
[6]:  http://symfony.com/doc/2.1/bundles/SensioFrameworkExtraBundle/index.html
[7]:  http://symfony.com/doc/2.1/book/doctrine.html
[8]:  http://symfony.com/doc/2.1/book/templating.html
[9]:  http://symfony.com/doc/2.1/book/security.html
[10]: http://symfony.com/doc/2.1/cookbook/email.html
[11]: http://symfony.com/doc/2.1/cookbook/logging/monolog.html
[12]: http://symfony.com/doc/2.1/cookbook/assetic/asset_management.html
[15]: http://symfony.com/doc/2.1/bundles/SensioGeneratorBundle/index.html
[16]: https://github.com/FriendsOfSymfony/FOSRestBundle
[17]: https://github.com/nelmio/NelmioApiDocBundle
[18]: https://github.com/willdurand/BazingaHateoasBundle
[19]: https://github.com/hautelook/TemplatedUriBundle
[20]: https://github.com/willdurand/BazingaRestExtraBundle
