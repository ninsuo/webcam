# Webcams

A simple webcam viewer that reads pictures from a local storage.

This is a simpler remake of [Webcams](https://github.com/ninsuo/webcam-legacy) which uses components that are difficult to maintain.
You first need to set-up your logins in 

I made this project for myself, so you may install it, but you'd better know Symfony as my explanations will be a bit succinct.

## Installation

This is a classic Symfony 7 project.

```
composer install
```

## Configuration

### Authentication

The whole application is behind basic authentication.

Go to [security.yaml](config/packages/security.yaml) to set-up your accounts.

To generate a password, use:

```shell
php bin/console security:encode-password
```

Now, set these cyphered passwords in `.env.local` file.

